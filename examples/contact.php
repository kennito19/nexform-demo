<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/nexform/autoload.php';

use NexForm\NexForm;

$form = new NexForm('contact');
$form->action('../process.php')
     ->theme('nf-theme-light')
     ->text('name',    'Your Name',    ['rules' => ['required', 'minlen:2', 'maxlen:80'], 'placeholder' => 'John Smith', 'class' => 'nf-half'])
     ->email('email',  'Email Address', ['rules' => ['required', 'email'], 'placeholder' => 'john@example.com', 'class' => 'nf-half'])
     ->tel('phone',    'Phone Number',  ['placeholder' => '+1 555 000 0000', 'class' => 'nf-half'])
     ->select('subject', 'Subject', [
         'general'   => 'General Enquiry',
         'support'   => 'Technical Support',
         'sales'     => 'Sales',
         'other'     => 'Other',
     ], ['rules' => ['required'], 'class' => 'nf-half'])
     ->textarea('message', 'Message', ['rules' => ['required', 'minlen:10'], 'placeholder' => 'How can we help you?', 'maxlen' => 2000])
     ->file('attachment', 'Attachment (optional)')
     ->submit('Send Message');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Form – NexForm Demo</title>
    <link rel="stylesheet" href="../assets/css/nexform.css">
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 16px; }
        h1 { color: #fff; font-size: 1.6rem; margin-bottom: 4px; text-align: center; }
        p.sub { color: rgba(255,255,255,.75); margin-bottom: 28px; text-align: center; }
        .back { color: rgba(255,255,255,.7); font-size: .85rem; text-decoration: none; margin-bottom: 16px; display: inline-block; }
        .back:hover { color: #fff; }
    </style>
</head>
<body>
    <a class="back" href="../index.php">&larr; Back to demos</a>
    <h1>Contact Us</h1>
    <p class="sub">We'll get back to you within 1–2 business days.</p>

    <div class="nf-form-card" style="width:100%;max-width:680px">
        <?= $form->render() ?>
    </div>

    <script src="../assets/js/nexform.js"></script>
    <script>
        document.querySelectorAll('.nf-form').forEach(f => new NexForm(f));
    </script>
</body>
</html>
