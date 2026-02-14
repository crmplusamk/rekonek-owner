<?php

use Illuminate\Support\Str;

return [
    [
        'id' => Str::uuid(),
        'name' => 'administrator',
        'alias' => 'Administrator',
        'guard_name' => 'web',
        'is_delete' => false,
        'created_at' => now(),
    ],
    [
        'id' => Str::uuid(),
        'name' => 'affiliator',
        'alias' => 'Affiliator',
        'guard_name' => 'web',
        'is_delete' => false,
        'created_at' => now(),
    ]
];
