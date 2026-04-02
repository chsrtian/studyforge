<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OtpService
{
    public function issueOtp(User $user, Request $request): void
    {
        $mailer = (string) config('mail.default', 'log');
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            DB::transaction(function () use ($user, $request, $otp, $mailer): void {
                LoginOtp::where('user_id', $user->id)
                    ->whereNull('verified_at')
                    ->delete();

                LoginOtp::create([
                    'user_id' => $user->id,
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 0,
                    'max_attempts' => 5,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                Mail::mailer($mailer)->to($user->email)->send(new LoginOtpMail($user, $otp));
            });
        } catch (Throwable $exception) {
            Log::error('OTP email delivery failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mailer' => $mailer,
                'smtp_configured' => filled(config('mail.mailers.smtp.host')),
                'smtp_username_set' => filled(config('mail.mailers.smtp.username')),
                'smtp_password_set' => filled(config('mail.mailers.smtp.password')),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function verifyOtp(User $user, string $otpInput): array
    {
        $otp = LoginOtp::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return ['ok' => false, 'reason' => 'not_found'];
        }

        if (now()->greaterThan($otp->expires_at)) {
            return ['ok' => false, 'reason' => 'expired'];
        }

        if ($otp->attempts >= $otp->max_attempts) {
            return ['ok' => false, 'reason' => 'locked'];
        }

        if (! Hash::check($otpInput, $otp->otp_hash)) {
            $otp->increment('attempts');

            if ($otp->fresh()->attempts >= $otp->max_attempts) {
                return ['ok' => false, 'reason' => 'locked'];
            }

            return ['ok' => false, 'reason' => 'invalid'];
        }

        $otp->forceFill([
            'verified_at' => now(),
        ])->save();

        return ['ok' => true, 'reason' => 'verified'];
    }
}
