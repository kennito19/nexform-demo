<?php
/**
 * Field map for the Multi-Step demo form.
 * Form ID: 'multistep'  (used in examples/multistep.php)
 */
$fieldMap = [
    'name'    => ['label' => 'Full Name',     'rules' => ['required', 'minlen:2'], 'filters' => ['trim', 'strip_tags']],
    'email'   => ['label' => 'Email Address', 'rules' => ['required', 'email'],    'filters' => ['trim', 'email']],
    'phone'   => ['label' => 'Phone',         'rules' => [],                       'filters' => ['trim', 'phone']],
    'subject' => ['label' => 'Service',       'rules' => ['required'],             'filters' => ['trim', 'strip_tags']],
    'budget'  => ['label' => 'Budget',        'rules' => [],                       'filters' => ['trim', 'strip_tags']],
    'message' => ['label' => 'Message',       'rules' => ['required', 'minlen:10'],'filters' => ['trim', 'strip_tags']],
    'source'  => ['label' => 'Referral',      'rules' => [],                       'filters' => ['trim', 'strip_tags']],
    'attachment' => ['label' => 'Attachment', 'rules' => ['file_max:' . (5*1024*1024), 'file_types:jpg,jpeg,png,pdf,zip'], 'filters' => []],
];
