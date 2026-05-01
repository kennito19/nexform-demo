<?php
namespace NexForm;

/**
 * Filter – sanitises incoming field values before validation or storage.
 *
 * Each filter is a named static method.  Filters can be chained by supplying
 * an ordered list: ['trim', 'strip_tags', 'lowercase']
 */
class Filter
{
    /**
     * Run a list of named filters against a value.
     *
     * @param  mixed  $value    Raw value from $_POST or $_FILES
     * @param  array  $filters  List of filter names
     * @return mixed            Filtered value
     */
    public static function run($value, array $filters): mixed
    {
        foreach ($filters as $filter) {
            $value = match ($filter) {
                'trim'          => is_string($value) ? trim($value) : $value,
                'strip_tags'    => is_string($value) ? strip_tags($value) : $value,
                'strip_slashes' => is_string($value) ? stripslashes($value) : $value,
                'lowercase'     => is_string($value) ? mb_strtolower($value, 'UTF-8') : $value,
                'uppercase'     => is_string($value) ? mb_strtoupper($value, 'UTF-8') : $value,
                'int'           => (int) $value,
                'float'         => (float) $value,
                'bool'          => (bool) $value,
                'email'         => filter_var($value, FILTER_SANITIZE_EMAIL),
                'url'           => filter_var($value, FILTER_SANITIZE_URL),
                'html_entities' => is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value,
                'nl2br'         => is_string($value) ? nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) : $value,
                'alpha'         => is_string($value) ? preg_replace('/[^a-zA-Z]/', '', $value) : $value,
                'alphanumeric'  => is_string($value) ? preg_replace('/[^a-zA-Z0-9]/', '', $value) : $value,
                'numeric'       => is_string($value) ? preg_replace('/[^0-9]/', '', $value) : $value,
                'phone'         => is_string($value) ? preg_replace('/[^0-9\+\-\(\)\s]/', '', $value) : $value,
                default         => $value,
            };
        }
        return $value;
    }

    /**
     * Sanitise an entire associative array of field => value pairs.
     *
     * @param  array $data     ['field' => 'raw value', ...]
     * @param  array $fieldMap ['field' => ['filters' => [...]]]
     * @return array           Filtered data
     */
    public static function filterAll(array $data, array $fieldMap): array
    {
        $filtered = [];
        foreach ($data as $name => $value) {
            $filters = $fieldMap[$name]['filters'] ?? ['trim', 'strip_tags'];
            $filtered[$name] = self::run($value, $filters);
        }
        return $filtered;
    }
}
