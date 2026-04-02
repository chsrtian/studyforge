<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        if ($this->missingGoogleConfigKeys() !== []) {
            return $this->googleMisconfigurationResponse('redirect');
        }

        return $this->googleProvider()->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        if ($this->missingGoogleConfigKeys() !== []) {
            return $this->googleMisconfigurationResponse('callback');
        }

        if ($this->missingGoogleSchemaColumns() !== []) {
            return $this->googleSchemaMismatchResponse();
        }

        try {
            $googleUser = $this->googleProvider()->user();
        } catch (\Throwable $exception) {
            Log::warning('Google OAuth callback failed.', [
                'error' => $exception->getMessage(),
            ]);

            $errorMessage = 'Google sign-in could not be completed. Please try again.';

            if (app()->isLocal()) {
                $errorMessage = 'Google callback failed: '.$exception->getMessage();
            }

            return redirect()->route('login')->withErrors([
                'email' => $errorMessage,
            ]);
        }

        $email = Str::lower((string) $googleUser->getEmail());

        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Google did not return an email address for this account.',
            ]);
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        $profileName = $googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($email, '@');
        $avatarUrl = $googleUser->getAvatar();

        if ($user) {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'name' => $user->name ?: $profileName,
                'email' => $email,
                'avatar_url' => $avatarUrl ?: $user->avatar_url,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $profileName,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $avatarUrl,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(40)),
            ]);
        }

        Auth::login($user, true);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function googleProvider()
    {
        return Socialite::driver('google')->redirectUrl($this->googleRedirectUri());
    }

    private function googleRedirectUri(): string
    {
        $configuredRedirect = (string) config('services.google.redirect');

        if ($configuredRedirect !== '') {
            return $configuredRedirect;
        }

        return route('auth.google.callback');
    }

    private function missingGoogleConfigKeys(): array
    {
        $missing = [];

        if (! filled(config('services.google.client_id'))) {
            $missing[] = 'GOOGLE_CLIENT_ID';
        }

        if (! filled(config('services.google.client_secret'))) {
            $missing[] = 'GOOGLE_CLIENT_SECRET';
        }

        return $missing;
    }

    private function googleMisconfigurationResponse(string $stage): RedirectResponse
    {
        $missingKeys = $this->missingGoogleConfigKeys();

        Log::error('Google OAuth misconfiguration detected.', [
            'stage' => $stage,
            'missing_keys' => $missingKeys,
            'configured_redirect' => (string) config('services.google.redirect'),
            'resolved_callback' => route('auth.google.callback'),
            'app_url' => (string) config('app.url'),
            'request_url' => request()->fullUrl(),
        ]);

        if (app()->isLocal()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google OAuth misconfigured: missing '.implode(', ', $missingKeys).'. Set them in .env and run php artisan config:clear.',
            ]);
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Google sign-in is currently unavailable. Please try again later.',
        ]);
    }

    private function missingGoogleSchemaColumns(): array
    {
        $missing = [];

        if (! Schema::hasColumn('users', 'google_id')) {
            $missing[] = 'users.google_id';
        }

        if (! Schema::hasColumn('users', 'avatar_url')) {
            $missing[] = 'users.avatar_url';
        }

        return $missing;
    }

    private function googleSchemaMismatchResponse(): RedirectResponse
    {
        $missingColumns = $this->missingGoogleSchemaColumns();

        Log::error('Google OAuth schema mismatch detected.', [
            'missing_columns' => $missingColumns,
            'migration_hint' => 'php artisan migrate --force',
        ]);

        $errorMessage = 'Google sign-in is temporarily unavailable.';

        if (app()->isLocal()) {
            $errorMessage = 'Google OAuth schema mismatch: missing '.implode(', ', $missingColumns).'. Run php artisan migrate --force.';
        }

        return redirect()->route('login')->withErrors([
            'email' => $errorMessage,
        ]);
    }
}