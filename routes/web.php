<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Backend\AizUploadController;
use App\Http\Controllers\Frontend\ContractorPortalController;
use App\Http\Controllers\Frontend\CustomerStatementController;
use App\Http\Controllers\Frontend\FormController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Frontend\RepairQuoteController;
use Illuminate\Support\Facades\Route;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

Route::post('/stripe/webhook', [\App\Http\Controllers\Webhook\StripeWebhookController::class, 'handle'])->name('stripe.webhook');
Route::post('/stripe/rent/webhook', [\App\Http\Controllers\Webhook\StripeRentWebhookController::class, 'handle'])->name('stripe.rent.webhook');

// Route::get('/test-pdf', function() {
//     $pdf = PDF::loadHTML('<h1>Hello World</h1>');
//     return $pdf->download('test.pdf');
// });

// Launch Step 5: maintenance Artisan commands are CLI-only. Public HTTP entry points removed.

// Group for web routes
Route::group(['middleware' => 'web'], function () {

    // Auth routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Public Registration (OTP flow — email or phone)
    Route::get('/register',              [RegistrationController::class, 'showForm'])->name('register');
    Route::post('/register',             [RegistrationController::class, 'submit'])->name('register.post');
    Route::get('/register/verify-otp',   [RegistrationController::class, 'showOtpForm'])->name('register.verify.otp');
    Route::post('/register/verify-otp',  [RegistrationController::class, 'verifyOtp'])->name('register.verify.otp.post');
    Route::post('/register/resend-otp',  [RegistrationController::class, 'resendOtp'])->name('register.resend.otp');

    // Local-only OTP lookup for signup debugging. 404 outside local+APP_DEBUG.
    Route::get('/_debug/registration-otp', [\App\Http\Controllers\Auth\DebugRegistrationOtpController::class, 'show'])
        ->name('debug.registration.otp');

    // Frontend routes
    Route::get('/', [FrontendController::class, 'index'])->name('home');
    Route::get('/pricing', [FrontendController::class, 'pricing'])->name('pricing');
    // Handle GET /logout gracefully (e.g. from email links or direct URL)
    Route::get('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        return redirect()->route('login');
    });

    // Password Reset Routes
    Route::get('/password/forgot', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/form/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset/', [PasswordResetController::class, 'reset'])->name('password.reset');

    // Customer self-serve statement
    Route::middleware(['auth', 'current.account', 'account.status', 'landlord.restricted'])->group(function () {
        Route::get('/customer/statements', [CustomerStatementController::class, 'show'])->name('customer.statements');
    });

    // Design experiment — proposed replacement for the property detail page.
    // Read-only prototype, self-contained in resources/views/backend/testExperiments.blade.php
    Route::get('/testExperiments', function () {
        return view('backend.testExperiments');
    })->middleware(['auth', 'current.account', 'account.status', 'landlord.restricted'])->name('test.experiments');

    // Contractor portal — repair listing & detail (auth required)
    Route::middleware(['auth', 'current.account', 'account.status', 'landlord.restricted'])->prefix('contractor')->name('contractor.')->group(function () {
        Route::get('/repairs',       [ContractorPortalController::class, 'index'])->name('repairs.index');
        Route::get('/repairs/{id}',  [ContractorPortalController::class, 'show'])->name('repairs.show');
    });

    Route::get('/repair-quotes/{assignment}/{token}', [RepairQuoteController::class, 'show'])
        ->middleware('signed')
        ->name('repair-quotes.show');
    Route::post('/repair-quotes/{assignment}/{token}', [RepairQuoteController::class, 'submit'])
        ->middleware('signed')
        ->name('repair-quotes.submit');

});

// Optional: Redirect from '/admin' to the login page if not authenticated
Route::get('/admin', function () {
    return redirect(route('backend.login'));
});

// AIZ Uploader
Route::controller(AizUploadController::class)->middleware(['auth', 'current.account', 'account.status'])->group(function () {
    Route::post('/aiz-uploader', 'show_uploader');
    Route::post('/aiz-uploader/upload', 'upload');
    Route::get('/aiz-uploader/get_uploaded_files', 'get_uploaded_files');
    Route::post('/aiz-uploader/get_file_by_ids', 'get_preview_files');
    Route::get('/aiz-uploader/download/{id}', 'attachment_download')->name('download_attachment');
    Route::delete('/aiz-uploader/destroy/{id}', 'destroy')->name('aiz_uploader.destroy');
});

// uploaded files
Route::resource('/uploaded-files', AizUploadController::class)
    ->except(['destroy'])
    ->middleware(['auth', 'current.account', 'account.status']);
Route::controller(AizUploadController::class)->middleware(['auth', 'current.account', 'account.status'])->group(function () {
    Route::any('/uploaded-files/file-info', 'file_info')->name('uploaded-files.info');
    Route::get('/uploaded-files/destroy/{id}', 'destroy')->name('uploaded-files.destroy');
    Route::post('/bulk-uploaded-files-delete', 'bulk_uploaded_files_delete')->name('bulk-uploaded-files-delete');
    Route::get('/all-file', 'all_file');
});

Route::get('/helper', function () {
    return view('helper');
});


Route::get('/form/{type}', [FormController::class, 'show'])->name('form.show');
Route::post('/form/{type}', [FormController::class, 'submit'])->name('form.submit');
