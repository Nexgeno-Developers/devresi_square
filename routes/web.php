<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Backend\AizUploadController;
use App\Http\Controllers\Frontend\ContractorClaimController;
use App\Http\Controllers\Frontend\ContractorJobController;
use App\Http\Controllers\Frontend\ContractorProfileController;
use App\Http\Controllers\Frontend\CustomerStatementController;
use App\Http\Controllers\Frontend\FormController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Frontend\RepairQuoteController;
use App\Http\Controllers\Frontend\RepairVisitController;
use Illuminate\Support\Facades\Route;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

Route::post('/stripe/webhook', [\App\Http\Controllers\Webhook\StripeWebhookController::class, 'handle'])->name('stripe.webhook');
Route::post('/stripe/rent/webhook', [\App\Http\Controllers\Webhook\StripeRentWebhookController::class, 'handle'])->name('stripe.rent.webhook');

// Route::get('/test-pdf', function() {
//     $pdf = PDF::loadHTML('<h1>Hello World</h1>');
//     return $pdf->download('test.pdf');
// });

// Schema changes run with `php artisan migrate`. That applies pending migrations only.
// It does not seed, refresh, or wipe existing rows. There is no public /migrate URL.

// Group for web routes
Route::group(['middleware' => 'web'], function () {

    // Auth routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/register/contractor', [RegistrationController::class, 'showContractorForm'])->name('register.contractor');
    Route::post('/register/contractor', [RegistrationController::class, 'submitContractor'])->name('register.contractor.post');

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
    Route::get('/robots.txt', function () {
        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /aiz-uploader',
            'Disallow: /contractor',
            'Disallow: /_debug',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    })->name('robots');
    Route::get('/sitemap.xml', function () {
        $locs = collect([
            route('home'),
            route('pricing'),
            route('login'),
            route('register'),
        ])->map(fn (string $url) => '<url><loc>'.e($url).'</loc></url>')->implode('');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$locs.'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    })->name('sitemap');
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
    Route::get('/email/confirm/{token}', [EmailChangeController::class, 'confirm'])
        ->middleware('throttle:20,1')
        ->where('token', '[A-Za-z0-9]+')
        ->name('email.confirm');

    // Customer self-serve statement
    Route::middleware(['auth', 'current.account', 'account.status', 'landlord.restricted'])->group(function () {
        Route::get('/customer/statements', [CustomerStatementController::class, 'show'])->name('customer.statements');
    });

    // Design experiment — proposed replacement for the property detail page.
    // Read-only prototype, self-contained in resources/views/backend/testExperiments.blade.php
    Route::get('/testExperiments', function () {
        return view('backend.testExperiments');
    })->middleware(['auth', 'current.account', 'account.status', 'landlord.restricted'])->name('test.experiments');

    // Contractor marketplace. Old repair list redirects here and is not the product path.
    Route::middleware(['auth'])->prefix('contractor')->name('contractor.')->group(function () {
        Route::get('/repairs', function () {
            abort_unless(auth()->user()?->hasRole('Contractor'), 403);

            return redirect()->route('contractor.jobs.index');
        })->name('repairs.index');
        Route::get('/repairs/{id}', function () {
            abort_unless(auth()->user()?->hasRole('Contractor'), 403);

            return redirect()->route('contractor.jobs.index');
        })->name('repairs.show');

        Route::get('/jobs', [ContractorJobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/{offer}', [ContractorJobController::class, 'show'])->name('jobs.show');
        Route::post('/jobs/{offer}/quote', [ContractorJobController::class, 'quote'])->name('jobs.quote');
        Route::post('/jobs/{offer}/decline', [ContractorJobController::class, 'decline'])->name('jobs.decline');
        Route::post('/jobs/{offer}/withdraw', [ContractorJobController::class, 'withdraw'])->name('jobs.withdraw');
        Route::post('/jobs/{offer}/cancel', [ContractorJobController::class, 'cancel'])->name('jobs.cancel');
        Route::post('/jobs/{offer}/reconfirm', [ContractorJobController::class, 'reconfirm'])->name('jobs.reconfirm');
        Route::post('/jobs/{offer}/progress', [ContractorJobController::class, 'progress'])->name('jobs.progress');
        Route::post('/jobs/{offer}/dispute', [ContractorJobController::class, 'dispute'])->name('jobs.dispute');
        Route::get('/jobs/{offer}/photos/{upload}', [ContractorJobController::class, 'photo'])->name('jobs.photo');
        Route::post('/jobs/{offer}/messages', [ContractorJobController::class, 'message'])->middleware('throttle:30,1')->name('jobs.message');
        Route::post('/jobs/{offer}/variations', [ContractorJobController::class, 'variation'])->name('jobs.variation');
        Route::post('/jobs/{offer}/variations/{variation}/withdraw', [ContractorJobController::class, 'withdrawVariation'])->name('jobs.variation.withdraw');
        Route::post('/jobs/{offer}/reschedule', [ContractorJobController::class, 'reschedule'])->name('jobs.reschedule');
        Route::get('/jobs/{offer}/completion-photos/{index}', [ContractorJobController::class, 'completionPhoto'])->whereNumber('index')->name('jobs.completion_photo');
        Route::get('/jobs/{offer}/invoice', [ContractorJobController::class, 'invoice'])->name('jobs.invoice');
        Route::post('/jobs/{offer}/invoice', [ContractorJobController::class, 'issueInvoice'])->name('jobs.invoice.store');

        Route::get('/profile', [ContractorProfileController::class, 'edit'])->name('profile.edit');
        Route::get('/profile/bank', [ContractorProfileController::class, 'bank'])->name('profile.bank');
        Route::post('/profile/bank', [ContractorProfileController::class, 'updateBank'])->name('profile.bank.update');
        Route::post('/profile/business', [ContractorProfileController::class, 'updateBusiness'])->name('profile.business');
        Route::post('/profile/documents', [ContractorProfileController::class, 'updateDocuments'])->name('profile.documents');
        Route::post('/profile/reapply', [ContractorProfileController::class, 'reapply'])->name('profile.reapply');
    });

    Route::view('/contractor-terms', 'frontend.contractor.terms')->name('contractor.terms');
    Route::get('/contractor/claim/{user}', [ContractorClaimController::class, 'show'])
        ->middleware('throttle:20,1')
        ->name('contractor.claim');
    Route::post('/contractor/claim/{user}', [ContractorClaimController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('contractor.claim.store');

    Route::get('/repair-visits/{offer}/confirm', [RepairVisitController::class, 'show'])
        ->middleware(['signed', 'throttle:30,1'])
        ->name('repair_visit.confirm');
    Route::post('/repair-visits/{offer}/confirm', [RepairVisitController::class, 'confirm'])
        ->middleware(['signed', 'throttle:30,1'])
        ->name('repair_visit.confirm.store');

    Route::middleware('legacy.contractors')->group(function () {
        Route::get('/repair-quotes/{assignment}/{token}', [RepairQuoteController::class, 'show'])
            ->middleware('signed')
            ->name('repair-quotes.show');
        Route::post('/repair-quotes/{assignment}/{token}', [RepairQuoteController::class, 'submit'])
            ->middleware('signed')
            ->name('repair-quotes.submit');
    });

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
