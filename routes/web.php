<?php

use App\Http\Controllers\Auth\SignupIntentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/app');
});

Route::get('/signup/verify/{token}', [SignupIntentController::class, 'verify'])
    ->name('signup.verify');
