<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json([
        'message' => 'Authentication required.',
    ], 401);
})->name('login');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/users', function () {
    return view('users.index');
});
