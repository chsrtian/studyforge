<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginAuditService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    public function __construct(
        protected OtpService $otpService,
        protected LoginAuditService $auditService
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_login')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your login session has expired. Please login again.',
            ]);
        }

        $requestedAt = $request->session()->get('otp_login.requested_at');
        $secondsLeft = 600;

        if ($requestedAt) {
            $elapsed = now()->diffInSeconds($requestedAt);
            $secondsLeft = max(0, 600 - $elapsed);
        }

        return view('auth.otp-verify', [
            'email' => $request->session()->get('otp_login.email'),
            'secondsLeft' => $secondsLeft,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pendingLogin = $request->session()->get('otp_login');

        if (! $pendingLogin || empty($pendingLogin['user_id'])) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your login session has expired. Please login again.',
            ]);
        }

        $user = User::find($pendingLogin['user_id']);

        if (! $user) {
            $request->session()->forget('otp_login');

            return redirect()->route('login')->withErrors([
                'email' => 'User was not found. Please login again.',
            ]);
        }

        $result = $this->otpService->verifyOtp($user, (string) $request->input('otp'));

        if (! $result['ok']) {
            $this->auditService->log('otp_failed', $user, $user->email, $request, [
                'reason' => $result['reason'],
            ]);

            if (in_array($result['reason'], ['expired', 'locked', 'not_found'], true)) {
                $request->session()->forget('otp_login');

                return redirect()->route('login')->withErrors([
                    'email' => 'OTP has expired or is no longer valid. Please login again.',
                ]);
            }

            return back()->withErrors([
                'otp' => 'Invalid OTP code. Please try again.',
            ]);
        }

        Auth::login($user, (bool) ($pendingLogin['remember'] ?? false));
        $request->session()->regenerate();
        $request->session()->forget('otp_login');

        $user->forceFill([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
        ])->save();

        $this->auditService->log('otp_verified', $user, $user->email, $request);
        $this->auditService->log('login_success', $user, $user->email, $request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request): RedirectResponse
    {
        $pendingLogin = $request->session()->get('otp_login');

        if (! $pendingLogin || empty($pendingLogin['user_id'])) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your login session has expired. Please login again.',
            ]);
        }

        $user = User::find($pendingLogin['user_id']);

        if (! $user) {
            $request->session()->forget('otp_login');

            return redirect()->route('login')->withErrors([
                'email' => 'User was not found. Please login again.',
            ]);
        }

        $this->otpService->issueOtp($user, $request);

        $request->session()->put('otp_login.requested_at', now()->toIso8601String());

        $this->auditService->log('otp_sent', $user, $user->email, $request, [
            'resend' => true,
        ]);

        return back()->with('status', 'otp_resent');
    }
}
