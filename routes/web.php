<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Auth\SignupIntentController;
use App\Http\Controllers\Billing\DemoCheckoutController;
use App\Http\Controllers\QodeQrCodeController;
use App\Http\Controllers\QodeResolveController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/app');
});

Route::get('/signup/verify/{token}', [SignupIntentController::class, 'verify'])
    ->name('signup.verify');

$scanPrefix = trim((string) config('deqode.scan_path_prefix', 'r'), '/');

if ($scanPrefix !== '') {
    Route::get('/'.$scanPrefix.'/{slug}', QodeResolveController::class)
        ->where('slug', '[a-z0-9]+')
        ->name('qodes.resolve');
} else {
    Route::get('/{slug}', QodeResolveController::class)
        ->where('slug', '[a-z0-9]{3,}')
        ->name('qodes.resolve');
}

Route::middleware('auth')->group(function () {
    Route::get('/billing/demo/{token}', [DemoCheckoutController::class, 'show'])
        ->name('billing.demo.checkout');
    Route::post('/billing/demo', [DemoCheckoutController::class, 'complete'])
        ->name('billing.demo.complete');

    Route::get('/admin/impersonate/{user}', [ImpersonationController::class, 'start'])
        ->name('admin.impersonate');
    Route::post('/admin/leave-impersonation', [ImpersonationController::class, 'stop'])
        ->name('admin.leave-impersonation');

    Route::get('/qodes/{qode}/qr.svg', QodeQrCodeController::class)
        ->name('qodes.qr');
});
