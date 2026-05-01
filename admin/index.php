<?php
/**
 * NexForm Admin Panel – view and manage submissions.
 * Password protected via NF_ADMIN_PASSWORD in config.php.
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/nexform/autoload.php';

use NexForm\Database;

// ----- Auth -----
$error = '';
if (isset($_POST['nf_admin_login'])) {
    if ($_POST['password'] === NF_ADMIN_PASSWORD) {
        $_SESSION['nf_admin'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $error = 'Incorrect password.';
}

if (isset($_GET['logout'])) {
    unset($_SESSION['nf_admin']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$authed = !empty($_SESSION['nf_admin']);

// ----- Actions (require auth) -----
if ($authed && isset($_POST['delete_id'])) {
    $db = new Database();
    $db->delete((int)$_POST['delete_id']);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?deleted=1');
    exit;
}

// ----- Data -----
$rows  = [];
$total = 0;
$page  = max(1, (int)($_GET['p'] ?? 1));

if ($authed) {
    $db     = new Database();
    $formId = $_GET['form_id'] ?? '';
    $result = $db->getAll($page, 15, $formId);
    $rows   = $result['rows'];
    $total  = $result['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexForm Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; }
        .nf-admin-header { background: #4f46e5; color: #fff; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
        .nf-admin-header h1 { font-size: 1.2rem; font-weight: 700; letter-spacing: -.5px; }
        .nf-admin-header a { color: #c7d2fe; font-size: .85rem; text-decoration: none; }
        .nf-admin-wrap { max-width: 1100px; margin: 32px auto; padding: 0 16px; }
        .nf-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 24px; margin-bottom: 24px; }
        .nf-form-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .nf-form-row label { font-size: .85rem; font-weight: 600; margin-bottom: 4px; display: block; color: #475569; }
        .nf-form-row input[type=password], .nf-form-row input[type=text], .nf-form-row select { border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px 14px; font-size: .9rem; outline: none; transition: border-color .2s; width: 100%; }
        .nf-form-row input:focus, .nf-form-row select:focus { border-color: #4f46e5; }
        .nf-btn { background: #4f46e5; color: #fff; border: none; border-radius: 8px; padding: 10px 20px; cursor: pointer; font-size: .9rem; font-weight: 600; transition: background .2s; }
        .nf-btn:hover { background: #4338ca; }
        .nf-btn-danger { background: #ef4444; }
        .nf-btn-danger:hover { background: #dc2626; }
        .nf-btn-sm { padding: 5px 12px; font-size: .8rem; border-radius: 6px; }
        .nf-btn-outline { background: transparent; border: 1px solid #4f46e5; color: #4f46e5; }
        .nf-btn-outline:hover { background: #4f46e5; color: #fff; }
        .nf-error { color: #ef4444; font-size: .85rem; margin-top: 8px; }
        .nf-success { color: #16a34a; font-size: .85rem; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        th { text-align: left; padding: 10px 12px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 700; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        tr:hover td { background: #f8fafc; }
        .nf-field-list { list-style: none; }
        .nf-field-list li { margin-bottom: 3px; }
        .nf-field-key { font-weight: 600; color: #475569; }
        .nf-pagination { display: flex; gap: 8px; margin-top: 20px; }
        .nf-page-btn { padding: 7px 14px; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; font-size: .85rem; background: #fff; }
        .nf-page-btn.active { background: #4f46e5; color: #fff; border-color: #4f46e5; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: .75rem; font-weight: 700; background: #e0e7ff; color: #4f46e5; }
    </style>
</head>
<body>
<header class="nf-admin-header">
    <h1>NexForm &mdash; Admin Panel</h1>
    <?php if ($authed): ?>
        <a href="?logout=1">Log out</a>
    <?php endif; ?>
</header>

<div class="nf-admin-wrap">
<?php if (!$authed): ?>
    <div class="nf-card" style="max-width:400px;margin:60px auto">
        <h2 style="margin-bottom:20px;font-size:1.1rem">Admin Login</h2>
        <?php if ($error): ?><p class="nf-error"><?= htmlspecialchars($error) ?></p><br><?php endif; ?>
        <form method="post">
            <div class="nf-form-row" style="flex-direction:column">
                <div><label>Password</label><input type="password" name="password" autofocus required></div>
                <div><button type="submit" name="nf_admin_login" class="nf-btn">Login</button></div>
            </div>
        </form>
    </div>
<?php else: ?>

    <?php if (isset($_GET['deleted'])): ?><p class="nf-success" style="margin-bottom:16px">Submission deleted.</p><?php endif; ?>

    <!-- Toolbar -->
    <div class="nf-card" style="padding:16px 24px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <form method="get" style="display:flex;gap:10px;align-items:center">
                <label style="font-weight:600;color:#475569;font-size:.85rem">Form:</label>
                <select name="form_id" onchange="this.form.submit()" style="border:1px solid #cbd5e1;border-radius:8px;padding:7px 12px;font-size:.85rem">
                    <option value="">All Forms</option>
                    <?php foreach (['contact','quote','registration'] as $fid): ?>
                        <option value="<?= $fid ?>" <?= ($formId ?? '') === $fid ? 'selected' : '' ?>><?= ucfirst($fid) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div style="display:flex;gap:8px">
                <a href="export.php?form_id=<?= urlencode($formId ?? '') ?>" class="nf-btn nf-btn-outline nf-btn-sm">Export CSV</a>
                <span class="badge"><?= $total ?> submission<?= $total !== 1 ? 's' : '' ?></span>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="nf-card" style="padding:0;overflow:hidden">
        <?php if (empty($rows)): ?>
            <p style="padding:32px;text-align:center;color:#94a3b8">No submissions yet.</p>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Form</th>
                    <th>Fields</th>
                    <th>IP</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><span class="badge"><?= htmlspecialchars($row['form_id']) ?></span></td>
                    <td>
                        <ul class="nf-field-list">
                        <?php foreach ((array)$row['fields'] as $k => $v): ?>
                            <li><span class="nf-field-key"><?= htmlspecialchars(ucfirst(str_replace(['_','-'],' ',$k))) ?>:</span>
                            <?= htmlspecialchars(is_array($v) ? implode(', ', $v) : (string)$v) ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </td>
                    <td><?= htmlspecialchars($row['ip_address']) ?></td>
                    <td style="white-space:nowrap"><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete this submission?')">
                            <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                            <button type="submit" class="nf-btn nf-btn-danger nf-btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <?php
            $totalPages = (int)ceil($total / 15);
            if ($totalPages > 1):
        ?>
        <div class="nf-pagination" style="padding:16px 24px">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?p=<?= $i ?>&form_id=<?= urlencode($formId ?? '') ?>" class="nf-page-btn <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>
</body>
</html>
