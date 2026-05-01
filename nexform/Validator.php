<?php
namespace NexForm;

/**
 * Validator – validates field values against named rules.
 *
 * Rules:
 *   required, email, url, min:N, max:N, minlen:N, maxlen:N,
 *   regex:/pattern/, numeric, alpha, alphanumeric, phone,
 *   date:format, in:a,b,c, not_in:a,b,c, confirmed:field,
 *   file_max:N (bytes), file_types:jpg,png,...
 */
class Validator
{
    /** @var array List of validation errors: ['field' => ['message', ...]] */
    private array $errors = [];

    /**
     * Validate a full field map against data.
     *
     * @param  array $data     ['field' => value, ...]
     * @param  array $fieldMap ['field' => ['rules' => [...], 'label' => '...']]
     * @return bool
     */
    public function validate(array $data, array $fieldMap): bool
    {
        $this->errors = [];

        foreach ($fieldMap as $name => $opts) {
            $rules = $opts['rules']   ?? [];
            $label = $opts['label']   ?? ucfirst(str_replace(['_', '-'], ' ', $name));
            $value = $data[$name]     ?? null;

            foreach ($rules as $rule) {
                $error = $this->applyRule($rule, $name, $value, $label, $data);
                if ($error !== null) {
                    $this->errors[$name][] = $error;
                    break; // stop at first error per field
                }
            }
        }

        return empty($this->errors);
    }

    /** Return errors array. */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** Return first error for a specific field, or null. */
    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    // -------------------------------------------------------
    // Internal rule dispatcher
    // -------------------------------------------------------

    private function applyRule(string $rule, string $name, mixed $value, string $label, array $data): ?string
    {
        // Parse rule with parameter, e.g. "min:5"
        [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($ruleName) {
            'required'     => $this->ruleRequired($value, $label),
            'email'        => $this->ruleEmail($value, $label),
            'url'          => $this->ruleUrl($value, $label),
            'numeric'      => $this->ruleNumeric($value, $label),
            'alpha'        => $this->ruleAlpha($value, $label),
            'alphanumeric' => $this->ruleAlphanumeric($value, $label),
            'phone'        => $this->rulePhone($value, $label),
            'min'          => $this->ruleMin($value, $label, (float)$param),
            'max'          => $this->ruleMax($value, $label, (float)$param),
            'minlen'       => $this->ruleMinLen($value, $label, (int)$param),
            'maxlen'       => $this->ruleMaxLen($value, $label, (int)$param),
            'regex'        => $this->ruleRegex($value, $label, $param),
            'date'         => $this->ruleDate($value, $label, $param ?? 'Y-m-d'),
            'in'           => $this->ruleIn($value, $label, explode(',', $param ?? '')),
            'not_in'       => $this->ruleNotIn($value, $label, explode(',', $param ?? '')),
            'confirmed'    => $this->ruleConfirmed($value, $label, $data[$param] ?? null, $param ?? ''),
            'file_max'     => $this->ruleFileMax($value, $label, (int)$param),
            'file_types'   => $this->ruleFileTypes($value, $label, explode(',', $param ?? '')),
            default        => null,
        };
    }

    // -------------------------------------------------------
    // Individual rules
    // -------------------------------------------------------

    private function isEmpty(mixed $v): bool
    {
        if ($v === null || $v === '') return true;
        if (is_array($v) && empty($v)) return true;
        // File field
        if (is_array($v) && isset($v['error'])) return $v['error'] === UPLOAD_ERR_NO_FILE;
        return false;
    }

    private function ruleRequired(mixed $v, string $label): ?string
    {
        return $this->isEmpty($v) ? "{$label} is required." : null;
    }

    private function ruleEmail(mixed $v, string $label): ?string
    {
        if ($this->isEmpty($v)) return null;
        return filter_var($v, FILTER_VALIDATE_EMAIL) ? null : "{$label} must be a valid email address.";
    }

    private function ruleUrl(mixed $v, string $label): ?string
    {
        if ($this->isEmpty($v)) return null;
        return filter_var($v, FILTER_VALIDATE_URL) ? null : "{$label} must be a valid URL.";
    }

    private function ruleNumeric(mixed $v, string $label): ?string
    {
        if ($this->isEmpty($v)) return null;
        return is_numeric($v) ? null : "{$label} must be a number.";
    }

    private function ruleAlpha(mixed $v, string $label): ?string
    {
        if ($this->isEmpty($v)) return null;
        return ctype_alpha($v) ? null : "{$label} may only contain letters.";
    }

    private function ruleAlphanumeric(mixed $v, string $label): ?string
    {
        if ($this->isEmpty($v)) return null;
        return ctype_alnum($v) ? null : "{$label} may only contain letters and numbers.";
    }

    private function rulePhone(mixed $v, string $label): ?string
    {
        if ($this->isEmpty($v)) return null;
        return preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,3}[)]?[-\s\.]?[0-9]{3,6}[-\s\.]?[0-9]{2,6}$/', trim($v))
            ? null : "{$label} must be a valid phone number.";
    }

    private function ruleMin(mixed $v, string $label, float $min): ?string
    {
        if ($this->isEmpty($v)) return null;
        return (float)$v >= $min ? null : "{$label} must be at least {$min}.";
    }

    private function ruleMax(mixed $v, string $label, float $max): ?string
    {
        if ($this->isEmpty($v)) return null;
        return (float)$v <= $max ? null : "{$label} must not exceed {$max}.";
    }

    private function ruleMinLen(mixed $v, string $label, int $min): ?string
    {
        if ($this->isEmpty($v)) return null;
        return mb_strlen($v, 'UTF-8') >= $min ? null : "{$label} must be at least {$min} characters.";
    }

    private function ruleMaxLen(mixed $v, string $label, int $max): ?string
    {
        if ($this->isEmpty($v)) return null;
        return mb_strlen($v, 'UTF-8') <= $max ? null : "{$label} must not exceed {$max} characters.";
    }

    private function ruleRegex(mixed $v, string $label, ?string $pattern): ?string
    {
        if ($this->isEmpty($v) || !$pattern) return null;
        return preg_match($pattern, $v) ? null : "{$label} format is invalid.";
    }

    private function ruleDate(mixed $v, string $label, string $format): ?string
    {
        if ($this->isEmpty($v)) return null;
        $d = \DateTime::createFromFormat($format, $v);
        return ($d && $d->format($format) === $v) ? null : "{$label} must be a valid date ({$format}).";
    }

    private function ruleIn(mixed $v, string $label, array $allowed): ?string
    {
        if ($this->isEmpty($v)) return null;
        return in_array($v, $allowed, true) ? null : "{$label} contains an invalid selection.";
    }

    private function ruleNotIn(mixed $v, string $label, array $forbidden): ?string
    {
        if ($this->isEmpty($v)) return null;
        return !in_array($v, $forbidden, true) ? null : "{$label} contains a forbidden value.";
    }

    private function ruleConfirmed(mixed $v, string $label, mixed $confirm, string $confirmField): ?string
    {
        if ($this->isEmpty($v)) return null;
        return $v === $confirm ? null : "{$label} does not match {$confirmField}.";
    }

    private function ruleFileMax(mixed $v, string $label, int $maxBytes): ?string
    {
        if (!is_array($v) || !isset($v['size']) || $v['error'] === UPLOAD_ERR_NO_FILE) return null;
        return $v['size'] <= $maxBytes
            ? null
            : "{$label} must not exceed " . round($maxBytes / 1024 / 1024, 1) . " MB.";
    }

    private function ruleFileTypes(mixed $v, string $label, array $types): ?string
    {
        if (!is_array($v) || !isset($v['name']) || $v['error'] === UPLOAD_ERR_NO_FILE) return null;
        $ext = strtolower(pathinfo($v['name'], PATHINFO_EXTENSION));
        return in_array($ext, $types, true) ? null : "{$label} must be one of: " . implode(', ', $types) . '.';
    }
}
