<?php
$fieldMap = [
    'company'  => ['label' => 'Company Name',  'rules' => ['required', 'maxlen:100'], 'filters' => ['trim', 'strip_tags']],
    'name'     => ['label' => 'Contact Person', 'rules' => ['required', 'maxlen:80'],  'filters' => ['trim', 'strip_tags']],
    'email'    => ['label' => 'Email Address',  'rules' => ['required', 'email'],       'filters' => ['trim', 'email']],
    'phone'    => ['label' => 'Phone',           'rules' => ['required', 'phone'],       'filters' => ['trim', 'phone']],
    'service'  => ['label' => 'Service Needed',  'rules' => ['required'],               'filters' => ['trim', 'strip_tags']],
    'budget'   => ['label' => 'Budget Range',    'rules' => ['required'],               'filters' => ['trim', 'strip_tags']],
    'timeline' => ['label' => 'Timeline',        'rules' => [],                         'filters' => ['trim', 'strip_tags']],
    'details'  => ['label' => 'Project Details', 'rules' => ['required', 'minlen:20'],  'filters' => ['trim', 'strip_tags']],
];
