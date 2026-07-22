<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Auth\SignupIntentController;
use App\Http\Controllers\Billing\DemoCheckoutController;
use App\Http\Controllers\CaptureLeadController;
use App\Http\Controllers\QodeFileDownloadController;
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

    Route::post('/'.$scanPrefix.'/{slug}/leads', CaptureLeadController::class)
        ->where('slug', '[a-z0-9]+')
        ->name('qodes.leads.store');

    Route::get('/'.$scanPrefix.'/{slug}/download', QodeFileDownloadController::class)
        ->where('slug', '[a-z0-9]+')
        ->name('qodes.download');
}

// Host-based resolve for dedicated scan hosts and verified custom domains.
Route::get('/{slug}', QodeResolveController::class)
    ->where('slug', '[a-z0-9]{3,}')
    ->name($scanPrefix === '' ? 'qodes.resolve' : 'qodes.resolve.host');

Route::post('/{slug}/leads', CaptureLeadController::class)
    ->where('slug', '[a-z0-9]{3,}')
    ->name($scanPrefix === '' ? 'qodes.leads.store' : 'qodes.leads.store.host');

Route::get('/{slug}/download', QodeFileDownloadController::class)
    ->where('slug', '[a-z0-9]{3,}')
    ->name($scanPrefix === '' ? 'qodes.download' : 'qodes.download.host');

Route::middleware('auth')->group(function () {
    Route::get('/billing/demo/{token}', [DemoCheckoutController::class, 'show'])
        ->name('billing.demo.checkout');
    Route::post('/billing/demo', [DemoCheckoutController::class, 'complete'])
        ->name('billing.demo.complete');

    Route::get('/admin/impersonate/{user}', [ImpersonationController::class, 'start'])
        ->name('admin.impersonate');
    Route::post('/admin/leave-impersonation', [ImpersonationController::class, 'stop'])
        ->name('admin.leave-impersonation');

    Route::get('/qodes/{qode}/qr/{format}', QodeQrCodeController::class)
        ->whereIn('format', ['svg', 'png'])
        ->name('qodes.qr');
});
