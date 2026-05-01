<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/nexform/autoload.php';

use NexForm\NexForm;

$form = new NexForm('multistep');
$form->action('../process.php')
     ->theme('nf-theme-glass')
     ->steps(3)

     // Step 1 – Personal Info
     ->step(1, 'Personal Info')
     ->text('name',  'Full Name',     ['rules' => ['required'], 'placeholder' => 'John Smith',          'step' => 1, 'class' => 'nf-half'])
     ->email('email','Email Address', ['rules' => ['required', 'email'], 'placeholder' => 'you@example.com', 'step' => 1, 'class' => 'nf-half'])
     ->tel('phone',  'Phone',         ['placeholder' => '+1 555 000 0000', 'step' => 1])

     // Step 2 – Project Details
     ->step(2, 'Project Details')
     ->select('subject', 'Service', [
         'general' => 'General Enquiry',
         'support' => 'Technical Support',
         'sales'   => 'Sales',
     ], ['rules' => ['required'], 'step' => 2])
     ->select('budget', 'Budget', [
         'small'  => 'Under $1,000',
         'medium' => '$1,000 – $10,000',
         'large'  => '$10,000+',
     ], ['step' => 2])
     ->textarea('message', 'Message', ['rules' => ['required', 'minlen:10'], 'placeholder' => 'Describe your needs...', 'step' => 2, 'rows' => 5])

     // Step 3 – Final
     ->step(3, 'Attachments & Submit')
     ->file('attachment', 'Attach a File (optional)', ['step' => 3])
     ->radio('source', 'How did you find us?', [
         'google'   => 'Google Search',
         'referral' => 'Referral',
         'social'   => 'Social Media',
         'other'    => 'Other',
     ], ['step' => 3])
     ->submit('Submit');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Multi-Step Form – NexForm Demo</title>
    <link rel="stylesheet" href="../assets/css/nexform.css">
    <style>
        body {
            margin: 0;
            font-family: system-ui, sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }
        h1   { color: #fff; font-size: 1.6rem; margin-bottom: 4px; text-align: center; }
        p.sub { color: rgba(255,255,255,.65); margin-bottom: 28px; text-align: center; }
        .back { color: rgba(255,255,255,.6); font-size: .85rem; text-decoration: none; margin-bottom: 16px; display: inline-block; }
        .back:hover { color: #fff; }
        .nf-form-card {
            background:        rgba(255,255,255,.1);
            backdrop-filter:   blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border:            1px solid rgba(255,255,255,.2);
            border-radius:     20px;
            box-shadow:        0 8px 40px rgba(0,0,0,.3);
            padding:           40px;
        }
    </style>
</head>
<body>
    <a class="back" href="../index.php">&larr; Back to demos</a>
    <h1>Multi-Step Contact Form</h1>
    <p class="sub">Glassmorphism theme &bull; 3 step wizard with progress bar</p>

    <div class="nf-form-card" style="width:100%;max-width:660px">
        <?= $form->render() ?>
    </div>

    <script src="../assets/js/nexform.js"></script>
    <script>
        document.querySelectorAll('.nf-form').forEach(f => new NexForm(f));
    </script>
</body>
</html>
