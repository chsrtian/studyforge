<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\LoginAuditService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected OtpService $otpService,
        protected LoginAuditService $auditService
    ) {
    }

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $email = (string) $request->string('email')->lower();
        $this->auditService->log('login_attempt', null, $email, $request);

        try {
            $user = $request->authenticate();
        } catch (ValidationException $exception) {
            $this->auditService->log('login_failed', null, $email, $request, [
                'reason' => 'invalid_credentials',
            ]);
            throw $exception;
        }

        try {
            $this->otpService->issueOtp($user, $request);
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            $isSmtpAuthFailure = str_contains($message, '535') || str_contains($message, 'BadCredentials');
            $isSmtpSchemeFailure = str_contains($message, 'scheme is not supported') || str_contains($message, 'supported schemes for mailer');

            Log::error('OTP login flow failed before verification step.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $message,
                'mailer' => config('mail.default'),
            ]);

            $this->auditService->log('login_failed', $user, $user->email, $request, [
                'reason' => 'otp_issue_failed',
            ]);

            $userFacingMessage = 'Unable to send verification code right now. Please try again in a moment.';

            if (app()->isLocal() && $isSmtpAuthFailure) {
                $userFacingMessage = 'SMTP authentication failed. For Gmail, use a 16-character App Password (not your normal password), then run config:clear.';
            }

            if (app()->isLocal() && $isSmtpSchemeFailure) {
                $userFacingMessage = 'SMTP transport scheme is misconfigured. Use MAIL_SCHEME=smtp for port 587, then clear config cache.';
            }

            return back()->withErrors([
                'email' => $userFacingMessage,
            ])->withInput($request->only('email'));
        }

        $request->session()->put('otp_login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'remember' => $request->boolean('remember'),
            'requested_at' => now()->toIso8601String(),
        ]);

        $this->auditService->log('otp_sent', $user, $user->email, $request);

        return redirect()->route('otp.verify.create')->with('status', 'otp_sent');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($user) {
            $this->auditService->log('logout', $user, $user->email, $request);
        }

        return redirect('/');
    }
}
