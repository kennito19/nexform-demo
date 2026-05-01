<?php
$fieldMap = [
    'first_name' => ['label' => 'First Name',       'rules' => ['required', 'minlen:2', 'maxlen:50'],  'filters' => ['trim', 'strip_tags']],
    'last_name'  => ['label' => 'Last Name',        'rules' => ['required', 'minlen:2', 'maxlen:50'],  'filters' => ['trim', 'strip_tags']],
    'email'      => ['label' => 'Email Address',    'rules' => ['required', 'email'],                  'filters' => ['trim', 'email']],
    'password'   => ['label' => 'Password',         'rules' => ['required', 'minlen:8'],               'filters' => ['trim']],
    'password_c' => ['label' => 'Confirm Password', 'rules' => ['required', 'confirmed:password'],     'filters' => ['trim']],
    'phone'      => ['label' => 'Phone',             'rules' => ['phone'],                             'filters' => ['trim', 'phone']],
    'country'    => ['label' => 'Country',           'rules' => ['required'],                          'filters' => ['trim', 'strip_tags']],
    'terms'      => ['label' => 'Terms Agreement',   'rules' => ['required'],                          'filters' => ['trim']],
];
