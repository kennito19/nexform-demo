<?php
/**
 * Field map for the Contact form.
 * This file is loaded by process.php based on nf_form_id = 'contact'.
 */
$fieldMap = [
    'name' => [
        'label'   => 'Your Name',
        'rules'   => ['required', 'minlen:2', 'maxlen:80'],
        'filters' => ['trim', 'strip_tags'],
    ],
    'email' => [
        'label'   => 'Email Address',
        'rules'   => ['required', 'email'],
        'filters' => ['trim', 'email'],
    ],
    'phone' => [
        'label'   => 'Phone Number',
        'rules'   => ['phone'],
        'filters' => ['trim', 'phone'],
    ],
    'subject' => [
        'label'   => 'Subject',
        'rules'   => ['required', 'minlen:3', 'maxlen:150'],
        'filters' => ['trim', 'strip_tags'],
    ],
    'message' => [
        'label'   => 'Message',
        'rules'   => ['required', 'minlen:10', 'maxlen:2000'],
        'filters' => ['trim', 'strip_tags'],
    ],
    'attachment' => [
        'label'   => 'Attachment',
        'rules'   => ['file_max:' . (5 * 1024 * 1024), 'file_types:jpg,jpeg,png,pdf,doc,docx,zip'],
        'filters' => [],
    ],
];
