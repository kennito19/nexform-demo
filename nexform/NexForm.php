<?php
namespace NexForm;

/**
 * NexForm – main form builder class.
 *
 * Usage example:
 *
 *   $form = new NexForm('contact');
 *   $form->text('name',    'Your Name',    ['rules' => ['required', 'minlen:2']])
 *        ->email('email',  'Email Address', ['rules' => ['required', 'email']])
 *        ->textarea('message', 'Message',  ['rules' => ['required', 'minlen:10']])
 *        ->submit('Send Message');
 *
 *   echo $form->render();
 *
 * Each field method returns $this for fluent chaining.
 */
class NexForm
{
    /** @var array<int, array> Ordered list of field definitions */
    private array $fields = [];

    /** @var string Unique identifier for this form (used for DB & rate limiting) */
    private string $formId;

    /** @var string Form action URL */
    private string $action = '';

    /** @var string Active theme class: 'nf-theme-light' | 'nf-theme-dark' | 'nf-theme-material' | 'nf-theme-glass' */
    private string $theme = 'nf-theme-light';

    /** @var string Submit button label */
    private string $submitLabel = 'Send';

    /** @var string Submit button icon (HTML, e.g. an SVG or font-icon class) */
    private string $submitIcon = '';

    /** @var int|null Total number of steps for multi-step forms (null = single step) */
    private ?int $steps = null;

    /** @var array<string, mixed> Extra attributes for the <form> element */
    private array $formAttrs = [];

    // -------------------------------------------------------
    // Constructor
    // -------------------------------------------------------

    public function __construct(string $formId = 'default')
    {
        $this->formId = $formId;
    }

    // -------------------------------------------------------
    // Configuration helpers (fluent)
    // -------------------------------------------------------

    public function theme(string $theme): static
    {
        $validThemes = ['nf-theme-light', 'nf-theme-dark', 'nf-theme-material', 'nf-theme-glass'];
        $this->theme = in_array($theme, $validThemes, true) ? $theme : 'nf-theme-light';
        return $this;
    }

    public function action(string $url): static
    {
        $this->action = $url;
        return $this;
    }

    public function submit(string $label, string $icon = ''): static
    {
        $this->submitLabel = $label;
        $this->submitIcon  = $icon;
        return $this;
    }

    /** Enable multi-step mode and set total step count. */
    public function steps(int $count): static
    {
        $this->steps = $count;
        return $this;
    }

    public function attr(string $key, string $value): static
    {
        $this->formAttrs[$key] = $value;
        return $this;
    }

    // -------------------------------------------------------
    // Field builders (fluent)
    // -------------------------------------------------------

    /**
     * @param  string $name    Field name (HTML name attribute)
     * @param  string $label   Human-readable label
     * @param  array  $opts    Options: rules, filters, placeholder, class, id,
     *                         help, default, required, step (for multi-step),
     *                         show_if ['field' => 'value'] for conditional logic
     */
    public function text(string $name, string $label, array $opts = []): static
    {
        return $this->addField('text', $name, $label, $opts);
    }

    public function email(string $name, string $label, array $opts = []): static
    {
        return $this->addField('email', $name, $label, $opts);
    }

    public function tel(string $name, string $label, array $opts = []): static
    {
        return $this->addField('tel', $name, $label, $opts);
    }

    public function number(string $name, string $label, array $opts = []): static
    {
        return $this->addField('number', $name, $label, $opts);
    }

    public function url(string $name, string $label, array $opts = []): static
    {
        return $this->addField('url', $name, $label, $opts);
    }

    public function date(string $name, string $label, array $opts = []): static
    {
        return $this->addField('date', $name, $label, $opts);
    }

    public function textarea(string $name, string $label, array $opts = []): static
    {
        $opts['rows'] = $opts['rows'] ?? 5;
        return $this->addField('textarea', $name, $label, $opts);
    }

    public function select(string $name, string $label, array $choices, array $opts = []): static
    {
        $opts['choices'] = $choices;
        return $this->addField('select', $name, $label, $opts);
    }

    public function radio(string $name, string $label, array $choices, array $opts = []): static
    {
        $opts['choices'] = $choices;
        return $this->addField('radio', $name, $label, $opts);
    }

    public function checkbox(string $name, string $label, array $choices, array $opts = []): static
    {
        $opts['choices'] = $choices;
        return $this->addField('checkbox', $name, $label, $opts);
    }

    public function toggle(string $name, string $label, array $opts = []): static
    {
        return $this->addField('toggle', $name, $label, $opts);
    }

    public function rating(string $name, string $label, array $opts = []): static
    {
        $opts['max'] = $opts['max'] ?? 5;
        return $this->addField('rating', $name, $label, $opts);
    }

    public function file(string $name, string $label, array $opts = []): static
    {
        return $this->addField('file', $name, $label, $opts);
    }

    public function hidden(string $name, string $value): static
    {
        return $this->addField('hidden', $name, '', ['default' => $value]);
    }

    /** Add a visual divider / section heading inside the form. */
    public function section(string $title, string $description = ''): static
    {
        $this->fields[] = ['type' => 'section', 'title' => $title, 'description' => $description];
        return $this;
    }

    /** Start a new step (for multi-step forms). */
    public function step(int $number, string $title = ''): static
    {
        $this->fields[] = ['type' => 'step_start', 'number' => $number, 'title' => $title];
        return $this;
    }

    // -------------------------------------------------------
    // Rendering
    // -------------------------------------------------------

    public function render(): string
    {
        $captcha  = new Captcha();
        $attrs    = $this->buildFormAttrs();
        $isMulti  = $this->steps !== null;

        $html  = $captcha->scripts() . "\n";
        $html .= "<form {$attrs}>\n";
        $html .= "  <input type=\"hidden\" name=\"nf_form_id\" value=\"" . htmlspecialchars($this->formId) . "\">\n";
        $html .= "  <input type=\"hidden\" name=\"nf_token\" value=\"" . $this->generateToken() . "\">\n";

        if ($isMulti) {
            $html .= $this->renderProgressBar();
        }

        $html .= "  <div class=\"nf-fields\">\n";

        foreach ($this->fields as $field) {
            $html .= $this->renderField($field);
        }

        // Captcha
        $html .= $captcha->html();

        $html .= "  </div>\n";

        // Navigation buttons
        if ($isMulti) {
            $html .= $this->renderMultiStepNav();
        } else {
            $html .= $this->renderSubmitButton();
        }

        $html .= "  <div class=\"nf-response\" role=\"alert\" aria-live=\"polite\"></div>\n";
        $html .= "</form>\n";

        return $html;
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    private function addField(string $type, string $name, string $label, array $opts): static
    {
        $this->fields[] = array_merge([
            'type'        => $type,
            'name'        => $name,
            'label'       => $label,
            'rules'       => [],
            'filters'     => ['trim', 'strip_tags'],
            'placeholder' => '',
            'class'       => '',
            'id'          => 'nf_' . $name,
            'help'        => '',
            'default'     => '',
            'required'    => in_array('required', $opts['rules'] ?? [], true),
            'step'        => null,
            'show_if'     => null,
        ], $opts);
        return $this;
    }

    private function buildFormAttrs(): string
    {
        $defaults = [
            'method'       => 'post',
            'action'       => $this->action ?: '#',
            'class'        => 'nf-form ' . $this->theme,
            'data-form-id' => $this->formId,
            'novalidate'   => 'novalidate',
            'enctype'      => 'multipart/form-data',
        ];

        if ($this->steps !== null) {
            $defaults['data-steps'] = $this->steps;
        }

        $merged = array_merge($defaults, $this->formAttrs);
        $parts  = [];
        foreach ($merged as $k => $v) {
            if ($v === true) { $parts[] = htmlspecialchars($k); continue; }
            $parts[] = htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
        }
        return implode(' ', $parts);
    }

    private function renderField(array $field): string
    {
        $type = $field['type'];

        if ($type === 'section')    return $this->renderSection($field);
        if ($type === 'step_start') return $this->renderStepStart($field);
        if ($type === 'hidden')     return "  <input type=\"hidden\" name=\"{$field['name']}\" value=\"" . htmlspecialchars($field['default']) . "\">\n";

        $id          = $field['id'] ?? ('nf_' . $field['name']);
        $required    = $field['required'] ? ' required' : '';
        $extraClass  = $field['class'] ? ' ' . $field['class'] : '';
        $showIf      = $field['show_if'] ? ' data-show-if="' . htmlspecialchars(json_encode($field['show_if'])) . '"' : '';
        $stepAttr    = $field['step'] ? ' data-step="' . (int)$field['step'] . '"' : '';

        $wrapClass   = "nf-field-wrap nf-type-{$type}{$extraClass}";
        if ($field['required'])  $wrapClass .= ' nf-required';

        $label = $this->renderLabel($id, $field['label'], $field['required']);
        $input = match ($type) {
            'textarea'  => $this->renderTextarea($field, $id, $required),
            'select'    => $this->renderSelect($field, $id, $required),
            'radio'     => $this->renderChoices('radio', $field),
            'checkbox'  => $this->renderChoices('checkbox', $field),
            'toggle'    => $this->renderToggle($field, $id),
            'rating'    => $this->renderRating($field, $id),
            'file'      => $this->renderFile($field, $id),
            default     => $this->renderInput($field, $id, $required),
        };

        $help  = $field['help'] ? "<p class=\"nf-help\">{$field['help']}</p>" : '';
        $error = "<p class=\"nf-error\" id=\"{$id}-error\" role=\"alert\"></p>";

        return "  <div class=\"{$wrapClass}\"{$showIf}{$stepAttr}>\n{$label}{$input}{$help}{$error}\n  </div>\n";
    }

    private function renderLabel(string $id, string $label, bool $required): string
    {
        $req = $required ? '<span class="nf-required-star" aria-hidden="true">*</span>' : '';
        return "    <label class=\"nf-label\" for=\"{$id}\">{$label}{$req}</label>\n";
    }

    private function renderInput(array $f, string $id, string $required): string
    {
        $ph  = $f['placeholder'] ? " placeholder=\"" . htmlspecialchars($f['placeholder']) . "\"" : '';
        $val = $f['default']     ? " value=\"" . htmlspecialchars($f['default']) . "\""            : '';
        return "    <input type=\"{$f['type']}\" class=\"nf-input\" id=\"{$id}\" name=\"{$f['name']}\"{$ph}{$val}{$required} autocomplete=\"on\">\n";
    }

    private function renderTextarea(array $f, string $id, string $required): string
    {
        $ph   = $f['placeholder'] ? " placeholder=\"" . htmlspecialchars($f['placeholder']) . "\"" : '';
        $rows = (int)($f['rows'] ?? 5);
        $val  = htmlspecialchars($f['default'] ?? '');
        $maxlen = isset($f['maxlen']) ? " maxlength=\"{$f['maxlen']}\" data-char-count=\"true\"" : '';
        $counter = isset($f['maxlen']) ? "    <span class=\"nf-char-count\">0 / {$f['maxlen']}</span>\n" : '';
        return "    <textarea class=\"nf-input nf-textarea\" id=\"{$id}\" name=\"{$f['name']}\" rows=\"{$rows}\"{$ph}{$required}{$maxlen}>{$val}</textarea>\n{$counter}";
    }

    private function renderSelect(array $f, string $id, string $required): string
    {
        $choices = $f['choices'] ?? [];
        $ph      = $f['placeholder'] ?: '-- Select --';
        $opts    = "      <option value=\"\">{$ph}</option>\n";
        foreach ($choices as $val => $text) {
            $sel   = ($f['default'] == $val) ? ' selected' : '';
            $opts .= "      <option value=\"" . htmlspecialchars($val) . "\"{$sel}>" . htmlspecialchars($text) . "</option>\n";
        }
        return "    <select class=\"nf-input nf-select\" id=\"{$id}\" name=\"{$f['name']}\"{$required}>\n{$opts}    </select>\n";
    }

    private function renderChoices(string $type, array $f): string
    {
        $choices = $f['choices'] ?? [];
        $name    = $type === 'checkbox' ? $f['name'] . '[]' : $f['name'];
        $html    = "    <div class=\"nf-choices nf-choices-{$type}\">\n";
        foreach ($choices as $val => $text) {
            $cid = 'nf_' . $f['name'] . '_' . $val;
            $chk = ($f['default'] == $val) ? ' checked' : '';
            $html .= "      <label class=\"nf-choice-label\"><input type=\"{$type}\" class=\"nf-choice-input\" id=\"{$cid}\" name=\"{$name}\" value=\"" . htmlspecialchars($val) . "\"{$chk}> " . htmlspecialchars($text) . "</label>\n";
        }
        $html .= "    </div>\n";
        return $html;
    }

    private function renderToggle(array $f, string $id): string
    {
        $chk = $f['default'] ? ' checked' : '';
        return "    <label class=\"nf-toggle-label\">
      <input type=\"checkbox\" class=\"nf-toggle-input\" id=\"{$id}\" name=\"{$f['name']}\" value=\"1\"{$chk}>
      <span class=\"nf-toggle-track\"><span class=\"nf-toggle-thumb\"></span></span>
    </label>\n";
    }

    private function renderRating(array $f, string $id): string
    {
        $max  = (int)($f['max'] ?? 5);
        $html = "    <div class=\"nf-rating\" data-name=\"{$f['name']}\" data-max=\"{$max}\">\n";
        for ($i = 1; $i <= $max; $i++) {
            $html .= "      <button type=\"button\" class=\"nf-star\" data-value=\"{$i}\" aria-label=\"{$i} star\">&#9733;</button>\n";
        }
        $html .= "      <input type=\"hidden\" name=\"{$f['name']}\" id=\"{$id}\" value=\"\">\n";
        $html .= "    </div>\n";
        return $html;
    }

    private function renderFile(array $f, string $id): string
    {
        $allowed = implode(',', array_map(fn($e) => '.' . $e, NF_UPLOAD_ALLOWED));
        return "    <div class=\"nf-file-drop\" data-for=\"{$id}\">
      <input type=\"file\" class=\"nf-file-input\" id=\"{$id}\" name=\"{$f['name']}\" accept=\"{$allowed}\">
      <div class=\"nf-file-ui\">
        <svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\"><path d=\"M12 16V8m0 0-3 3m3-3 3 3\"/><path d=\"M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3\"/></svg>
        <p class=\"nf-file-label\">Drag &amp; drop file here, or <span>browse</span></p>
        <p class=\"nf-file-hint\">Max " . round(NF_UPLOAD_MAX_SIZE / 1048576, 0) . " MB &bull; " . implode(', ', NF_UPLOAD_ALLOWED) . "</p>
      </div>
      <div class=\"nf-file-preview\"></div>
    </div>\n";
    }

    private function renderSection(array $f): string
    {
        $desc = $f['description'] ? "<p class=\"nf-section-desc\">" . htmlspecialchars($f['description']) . "</p>" : '';
        return "  <div class=\"nf-section\"><h3 class=\"nf-section-title\">" . htmlspecialchars($f['title']) . "</h3>{$desc}</div>\n";
    }

    private function renderStepStart(array $f): string
    {
        return "  <!-- Step {$f['number']}: {$f['title']} -->\n  <div class=\"nf-step\" data-step=\"{$f['number']}\">\n    <h4 class=\"nf-step-title\">" . htmlspecialchars($f['title']) . "</h4>\n";
    }

    private function renderProgressBar(): string
    {
        $steps = (int)$this->steps;
        $dots  = '';
        for ($i = 1; $i <= $steps; $i++) {
            $dots .= "    <span class=\"nf-progress-dot\" data-step=\"{$i}\">{$i}</span>\n";
        }
        return "  <div class=\"nf-progress\" data-total-steps=\"{$steps}\">\n    <div class=\"nf-progress-track\"><div class=\"nf-progress-fill\"></div></div>\n    <div class=\"nf-progress-dots\">\n{$dots}    </div>\n  </div>\n";
    }

    private function renderMultiStepNav(): string
    {
        return "  <div class=\"nf-step-nav\">\n    <button type=\"button\" class=\"nf-btn nf-btn-prev\" style=\"display:none\">
      <svg viewBox=\"0 0 20 20\" fill=\"currentColor\"><path d=\"M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z\"/></svg>
      Back
    </button>\n    <button type=\"button\" class=\"nf-btn nf-btn-next\">Next
      <svg viewBox=\"0 0 20 20\" fill=\"currentColor\"><path d=\"M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z\"/></svg>
    </button>\n{$this->renderSubmitButton(true)}\n  </div>\n";
    }

    private function renderSubmitButton(bool $hidden = false): string
    {
        $icon   = $this->submitIcon ? "<span class=\"nf-btn-icon\">{$this->submitIcon}</span>" : '';
        $style  = $hidden ? ' style="display:none"' : '';
        $class  = $hidden ? 'nf-btn nf-btn-submit nf-btn-final' : 'nf-btn nf-btn-submit';
        return "  <div class=\"nf-submit-wrap\"{$style}>\n    <button type=\"submit\" class=\"{$class}\">\n      <span class=\"nf-btn-label\">{$icon}" . htmlspecialchars($this->submitLabel) . "</span>\n      <span class=\"nf-btn-spinner\" aria-hidden=\"true\"></span>\n    </button>\n  </div>\n";
    }

    private function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $token = bin2hex(random_bytes(16));
        $_SESSION['nf_token_' . $this->formId] = $token;
        return $token;
    }

    // -------------------------------------------------------
    // Static utility: get field map for validation from fields array
    // -------------------------------------------------------

    public function getFieldMap(): array
    {
        $map = [];
        foreach ($this->fields as $f) {
            if (!isset($f['name']) || in_array($f['type'], ['section', 'step_start', 'hidden'])) continue;
            $map[$f['name']] = [
                'rules'   => $f['rules']   ?? [],
                'filters' => $f['filters'] ?? ['trim', 'strip_tags'],
                'label'   => $f['label']   ?? $f['name'],
            ];
        }
        return $map;
    }

    public function getFormId(): string
    {
        return $this->formId;
    }
}
