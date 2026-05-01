/**
 * NexForm – Client-side JS
 *
 * Features:
 *  - Ajax form submission (no page reload)
 *  - Client-side validation with inline error messages
 *  - Multi-step wizard with progress bar
 *  - Conditional field logic (data-show-if)
 *  - Drag & drop file upload with preview
 *  - Star rating widget
 *  - Character counter for textarea
 *  - Smooth scroll to first error
 *  - Works progressively: if JS is disabled the form still submits normally
 */

(function (root, factory) {
    if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.NexForm = factory();
    }
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {

    'use strict';

    // =====================================================================
    // Utility helpers
    // =====================================================================

    const qs   = (sel, ctx = document) => ctx.querySelector(sel);
    const qsa  = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
    const on    = (el, ev, fn) => el && el.addEventListener(ev, fn);
    const off   = (el, ev, fn) => el && el.removeEventListener(ev, fn);
    const cls   = (el, ...names) => el && el.classList;
    const fmt   = (bytes) => bytes < 1048576 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1048576).toFixed(1) + ' MB';

    function closest(el, sel) {
        while (el && el !== document) {
            if (el.matches(sel)) return el;
            el = el.parentElement;
        }
        return null;
    }

    // =====================================================================
    // NexForm class
    // =====================================================================

    class NexForm {

        /**
         * @param {HTMLFormElement|string} formOrSelector
         * @param {object} options
         */
        constructor(formOrSelector, options = {}) {
            this.form = typeof formOrSelector === 'string'
                ? qs(formOrSelector) : formOrSelector;

            if (!this.form) return;

            this.options = Object.assign({
                scrollOffset:    80,      // px above first error when scrolling
                successCallback: null,    // fn(response) called on success
                errorCallback:   null,    // fn(response) called on error
                beforeSubmit:    null,    // fn(formData) – return false to abort
                resetOnSuccess:  true,
            }, options);

            this._currentStep  = 1;
            this._totalSteps   = parseInt(this.form.dataset.steps || '0', 10);
            this._isMultiStep  = this._totalSteps > 1;

            this._init();
        }

        // -----------------------------------------------------------------
        // Initialise all widgets
        // -----------------------------------------------------------------

        _init() {
            this._initSubmit();
            this._initCharCounters();
            this._initFileDrops();
            this._initRatings();
            this._initConditionals();
            if (this._isMultiStep) this._initSteps();
        }

        // -----------------------------------------------------------------
        // Ajax Submit
        // -----------------------------------------------------------------

        _initSubmit() {
            on(this.form, 'submit', (e) => {
                e.preventDefault();
                this._submit();
            });
        }

        async _submit() {
            this._clearErrors();

            // Client-side validation
            if (!this._validateCurrentStep()) {
                this._scrollToFirstError();
                return;
            }

            const submitBtn = qs('.nf-btn-submit', this.form);
            const response  = qs('.nf-response', this.form);

            if (submitBtn) submitBtn.classList.add('loading');

            const formData = new FormData(this.form);

            if (this.options.beforeSubmit && this.options.beforeSubmit(formData) === false) {
                if (submitBtn) submitBtn.classList.remove('loading');
                return;
            }

            try {
                const res  = await fetch(this.form.action || window.location.href, {
                    method:      'POST',
                    body:        formData,
                    headers:     { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (data.success) {
                    this._showResponse(response, data.message, true);
                    if (this.options.resetOnSuccess) this.form.reset();
                    this._resetFilePreviews();
                    this._resetRatings();
                    if (this.options.successCallback) this.options.successCallback(data);
                } else {
                    if (data.errors && Object.keys(data.errors).length) {
                        this._showFieldErrors(data.errors);
                        this._scrollToFirstError();
                    } else {
                        this._showResponse(response, data.message || 'An error occurred.', false);
                    }
                    if (this.options.errorCallback) this.options.errorCallback(data);
                }

            } catch (err) {
                this._showResponse(response, 'Network error. Please check your connection and try again.', false);
            } finally {
                if (submitBtn) submitBtn.classList.remove('loading');
            }
        }

        // -----------------------------------------------------------------
        // Validation (client-side)
        // -----------------------------------------------------------------

        _validateCurrentStep() {
            let valid = true;
            const step = this._isMultiStep ? this._currentStep : null;

            qsa('[required]', this.form).forEach(el => {
                if (step !== null) {
                    const wrap = closest(el, '.nf-field-wrap');
                    if (!wrap) return;
                    const fieldStep = parseInt(wrap.dataset.step || '0', 10);
                    if (fieldStep && fieldStep !== step) return;
                }

                const wrap    = closest(el, '.nf-field-wrap');
                const errorEl = wrap ? qs('.nf-error', wrap) : null;

                if (!this._isFieldValid(el)) {
                    el.classList.add('nf-has-error');
                    if (errorEl) errorEl.textContent = this._getErrorMessage(el);
                    valid = false;
                } else {
                    el.classList.remove('nf-has-error');
                    if (errorEl) errorEl.textContent = '';
                }
            });

            return valid;
        }

        _isFieldValid(el) {
            const type = el.type;
            if (type === 'checkbox' && el.closest('.nf-choices')) {
                // Group validation – at least one checked
                const name    = el.name.replace('[]', '');
                const group   = qsa(`[name="${el.name}"], [name="${name}[]"]`, this.form);
                return group.some(cb => cb.checked);
            }
            if (type === 'radio') {
                const group = qsa(`[name="${el.name}"]`, this.form);
                return group.some(r => r.checked);
            }
            if (type === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim());
            if (type === 'tel')   return el.value.trim().length > 0;
            return el.value.trim().length > 0;
        }

        _getErrorMessage(el) {
            const type  = el.type;
            const label = closest(el, '.nf-field-wrap')?.querySelector('.nf-label')?.textContent.replace('*', '').trim() || 'This field';
            if (type === 'email') return `${label} must be a valid email address.`;
            return `${label} is required.`;
        }

        _clearErrors() {
            qsa('.nf-has-error', this.form).forEach(el => el.classList.remove('nf-has-error'));
            qsa('.nf-error',     this.form).forEach(el => el.textContent = '');
            const resp = qs('.nf-response', this.form);
            if (resp) { resp.className = 'nf-response'; resp.textContent = ''; }
        }

        _showFieldErrors(errors) {
            Object.entries(errors).forEach(([name, messages]) => {
                const msg  = Array.isArray(messages) ? messages[0] : messages;
                const el   = qs(`[name="${name}"], [name="${name}[]"]`, this.form);
                if (!el) return;
                el.classList.add('nf-has-error');
                const wrap = closest(el, '.nf-field-wrap');
                if (wrap) {
                    const errEl = qs('.nf-error', wrap);
                    if (errEl) errEl.textContent = msg;
                }
            });
        }

        _showResponse(el, message, success) {
            if (!el) return;
            el.textContent  = message;
            el.className    = 'nf-response ' + (success ? 'nf-success' : 'nf-error-msg');
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        _scrollToFirstError() {
            const first = qs('.nf-has-error', this.form);
            if (!first) return;
            const top = first.getBoundingClientRect().top + window.scrollY - this.options.scrollOffset;
            window.scrollTo({ top, behavior: 'smooth' });
            first.focus?.();
        }

        // -----------------------------------------------------------------
        // Character counter
        // -----------------------------------------------------------------

        _initCharCounters() {
            qsa('[data-char-count]', this.form).forEach(ta => {
                const max     = parseInt(ta.maxLength, 10);
                const counter = ta.nextElementSibling;
                if (!counter || !counter.classList.contains('nf-char-count')) return;

                const update = () => {
                    const len = ta.value.length;
                    counter.textContent = `${len} / ${max}`;
                    counter.style.color = len > max * .9 ? '#ef4444' : '';
                };

                on(ta, 'input', update);
                update();
            });
        }

        // -----------------------------------------------------------------
        // File drag & drop
        // -----------------------------------------------------------------

        _initFileDrops() {
            qsa('.nf-file-drop', this.form).forEach(drop => {
                const input   = qs('.nf-file-input', drop);
                const preview = qs('.nf-file-preview', drop);

                if (!input || !preview) return;

                on(drop, 'dragover',  (e) => { e.preventDefault(); drop.classList.add('drag-over'); });
                on(drop, 'dragleave', ()  => drop.classList.remove('drag-over'));
                on(drop, 'drop',      (e) => {
                    e.preventDefault();
                    drop.classList.remove('drag-over');
                    if (e.dataTransfer.files.length) {
                        const dt = new DataTransfer();
                        dt.items.add(e.dataTransfer.files[0]);
                        input.files = dt.files;
                        this._showFilePreview(preview, e.dataTransfer.files[0], input);
                    }
                });

                on(input, 'change', (e) => {
                    if (e.target.files.length) {
                        this._showFilePreview(preview, e.target.files[0], input);
                    }
                });
            });
        }

        _showFilePreview(preview, file, input) {
            preview.innerHTML = `
                <span class="nf-file-preview-name" title="${file.name}">${file.name}</span>
                <span class="nf-file-preview-size">${fmt(file.size)}</span>
                <button type="button" class="nf-file-remove" title="Remove file">&times;</button>`;
            preview.classList.add('visible');

            on(qs('.nf-file-remove', preview), 'click', () => {
                input.value = '';
                preview.innerHTML = '';
                preview.classList.remove('visible');
            });
        }

        _resetFilePreviews() {
            qsa('.nf-file-preview', this.form).forEach(p => {
                p.innerHTML = '';
                p.classList.remove('visible');
            });
        }

        // -----------------------------------------------------------------
        // Star rating
        // -----------------------------------------------------------------

        _initRatings() {
            qsa('.nf-rating', this.form).forEach(widget => {
                const stars  = qsa('.nf-star', widget);
                const hidden = qs('input[type=hidden]', widget);
                let   value  = 0;

                stars.forEach((star, i) => {
                    on(star, 'mouseenter', () => this._highlightStars(stars, i));
                    on(star, 'mouseleave', () => this._highlightStars(stars, value - 1));
                    on(star, 'click', () => {
                        value        = i + 1;
                        if (hidden)  hidden.value = value;
                        this._highlightStars(stars, i);
                        stars.forEach(s => s.classList.remove('hovered'));
                    });
                });

                on(widget, 'mouseleave', () => this._highlightStars(stars, value - 1));
            });
        }

        _highlightStars(stars, upTo) {
            stars.forEach((s, i) => {
                s.classList.toggle('hovered', i <= upTo);
            });
        }

        _resetRatings() {
            qsa('.nf-rating', this.form).forEach(widget => {
                const stars  = qsa('.nf-star', widget);
                const hidden = qs('input[type=hidden]', widget);
                if (hidden) hidden.value = '';
                stars.forEach(s => { s.classList.remove('active', 'hovered'); });
            });
        }

        // -----------------------------------------------------------------
        // Conditional fields (data-show-if='{"fieldName": "value"}')
        // -----------------------------------------------------------------

        _initConditionals() {
            const conditionals = qsa('[data-show-if]', this.form);
            if (!conditionals.length) return;

            const evaluate = () => {
                conditionals.forEach(wrap => {
                    let conditions;
                    try { conditions = JSON.parse(wrap.dataset.showIf); } catch { return; }

                    const visible = Object.entries(conditions).every(([name, expected]) => {
                        const el = qs(`[name="${name}"]`, this.form);
                        if (!el) return false;
                        if (el.type === 'checkbox') return el.checked.toString() === expected;
                        return el.value === expected;
                    });

                    wrap.style.display = visible ? '' : 'none';
                    qsa('[required]', wrap).forEach(el => {
                        el.dataset.wasRequired = el.dataset.wasRequired ?? (el.required ? '1' : '0');
                        el.required = visible && el.dataset.wasRequired === '1';
                    });
                });
            };

            // Listen to all input/change events on the form
            on(this.form, 'input',  evaluate);
            on(this.form, 'change', evaluate);
            evaluate(); // initial pass
        }

        // -----------------------------------------------------------------
        // Multi-step wizard
        // -----------------------------------------------------------------

        _initSteps() {
            this._steps = qsa('.nf-step', this.form);

            const prevBtn = qs('.nf-btn-prev', this.form);
            const nextBtn = qs('.nf-btn-next', this.form);
            const finalWrap = qs('.nf-submit-wrap', this.form);

            on(nextBtn, 'click', () => {
                if (!this._validateCurrentStep()) { this._scrollToFirstError(); return; }
                if (this._currentStep < this._totalSteps) {
                    this._goToStep(this._currentStep + 1);
                }
            });

            on(prevBtn, 'click', () => {
                if (this._currentStep > 1) this._goToStep(this._currentStep - 1);
            });

            this._goToStep(1);
        }

        _goToStep(step) {
            this._currentStep = step;
            const total = this._totalSteps;

            // Show/hide steps
            qsa('.nf-step', this.form).forEach(s => {
                const n = parseInt(s.dataset.step || '0', 10);
                s.classList.toggle('active', n === step);
            });

            // Also show fields with data-step attribute
            qsa('[data-step]', this.form).forEach(wrap => {
                const n = parseInt(wrap.dataset.step || '0', 10);
                if (n) wrap.style.display = n === step ? '' : 'none';
            });

            // Progress
            const fill = qs('.nf-progress-fill', this.form);
            if (fill) fill.style.width = ((step - 1) / (total - 1) * 100) + '%';

            qsa('.nf-progress-dot', this.form).forEach(dot => {
                const n = parseInt(dot.dataset.step || '0', 10);
                dot.classList.toggle('done',    n < step);
                dot.classList.toggle('current', n === step);
            });

            // Nav buttons
            const prevBtn   = qs('.nf-btn-prev',  this.form);
            const nextBtn   = qs('.nf-btn-next',  this.form);
            const finalWrap = qs('.nf-submit-wrap', this.form);

            if (prevBtn)   prevBtn.style.display   = step > 1       ? '' : 'none';
            if (nextBtn)   nextBtn.style.display   = step < total   ? '' : 'none';
            if (finalWrap) finalWrap.style.display = step === total ? '' : 'none';
        }

        // -----------------------------------------------------------------
        // Public API
        // -----------------------------------------------------------------

        /** Programmatically submit the form. */
        submit() { this._submit(); }

        /** Reset validation state and response. */
        reset() { this._clearErrors(); this.form.reset(); this._resetFilePreviews(); this._resetRatings(); }

        /** Destroy: remove event listeners (partial – add more as needed). */
        destroy() { this.form = null; }
    }

    // =====================================================================
    // Auto-init forms with data-nexform attribute
    // =====================================================================

    function autoInit() {
        qsa('[data-nexform]').forEach(form => {
            const opts = {};
            try { Object.assign(opts, JSON.parse(form.dataset.nexformOpts || '{}')); } catch {}
            new NexForm(form, opts);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

    return NexForm;

}));
