<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    // Correct the namespace to match the controller directory structure
    protected $namespace = 'App\Http\Controllers'; 

    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->configureAuthRateLimits();

        $this->routes(function () {
            Route::middleware('web')
                ->namespace($this->namespace . '\Frontend') // Assuming Frontend controllers are in App\Http\Controllers\Frontend
                ->group(base_path('routes/web.php'));

            Route::prefix('admin')
                ->middleware(['web'])
                ->namespace($this->namespace . '\Backend') // Assuming Backend controllers are in App\Http\Controllers\Backend
                ->group(base_path('routes/backend.php'));
        });
    }

    /**
     * Limits for public sign-in, sign-up, OTP, password reset and contact forms.
     * They stop password and OTP guessing and stop the app being used to flood inboxes or phones.
     */
    private function configureAuthRateLimits(): void
    {
        $tooMany = function (string $field, string $message) {
            return function (Request $request, array $headers) use ($field, $message) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 429, $headers);
                }

                return back()
                    ->withInput($request->except(['password', 'password_confirmation', 'otp']))
                    ->withErrors([$field => $message])
                    ->setStatusCode(302)
                    ->withHeaders($headers);
            };
        };

        $loginMessage = 'Too many sign-in attempts. Please wait a minute and try again.';
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(10)
                ->by('login:'.mb_strtolower((string) $request->input('email')).'|'.$request->ip())
                ->response($tooMany('email', $loginMessage)),
            Limit::perMinute(30)->by('login-ip:'.$request->ip())->response($tooMany('email', $loginMessage)),
        ]);

        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(6)
                ->by('otp:'.(session('reg_id') ?: 'none').'|'.$request->ip())
                ->response($tooMany('otp', 'Too many code attempts. Please wait a minute and try again.')),
            Limit::perMinute(20)->by('otp-ip:'.$request->ip())
                ->response($tooMany('otp', 'Too many code attempts. Please wait a minute and try again.')),
        ]);

        RateLimiter::for('auth-mail', fn (Request $request) => [
            Limit::perMinute(5)
                ->by('auth-mail:'.$request->ip())
                ->response($tooMany('email', 'Too many requests. Please wait a minute and try again.')),
            Limit::perHour(30)->by('auth-mail-hour:'.$request->ip())
                ->response($tooMany('email', 'Too many requests. Please try again later.')),
        ]);

        RateLimiter::for('public-form', fn (Request $request) => [
            Limit::perMinute(5)
                ->by('public-form:'.$request->ip())
                ->response($tooMany('form', 'Too many submissions. Please wait a minute and try again.')),
        ]);
    }
}
