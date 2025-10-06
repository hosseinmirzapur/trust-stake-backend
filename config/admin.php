<?php

use Illuminate\Support\Facades\Hash;

return [
    'name' => env('DEFAULT_ADMIN_USER'),
    'email' => env('DEFAULT_ADMIN_EMAIL'),
    'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD')),
];