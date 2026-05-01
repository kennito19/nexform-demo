<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/nexform/autoload.php';

use NexForm\NexForm;

$form = new NexForm('quote');
$form->action('../process.php')
     ->theme('nf-theme-dark')
     ->section('Company Details', 'Tell us about your business')
     ->text('company', 'Company Name', ['rules' => ['required'], 'placeholder' => 'Acme Corp', 'class' => 'nf-half'])
     ->text('name',    'Contact Person', ['rules' => ['required'], 'placeholder' => 'Jane Doe', 'class' => 'nf-half'])
     ->email('email',  'Email Address',  ['rules' => ['required', 'email'], 'placeholder' => 'jane@acme.com', 'class' => 'nf-half'])
     ->tel('phone',    'Phone Number',   ['rules' => ['required', 'phone'], 'placeholder' => '+1 555 000', 'class' => 'nf-half'])
     ->section('Project Details', 'Help us understand your requirements')
     ->select('service', 'Service Needed', [
         'web_design'   => 'Web Design',
         'web_dev'      => 'Web Development',
         'mobile_app'   => 'Mobile App',
         'seo'          => 'SEO / Marketing',
         'branding'     => 'Branding',
         'other'        => 'Other',
     ], ['rules' => ['required'], 'class' => 'nf-half'])
     ->select('budget', 'Budget Range', [
         'under_1k'  => 'Under $1,000',
         '1k_5k'     => '$1,000 – $5,000',
         '5k_10k'    => '$5,000 – $10,000',
         '10k_25k'   => '$10,000 – $25,000',
         '25k_plus'  => '$25,000+',
     ], ['rules' => ['required'], 'class' => 'nf-half'])
     ->select('timeline', 'Desired Timeline', [
         'asap'      => 'ASAP',
         '1_month'   => '1 Month',
         '3_months'  => '3 Months',
         '6_months'  => '6 Months',
         'flexible'  => 'Flexible',
     ], ['class' => 'nf-half'])
     ->rating('satisfaction', 'How did you hear about us? Rate your interest', ['class' => 'nf-half'])
     ->textarea('details', 'Project Details', ['rules' => ['required', 'minlen:20'], 'placeholder' => 'Describe your project...', 'rows' => 6, 'maxlen' => 3000])
     ->submit('Request a Quote');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Get a Quote – NexForm Demo</title>
    <link rel="stylesheet" href="../assets/css/nexform.css">
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #0f0f1a; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 16px; }
        h1 { color: #e2e8f0; font-size: 1.6rem; margin-bottom: 4px; text-align: center; }
        p.sub { color: #64748b; margin-bottom: 28px; text-align: center; }
        .back { color: #64748b; font-size: .85rem; text-decoration: none; margin-bottom: 16px; display: inline-block; }
        .back:hover { color: #a78bfa; }
        .nf-form-card { background: #1e1e2e; border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,.5); padding: 36px 40px; }
    </style>
</head>
<body>
    <a class="back" href="../index.php">&larr; Back to demos</a>
    <h1>Request a Quote</h1>
    <p class="sub">Fill in the details and we'll send you a custom proposal.</p>

    <div class="nf-form-card" style="width:100%;max-width:720px">
        <?= $form->render() ?>
    </div>

    <script src="../assets/js/nexform.js"></script>
    <script>
        document.querySelectorAll('.nf-form').forEach(f => new NexForm(f));
    </script>
</body>
</html>
