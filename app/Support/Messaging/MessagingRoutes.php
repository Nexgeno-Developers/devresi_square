<?php

namespace App\Support\Messaging;

use App\Http\Controllers\Messaging\MessagingController;
use Illuminate\Support\Facades\Route;

/**
 * The same messaging routes for each area. Each area's group adds its own
 * auth middleware; messaging.as decides the acting party from the area.
 * There is no route that creates a chat: they come from tenancies and jobs.
 */
final class MessagingRoutes
{
    public static function register(string $area): void
    {
        Route::middleware('messaging.as:'.$area)
            ->prefix('messages')
            ->name($area.'.messages.')
            ->group(function () {
                Route::get('/', [MessagingController::class, 'index'])->name('index');
                Route::get('/archived', [MessagingController::class, 'archived'])->name('archived');
                Route::get('/unread', [MessagingController::class, 'unread'])->middleware('throttle:60,1')->name('unread');

                Route::get('/c/{conversation}', [MessagingController::class, 'show'])->name('show');
                Route::get('/c/{conversation}/poll', [MessagingController::class, 'poll'])->middleware('throttle:60,1')->name('poll');
                Route::post('/c/{conversation}/messages', [MessagingController::class, 'send'])->middleware('throttle:30,1')->name('send');
                Route::get('/c/{conversation}/files/{attachment}', [MessagingController::class, 'download'])->name('download');
                Route::post('/c/{conversation}/report', [MessagingController::class, 'report'])->middleware('throttle:5,60')->name('report');
            });
    }
}
