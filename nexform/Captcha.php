<?php
namespace NexForm;

/**
 * Captcha – handles Honeypot, reCAPTCHA v2 and reCAPTCHA v3 verification.
 */
class Captcha
{
    private const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Verify the captcha based on the configured mode.
     *
     * @param  array $post  $_POST data
     * @return bool
     */
    public function verify(array $post): bool
    {
        return match (NF_CAPTCHA_MODE) {
            'honeypot'      => $this->verifyHoneypot($post),
            'recaptcha_v2'  => $this->verifyRecaptchaV2($post),
            'recaptcha_v3'  => $this->verifyRecaptchaV3($post),
            default         => true,
        };
    }

    /**
     * Return the captcha HTML snippet to embed in the form.
     * Called from NexForm::render().
     */
    public function html(): string
    {
        return match (NF_CAPTCHA_MODE) {
            'honeypot'     => $this->honeypotHtml(),
            'recaptcha_v2' => $this->recaptchaV2Html(),
            'recaptcha_v3' => $this->recaptchaV3Html(),
            default        => '',
        };
    }

    /**
     * Extra <script> tags needed in <head> or before </body>.
     */
    public function scripts(): string
    {
        return match (NF_CAPTCHA_MODE) {
            'recaptcha_v2' => '<script src="https://www.google.com/recaptcha/api.js" async defer></script>',
            'recaptcha_v3' => '<script src="https://www.google.com/recaptcha/api.js?render=' . NF_RECAPTCHA_SITE_KEY . '"></script>',
            default        => '',
        };
    }

    // -------------------------------------------------------
    // Honeypot
    // -------------------------------------------------------

    private function honeypotHtml(): string
    {
        // Hidden field that bots fill in – humans leave it blank
        return '<div class="nf-honeypot" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;">
            <label for="nf_website">Leave this field empty</label>
            <input type="text" id="nf_website" name="nf_website" tabindex="-1" autocomplete="off" value="">
        </div>';
    }

    private function verifyHoneypot(array $post): bool
    {
        return empty($post['nf_website']);
    }

    // -------------------------------------------------------
    // reCAPTCHA v2
    // -------------------------------------------------------

    private function recaptchaV2Html(): string
    {
        return '<div class="nf-field-wrap nf-captcha-wrap">
            <div class="g-recaptcha" data-sitekey="' . htmlspecialchars(NF_RECAPTCHA_SITE_KEY) . '"></div>
        </div>';
    }

    private function verifyRecaptchaV2(array $post): bool
    {
        $token = $post['g-recaptcha-response'] ?? '';
        if (empty($token)) return false;
        return $this->callVerifyApi($token, null)['success'] ?? false;
    }

    // -------------------------------------------------------
    // reCAPTCHA v3
    // -------------------------------------------------------

    private function recaptchaV3Html(): string
    {
        return '<input type="hidden" name="nf_recaptcha_token" id="nfRecaptchaToken">
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            grecaptcha.ready(function () {
                grecaptcha.execute("' . htmlspecialchars(NF_RECAPTCHA_SITE_KEY) . '", {action: "nexform"})
                    .then(function (token) {
                        document.getElementById("nfRecaptchaToken").value = token;
                    });
            });
        });
        </script>';
    }

    private function verifyRecaptchaV3(array $post): bool
    {
        $token = $post['nf_recaptcha_token'] ?? '';
        if (empty($token)) return false;
        $result = $this->callVerifyApi($token, 'nexform');
        return ($result['success'] ?? false) && (($result['score'] ?? 0) >= NF_RECAPTCHA_V3_SCORE);
    }

    // -------------------------------------------------------
    // Shared API call
    // -------------------------------------------------------

    private function callVerifyApi(string $token, ?string $action): array
    {
        $payload = http_build_query([
            'secret'   => NF_RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);

        $raw = @file_get_contents(self::RECAPTCHA_VERIFY_URL, false, $ctx);
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }
}
