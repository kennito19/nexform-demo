<?php
/**
 * NexForm simple autoloader.
 * Automatically loads class files from the nexform/ directory.
 * Session is started here — before any HTML output — so CSRF tokens work.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'NexForm\\')) return;
    $file = __DIR__ . '/' . str_replace('NexForm\\', '', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
