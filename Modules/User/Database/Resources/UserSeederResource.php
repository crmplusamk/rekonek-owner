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
    ],
    [
        'id' => Str::uuid(),
        'name' => 'staff',
        'email' => 'staff@mail.com',
        'password' => Hash::make('staff'),
        'is_delete' => false,
        'created_at' => now(),
    ],
];
