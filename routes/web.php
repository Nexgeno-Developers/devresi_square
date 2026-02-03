<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Backend\AuthenticateController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Backend\AizUploadController;

// Group for web routes
Route::group(['middleware' => 'web'], function () {
    // Auth routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Frontend routes
    Route::get('/', [FrontendController::class, 'index'])->name('home');
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
