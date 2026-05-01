<?php
/**
 * NexForm – CSV export endpoint.
 * Accessible only to authenticated admin users.
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/nexform/autoload.php';

use NexForm\Database;

if (empty($_SESSION['nf_admin'])) {
    http_response_code(403);
    exit('Forbidden');
}

$formId = $_GET['form_id'] ?? '';
$db     = new Database();
$rows   = $db->exportAll($formId);

if (empty($rows)) {
    header('Location: index.php?empty=1');
    exit;
}

// Collect all unique column names
$columns = [];
foreach ($rows as $row) {
    foreach (array_keys($row) as $col) {
        $columns[$col] = true;
    }
}
$columns = array_keys($columns);

$filename = 'nexform-export-' . ($formId ?: 'all') . '-' . date('Ymd-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');

// BOM for Excel UTF-8 compatibility
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, $columns);

// Data rows
foreach ($rows as $row) {
    $line = [];
    foreach ($columns as $col) {
        $val = $row[$col] ?? '';
        $line[] = is_array($val) ? implode('; ', $val) : (string)$val;
    }
    fputcsv($out, $line);
}

fclose($out);
