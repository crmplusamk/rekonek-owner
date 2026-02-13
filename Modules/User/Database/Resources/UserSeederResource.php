<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return [
    [
        'id' => Str::uuid(),
        'name' => 'administrator',
        'email' => 'administrator@mail.com',
        'password' => Hash::make('administrator'),
        'is_delete' => false,
        'created_at' => now(),
        'role' => 'administrator',
    ],
    [
        'id' => Str::uuid(),
        'name' => 'affiliator ',
        'email' => 'affiliator @mail.com',
        'password' => Hash::make('affiliator '),
        'is_delete' => false,
        'created_at' => now(),
        'role' => 'affiliator ',
    ],
];
