<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StudyForge') }} - Student Access Guide</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto w-full max-w-4xl px-6 py-8 sm:px-10 sm:py-10">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-6 dark:border-slate-700">
            <a href="{{ url('/') }}" class="text-3xl font-bold tracking-tight text-indigo-700 dark:text-indigo-300">StudyForge</a>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-100 dark:hover:bg-slate-800">Back to Login</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white transition hover:bg-indigo-500">Create Account</a>
                @endif
            </div>
        </header>

        <main class="space-y-6 py-8">
            <section class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-6 dark:border-indigo-400/30 dark:bg-indigo-900/25">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:text-indigo-300">Student Help</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 sm:text-4xl">Student Login and API Usage Guide</h1>
                <p class="mt-3 text-sm text-slate-700 dark:text-slate-200 sm:text-base">
                    This guide explains how to log in successfully and what usage limits protect StudyForge from abuse.
                </p>
            </section>

            <section id="login-steps" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">How To Log In</h2>
                <ol class="mt-4 list-decimal space-y-3 pl-6 text-sm leading-6 text-slate-700 dark:text-slate-200">
                    <li>Open the login page and choose either <span class="font-semibold">Sign in with Google</span> or email and password.</li>
                    <li>If you use email login, enter your school email and password, then click <span class="font-semibold">Sign In</span>.</li>
                    <li>Check your email for a 6-digit OTP code and submit it on the verification page.</li>
                    <li>If you do not receive the code immediately, wait for the timer to finish, then use <span class="font-semibold">Resend Code</span>.</li>
                </ol>
            </section>

            <section id="api-limits" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Current Safety Limits</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">These limits protect accounts and keep the app stable for everyone.</p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                <th class="py-3 pr-4">Action</th>
                                <th class="py-3 pr-4">Limit</th>
                                <th class="py-3">What Happens</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-700 dark:text-slate-200">
                            <tr>
                                <td class="py-3 pr-4 font-medium">Email login attempts</td>
                                <td class="py-3 pr-4">5 failed attempts</td>
                                <td class="py-3">Login is temporarily locked. Wait, then try again with correct credentials.</td>
                            </tr>
                            <tr>
                                <td class="py-3 pr-4 font-medium">OTP verification submissions</td>
                                <td class="py-3 pr-4">20 attempts per 10 minutes</td>
                                <td class="py-3">OTP form is rate limited for a short cooldown period.</td>
                            </tr>
                            <tr>
                                <td class="py-3 pr-4 font-medium">OTP resend requests</td>
                                <td class="py-3 pr-4">3 requests per 15 minutes</td>
                                <td class="py-3">You must wait before requesting another OTP email.</td>
                            </tr>
                            <tr>
                                <td class="py-3 pr-4 font-medium">Chat Tutor messages</td>
                                <td class="py-3 pr-4">20 messages per minute</td>
                                <td class="py-3">The API returns a temporary message limit error. Retry after one minute.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Tips To Avoid Limits</h2>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-6 text-slate-700 dark:text-slate-200">
                    <li>Use password managers to avoid repeated failed login attempts.</li>
                    <li>Wait for OTP countdown timers before requesting another code.</li>
                    <li>Ask clear, complete questions in Chat Tutor so you need fewer retries.</li>
                    <li>If you hit a limit, wait for the cooldown window, then continue normally.</li>
                </ul>
            </section>
        </main>

        <x-public-footer
            wrapperClass="border-t border-slate-200 pt-6 dark:border-slate-700"
            containerClass="flex flex-col gap-4 text-sm sm:flex-row sm:items-center sm:justify-between"
            brandClass="text-2xl font-bold tracking-tight text-indigo-700 dark:text-indigo-300"
            navClass="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-medium uppercase tracking-widest text-slate-500 dark:text-slate-300"
            yearClass="text-xs text-slate-500 dark:text-slate-300"
            yearLabel="StudyForge. Student Access Guide."
        />
    </div>
</body>
</html>
