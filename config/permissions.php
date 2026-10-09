<?php

return [
    'actions' => [
        'view' => 'View',
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],
    'modules' => [
        'dashboard' => ['name' => 'Dashboard', 'actions' => ['view']],
        'settings' => ['name' => 'Settings', 'actions' => ['view', 'edit']],
        'accounts' => ['name' => 'Accounts', 'actions' => ['view', 'add', 'edit', 'delete']],
    ],
];
