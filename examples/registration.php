<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/nexform/autoload.php';

use NexForm\NexForm;

$countries = [
    'us' => 'United States', 'gb' => 'United Kingdom',
    'ca' => 'Canada', 'au' => 'Australia',
    'de' => 'Germany', 'fr' => 'France',
    'in' => 'India', 'ng' => 'Nigeria',
    'other' => 'Other',
];

$form = new NexForm('registration');
$form->action('../process.php')
     ->theme('nf-theme-material')
     ->text('first_name', 'First Name', ['rules' => ['required', 'minlen:2'], 'placeholder' => 'John', 'class' => 'nf-half'])
     ->text('last_name',  'Last Name',  ['rules' => ['required', 'minlen:2'], 'placeholder' => 'Smith', 'class' => 'nf-half'])
     ->email('email',     'Email Address', ['rules' => ['required', 'email'], 'placeholder' => 'john@example.com'])
     ->tel('phone',       'Phone Number',  ['placeholder' => '+1 555 000 0000'])
     ->select('country',  'Country', $countries, ['rules' => ['required']])
     ->text('password',   'Password', ['rules' => ['required', 'minlen:8'], 'placeholder' => 'Minimum 8 characters'])
     ->text('password_c', 'Confirm Password', ['rules' => ['required'], 'placeholder' => 'Repeat your password'])
     ->radio('plan', 'Select Plan', [
         'free'    => 'Free – Basic features, 1 form',
         'starter' => 'Starter $9/mo – 10 forms, email support',
         'pro'     => 'Pro $29/mo – Unlimited forms, priority support',
     ], ['rules' => ['required'], 'default' => 'free'])
     ->toggle('newsletter', 'Subscribe to our newsletter', ['default' => true])
     ->toggle('terms', 'I agree to the Terms of Service', [])
     ->submit('Create Account');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration – NexForm Demo</title>
    <link rel="stylesheet" href="../assets/css/nexform.css">
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #f5f7fa; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 16px; }
        h1 { color: #1976d2; font-size: 1.6rem; margin-bottom: 4px; text-align: center; }
        p.sub { color: #757575; margin-bottom: 28px; text-align: center; }
        .back { color: #1976d2; font-size: .85rem; text-decoration: none; margin-bottom: 16px; display: inline-block; }
        .back:hover { color: #1565c0; }
        .nf-form-card { background: #fff; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,.12); padding: 36px 40px; }
    </style>
</head>
<body>
    <a class="back" href="../index.php">&larr; Back to demos</a>
    <h1>Create an Account</h1>
    <p class="sub">Join thousands of users who trust NexForm.</p>

    <div class="nf-form-card" style="width:100%;max-width:640px">
        <?= $form->render() ?>
    </div>

    <script src="../assets/js/nexform.js"></script>
    <script>
        document.querySelectorAll('.nf-form').forEach(f => new NexForm(f));
    </script>
</body>
</html>
