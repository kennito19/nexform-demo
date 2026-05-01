<?php
/**
 * NexForm – AJAX form processor.
 *
 * All forms POST here.  The form builder PHP file is loaded based on
 * the nf_form_id field so each form can define its own field map.
 *
 * This file should be the action URL of every NexForm:
 *   $form->action('process.php');
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/nexform/autoload.php';

use NexForm\Captcha;
use NexForm\Database;
use NexForm\FileHandler;
use NexForm\Filter;
use NexForm\Mailer;
use NexForm\RateLimiter;
use NexForm\Validator;

// -------------------------------------------------------
// Bootstrap
// -------------------------------------------------------

header('Content-Type: application/json; charset=UTF-8');
function jsonResponse(bool $success, string $message, array $errors = [], int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'errors' => $errors]);
    exit;
}

// -------------------------------------------------------
// Basic request validation
// -------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$formId = Filter::run($_POST['nf_form_id'] ?? 'default', ['trim', 'alphanumeric']);
if (empty($formId)) {
    jsonResponse(false, 'Invalid form identifier.', [], 400);
}

// -------------------------------------------------------
// CSRF token check (stateless HMAC — no sessions required)
// -------------------------------------------------------

$submittedToken = $_POST['nf_token'] ?? '';

if (empty($submittedToken)) {
    jsonResponse(false, 'Security token missing. Please refresh the page and try again.', [], 403);
}

// Accept tokens from the current hour and the previous hour (handles hour-boundary edge cases)
$bucket       = (int) floor(time() / 3600);
$validCurrent = hash_hmac('sha256', $formId . '|' . $bucket,       NF_SECRET_KEY);
$validPrev    = hash_hmac('sha256', $formId . '|' . ($bucket - 1), NF_SECRET_KEY);

if (!hash_equals($validCurrent, $submittedToken) && !hash_equals($validPrev, $submittedToken)) {
    jsonResponse(false, 'Security token mismatch. Please refresh the page and try again.', [], 403);
}

// -------------------------------------------------------
// Rate limiting
// -------------------------------------------------------

$rateLimiter = new RateLimiter();
$ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (!$rateLimiter->check($ip, $formId)) {
    $retry = $rateLimiter->retryAfter($ip, $formId);
    jsonResponse(false, "Too many submissions. Please wait {$retry} seconds before trying again.", [], 429);
}

// -------------------------------------------------------
// Captcha verification
// -------------------------------------------------------

$captcha = new Captcha();
if (!$captcha->verify($_POST)) {
    jsonResponse(false, 'Captcha verification failed. Please try again.', [], 400);
}

// -------------------------------------------------------
// Load form-specific field map
// -------------------------------------------------------
// Each form example file exposes a $fieldMap variable.
// This allows individual forms to define their own rules and filters.

$formFile = __DIR__ . '/examples/forms/' . $formId . '.php';
if (!file_exists($formFile)) {
    // Fallback: build a generic map from POST keys (all treated as text)
    $fieldMap = [];
    $skip     = ['nf_form_id', 'nf_token', 'nf_website', 'g-recaptcha-response', 'nf_recaptcha_token'];
    foreach ($_POST as $key => $value) {
        if (in_array($key, $skip, true)) continue;
        $fieldMap[$key] = ['rules' => [], 'filters' => ['trim', 'strip_tags'], 'label' => ucfirst($key)];
    }
} else {
    require $formFile; // must set $fieldMap
}

// -------------------------------------------------------
// Filter & validate
// -------------------------------------------------------

// Collect raw data (exclude internal fields)
$skip    = ['nf_form_id', 'nf_token', 'nf_website', 'g-recaptcha-response', 'nf_recaptcha_token'];
$rawData = [];
foreach ($_POST as $key => $val) {
    if (!in_array($key, $skip, true)) {
        $rawData[$key] = is_array($val) ? array_map('strval', $val) : (string)$val;
    }
}

// Include file upload in data for validation
$fileField = null;
foreach ($_FILES as $key => $file) {
    if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        $rawData[$key] = $file;
        $fileField     = $key;
    }
}

$filtered  = Filter::filterAll($rawData, $fieldMap);
$validator = new Validator();

if (!$validator->validate($filtered, $fieldMap)) {
    jsonResponse(false, 'Please correct the errors below.', $validator->getErrors(), 422);
}

// -------------------------------------------------------
// Handle file upload
// -------------------------------------------------------

$uploadedFile = null;

if ($fileField && NF_UPLOAD_ENABLED && isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
    try {
        $handler      = new FileHandler();
        $uploadedFile = $handler->handle($_FILES[$fileField]);
        // Store just the filename in filtered data (not full path)
        $filtered[$fileField] = $uploadedFile['name'] . ' (' . round($uploadedFile['size'] / 1024, 1) . ' KB)';
    } catch (\RuntimeException $e) {
        jsonResponse(false, $e->getMessage(), [], 400);
    }
}

// -------------------------------------------------------
// Save to database
// -------------------------------------------------------

if (NF_DB_ENABLED) {
    try {
        $db = new Database();
        $db->save($filtered, $formId);
    } catch (\Exception $e) {
        if (NF_DEBUG) {
            jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
        }
        // Non-fatal – continue sending email
    }
}

// -------------------------------------------------------
// Send emails
// -------------------------------------------------------

$mailer = new Mailer();

try {
    $mailer->sendNotification($filtered, $uploadedFile, $formId);
} catch (\Exception $e) {
    if (NF_DEBUG) {
        jsonResponse(false, 'Mail error: ' . $e->getMessage(), [], 500);
    }
}

// Auto-responder
$emailField = NF_AUTORESPONDER_EMAIL_FIELD;
if (NF_AUTORESPONDER_ENABLED && !empty($filtered[$emailField])) {
    try {
        $visitorName = $filtered['name'] ?? $filtered['first_name'] ?? '';
        $mailer->sendAutoResponder($filtered[$emailField], $visitorName);
    } catch (\Exception) {
        // Non-fatal
    }
}

// -------------------------------------------------------
// Success
// -------------------------------------------------------

jsonResponse(true, 'Thank you! Your message has been sent. We will be in touch soon.');
