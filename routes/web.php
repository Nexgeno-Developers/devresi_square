<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use niklasravnsborg\LaravelPdf\Facades\Pdf;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Frontend\FormController;
use App\Http\Controllers\Backend\AizUploadController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Backend\AuthenticateController;
use App\Http\Controllers\Frontend\CustomerStatementController;

// Route::get('/test-pdf', function() {
//     $pdf = PDF::loadHTML('<h1>Hello World</h1>');
//     return $pdf->download('test.pdf');
// });

Route::get('/storage-link', function () {
    if (!file_exists(public_path('storage'))) {
        Artisan::call('storage:link');
        return 'Storage link created successfully.';
    }
    return 'Storage link already exists.';
});

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

    // Frontend routes
    Route::get('/', [FrontendController::class, 'index'])->name('home');
    Route::get('/pricing', [FrontendController::class, 'pricing'])->name('pricing');
    // Handle GET /logout gracefully (e.g. from email links or direct URL)
    Route::get('/logout', function () {
        \Illuminate\Support\Facades\Auth::logout();
        return redirect()->route('login');
    });

    // Password Reset Routes
    Route::get('/password/reset/form/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset/', [PasswordResetController::class, 'reset'])->name('password.reset');

    // Customer self-serve statement
    Route::middleware('auth')->group(function () {
        Route::get('/customer/statements', [CustomerStatementController::class, 'show'])->name('customer.statements');
    });

});

Route::group(['middleware' => 'auth', 'prefix' => 'admin'], function () {
    Route::get('/login', [AuthenticateController::class, 'index'])->name('backend.login');
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('backend.dashboard');
});

// Optional: Redirect from '/admin' to the login page if not authenticated
Route::get('/admin', function () {
    return redirect(route('backend.login'));
});

// AIZ Uploader
Route::controller(AizUploadController::class)->group(function () {
    Route::post('/aiz-uploader', 'show_uploader');
    Route::post('/aiz-uploader/upload', 'upload');
    Route::get('/aiz-uploader/get_uploaded_files', 'get_uploaded_files');
    Route::post('/aiz-uploader/get_file_by_ids', 'get_preview_files');
    Route::get('/aiz-uploader/download/{id}', 'attachment_download')->name('download_attachment');
    Route::delete('/aiz-uploader/destroy/{id}', 'destroy')->name('aiz_uploader.destroy');
});

// uploaded files
Route::resource('/uploaded-files', AizUploadController::class);
Route::controller(AizUploadController::class)->group(function () {
    Route::any('/uploaded-files/file-info', 'file_info')->name('uploaded-files.info');
    Route::get('/uploaded-files/destroy/{id}', 'destroy')->name('uploaded-files.destroy');
    Route::post('/bulk-uploaded-files-delete', 'bulk_uploaded_files_delete')->name('bulk-uploaded-files-delete');
    Route::get('/all-file', 'all_file');
});

Route::get('/helper', function () {
    return view('helper');
});

// ── Twilio SMS test (remove after debugging) ──────────────────────────────
Route::get('/test-sms', function () {
    $to = request('to', '');
    if (!$to) return 'Pass ?to=9833579014 in the URL';

    // Normalize
    $mobile = preg_replace('/[^\d]/', '', $to);
    if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) $mobile = substr($mobile, 2);
    if (strlen($mobile) !== 10) return "Invalid number: {$mobile}";

    // ── Try Fast2SMS ──
    if (env('FAST2SMS_API_KEY')) {
        $params = http_build_query([
            'authorization' => env('FAST2SMS_API_KEY'),
            'route'         => 'q',
            'message'       => 'Your ResiSquare OTP is 123456. Valid for 2 minutes. Do not share.',
            'language'      => 'english',
            'flash'         => '0',
            'numbers'       => $mobile,
        ]);
        $ch = curl_init('https://www.fast2sms.com/dev/bulkV2?' . $params);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET        => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Cache-Control: no-cache'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return response()->json([
            'provider'  => 'Fast2SMS',
            'http_code' => $httpCode,
            'mobile'    => $mobile,
            'response'  => json_decode($response, true) ?? $response,
        ]);
    }

    // ── Try Twilio ──
    $sid   = env('TWILIO_SID');
    $token = env('TWILIO_TOKEN');
    $from  = env('TWILIO_FROM');
    $toE164 = '+91' . $mobile;
    $fromClean = preg_replace('/[^\d+]/', '', $from ?? '');
    if ($fromClean && !str_starts_with($fromClean, '+')) $fromClean = '+' . $fromClean;

    $url  = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
    $data = http_build_query(['From' => $fromClean, 'To' => $toE164, 'Body' => 'Test OTP: 123456']);
    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => "{$sid}:{$token}",
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return response()->json([
        'provider'   => 'Twilio',
        'http_code'  => $httpCode,
        'sid_prefix' => substr($sid ?? '', 0, 4),
        'from'       => $fromClean,
        'to'         => $toE164,
        'response'   => json_decode($response, true) ?? $response,
    ]);
});

Route::get('/form/{type}', [FormController::class, 'show'])->name('form.show');
Route::post('/form/{type}', [FormController::class, 'submit'])->name('form.submit');
