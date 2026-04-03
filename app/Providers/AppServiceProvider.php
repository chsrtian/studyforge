<?php

namespace App\Providers;

use App\Models\StudySession;
use App\Policies\StudySessionPolicy;
use App\Services\QueueHealthService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       
        if (app()->environment('production')) {
        URL::forceScheme('https');
        }
        Gate::policy(StudySession::class, StudySessionPolicy::class);

        Event::listen(JobProcessing::class, function () {
            app(QueueHealthService::class)->markHeartbeat('job-processing');
        });

        Event::listen(JobProcessed::class, function () {
            app(QueueHealthService::class)->markHeartbeat('job-processed');
        });

        Event::listen(JobFailed::class, function () {
            app(QueueHealthService::class)->markHeartbeat('job-failed');
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $email = (string) $request->session()->get('otp_login.email', 'guest');

            return Limit::perMinutes(10, 20)
                ->by($request->ip().'|'.$email)
                ->response(function () {
                    return redirect()->route('otp.verify.create')->withErrors([
                        'otp' => 'Too many OTP verification attempts. Please wait a few minutes and try again.',
                    ]);
                });
        });

        RateLimiter::for('otp-send', function (Request $request) {
            $email = (string) $request->session()->get('otp_login.email', 'guest');

            return Limit::perMinutes(15, 3)
                ->by($request->ip().'|'.$email)
                ->response(function () {
                    return redirect()->route('otp.verify.create')->withErrors([
                        'otp' => 'Too many OTP requests. Please wait before requesting another code.',
                    ]);
                });
        });

        RateLimiter::for('chat-send', function (Request $request) {
            $userKey = (string) optional($request->user())->getAuthIdentifier();
            $key = $userKey !== '' ? $userKey : $request->ip();

            return Limit::perMinute(20)
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many messages. Please wait a minute and try again.',
                    ], 429);
                });
        });
    }
}
