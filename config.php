<?php
/**
 * NexForm Configuration
 * Edit these settings to match your environment.
 */

// -------------------------------------------------------
// MAIL SETTINGS
// -------------------------------------------------------

// Recipient email address(es) – comma separated for multiple
define('NF_MAIL_TO', 'you@example.com');

// From name shown in the email
define('NF_MAIL_FROM_NAME', 'NexForm Contact');

// From address (must be valid on your server)
define('NF_MAIL_FROM_EMAIL', 'noreply@yourdomain.com');

// Subject prefix for incoming emails
define('NF_MAIL_SUBJECT_PREFIX', '[NexForm] ');

// Mail transport: 'mail' or 'smtp'
define('NF_MAIL_TRANSPORT', 'mail');

// SMTP settings (only used when NF_MAIL_TRANSPORT = 'smtp')
define('NF_SMTP_HOST',       'smtp.yourdomain.com');
define('NF_SMTP_PORT',       587);
define('NF_SMTP_ENCRYPTION', 'tls');   // 'tls' or 'ssl'
define('NF_SMTP_USERNAME',   'smtp_user@yourdomain.com');
define('NF_SMTP_PASSWORD',   'smtp_password');

// -------------------------------------------------------
// AUTO-RESPONDER
// -------------------------------------------------------

// Set to true to send an auto-reply to the visitor
define('NF_AUTORESPONDER_ENABLED', true);

// The field name in your form that holds the visitor's email
define('NF_AUTORESPONDER_EMAIL_FIELD', 'email');

// Auto-reply subject
define('NF_AUTORESPONDER_SUBJECT', 'Thank you for contacting us!');

// Auto-reply message (HTML allowed)
define('NF_AUTORESPONDER_MESSAGE', '
<p>Hi there,</p>
<p>Thank you for getting in touch! We have received your message and will reply within 1–2 business days.</p>
<p>Best regards,<br>The Team</p>
');

// -------------------------------------------------------
// DATABASE / SUBMISSION LOGGING
// -------------------------------------------------------

// Set to true to save every submission to the database
define('NF_DB_ENABLED', true);

// Database driver: 'sqlite' or 'mysql'
define('NF_DB_DRIVER', 'sqlite');

// SQLite path (relative to nexform root, writable by server)
define('NF_DB_SQLITE_PATH', __DIR__ . '/data/nexform.db');

// MySQL credentials (used when NF_DB_DRIVER = 'mysql')
define('NF_DB_MYSQL_HOST',   'localhost');
define('NF_DB_MYSQL_PORT',   3306);
define('NF_DB_MYSQL_NAME',   'nexform');
define('NF_DB_MYSQL_USER',   'root');
define('NF_DB_MYSQL_PASS',   '');

// -------------------------------------------------------
// SPAM PROTECTION
// -------------------------------------------------------

// Captcha mode: 'none', 'honeypot', 'recaptcha_v2', 'recaptcha_v3'
define('NF_CAPTCHA_MODE', 'honeypot');

// Google reCAPTCHA keys (required when mode is recaptcha_v2 or recaptcha_v3)
define('NF_RECAPTCHA_SITE_KEY',   'YOUR_SITE_KEY');
define('NF_RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY');

// reCAPTCHA v3 minimum score (0.0 – 1.0; lower = more permissive)
define('NF_RECAPTCHA_V3_SCORE', 0.5);

// Rate limiting: maximum submissions per window per IP
define('NF_RATE_LIMIT_ENABLED', true);
define('NF_RATE_LIMIT_MAX',     5);      // max submissions
define('NF_RATE_LIMIT_WINDOW',  600);    // in seconds (10 minutes)

// -------------------------------------------------------
// FILE UPLOADS
// -------------------------------------------------------

define('NF_UPLOAD_ENABLED',    true);
define('NF_UPLOAD_MAX_SIZE',   5 * 1024 * 1024);  // 5 MB in bytes
define('NF_UPLOAD_ALLOWED',    ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip']);
define('NF_UPLOAD_DIR',        __DIR__ . '/uploads/');
define('NF_UPLOAD_ATTACH_MAIL', true);  // attach file to notification email

// -------------------------------------------------------
// GENERAL
// -------------------------------------------------------

// Timezone
date_default_timezone_set('UTC');

// Admin password for the /admin panel
define('NF_ADMIN_PASSWORD', 'changeme123');

// Debug mode – set to false in production
define('NF_DEBUG', false);
