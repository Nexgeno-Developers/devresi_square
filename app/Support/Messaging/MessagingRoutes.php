<?php

namespace App\Support\Messaging;

use App\Http\Controllers\Messaging\JobThreadController;
use App\Http\Controllers\Messaging\MessagingController;
use App\Http\Controllers\Messaging\SafetyController;
use Illuminate\Support\Facades\Route;

/**
 * The same messaging routes for each area. Each area's group adds its own
 * auth middleware; messaging.as decides the acting party from the area.
 */
final class MessagingRoutes
{
    public static function register(string $area): void
    {
        Route::middleware('messaging.as:'.$area)
            ->prefix('messages')
            ->name($area.'.messages.')
            ->group(function () use ($area) {
                Route::get('/', [MessagingController::class, 'index'])->name('index');
                Route::get('/archived', [MessagingController::class, 'archived'])->name('archived');
                Route::get('/requests', [MessagingController::class, 'requests'])->name('requests');
                Route::get('/new', [MessagingController::class, 'create'])->name('create');
                Route::post('/requests', [MessagingController::class, 'storeRequest'])->middleware('throttle:10,1')->name('requests.store');
                Route::post('/requests/{messageRequest}/accept', [MessagingController::class, 'accept'])->name('requests.accept');
                Route::post('/requests/{messageRequest}/decline', [MessagingController::class, 'decline'])->name('requests.decline');
                Route::post('/requests/{messageRequest}/withdraw', [MessagingController::class, 'withdraw'])->name('requests.withdraw');
                Route::post('/requests/{messageRequest}/block', [SafetyController::class, 'blockRequest'])->name('requests.block');
                Route::post('/requests/{messageRequest}/report', [SafetyController::class, 'reportRequest'])->middleware('throttle:10,1')->name('requests.report');
                Route::get('/start/{context}/{id}/{user?}', [MessagingController::class, 'start'])
                    ->whereIn('context', ['tenant', 'tenancy', 'repair-job', 'compliance-job', 'event'])
                    ->whereNumber('id')->whereNumber('user')
                    ->name('start');
                Route::get('/unread', [MessagingController::class, 'unread'])->middleware('throttle:60,1')->name('unread');

                Route::get('/c/{conversation}', [MessagingController::class, 'show'])->name('show');
                Route::get('/c/{conversation}/poll', [MessagingController::class, 'poll'])->middleware('throttle:60,1')->name('poll');
                Route::post('/c/{conversation}/messages', [MessagingController::class, 'send'])->middleware('throttle:30,1')->name('send');
                Route::delete('/c/{conversation}/messages/{message}', [MessagingController::class, 'unsend'])->scopeBindings()->name('unsend');
                Route::get('/c/{conversation}/files/{attachment}', [MessagingController::class, 'download'])->name('download');
                Route::post('/c/{conversation}/archive', [MessagingController::class, 'archive'])->name('archive');
                Route::post('/c/{conversation}/mute', [MessagingController::class, 'mute'])->name('mute');
                Route::post('/c/{conversation}/block', [SafetyController::class, 'blockConversation'])->name('block');
                Route::post('/c/{conversation}/unblock', [SafetyController::class, 'unblockConversation'])->name('unblock');
                Route::post('/c/{conversation}/report', [SafetyController::class, 'reportConversation'])->middleware('throttle:10,1')->name('report');

                if ($area !== MessagingArea::PORTAL) {
                    Route::get('/jobs/repair/{repairOffer}', [JobThreadController::class, 'repair'])->name('jobs.repair');
                    Route::post('/jobs/repair/{repairOffer}', [JobThreadController::class, 'repairSend'])->middleware('throttle:30,1')->name('jobs.repair.send');
                    Route::get('/jobs/compliance/{complianceOffer}', [JobThreadController::class, 'compliance'])->name('jobs.compliance');
                    Route::post('/jobs/compliance/{complianceOffer}', [JobThreadController::class, 'complianceSend'])->middleware('throttle:30,1')->name('jobs.compliance.send');
                }
            });
    }
}
