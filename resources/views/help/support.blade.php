<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StudyForge') }} - Support</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto w-full max-w-5xl px-6 py-8 sm:px-10">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-6 dark:border-slate-700">
            <a href="{{ url('/') }}" class="text-3xl font-bold tracking-tight text-indigo-700 dark:text-indigo-300">StudyForge</a>
            <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-100 dark:hover:bg-slate-800">Log in</a>
        </header>

        <main class="space-y-6 py-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:text-indigo-300">Support</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Support Center</h1>
                <p class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-200">
                    Get help with login, OTP verification, API limits, and study session workflows.
                    Use the resources below for quick issue resolution.
                </p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-xl font-bold">Quick Links</h2>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-6 text-slate-700 dark:text-slate-200">
                    <li><a href="{{ route('help.student-access-guide') }}" class="font-semibold text-indigo-700 hover:text-indigo-600 dark:text-indigo-300 dark:hover:text-indigo-200">Student Login and API Usage Guide</a></li>
                    <li><a href="{{ route('password.request') }}" class="font-semibold text-indigo-700 hover:text-indigo-600 dark:text-indigo-300 dark:hover:text-indigo-200">Reset Password</a></li>
                    <li><a href="{{ route('help.privacy') }}" class="font-semibold text-indigo-700 hover:text-indigo-600 dark:text-indigo-300 dark:hover:text-indigo-200">Privacy Policy</a></li>
                    <li><a href="{{ route('help.terms') }}" class="font-semibold text-indigo-700 hover:text-indigo-600 dark:text-indigo-300 dark:hover:text-indigo-200">Terms of Service</a></li>
                </ul>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-xl font-bold">Response Expectations</h2>
                <p class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-200">
                    Account and access issues are prioritized. Include your account email and a short description
                    of the problem for faster resolution through the contact details in the footer CONTACT action.
                </p>
            </section>
        </main>

        <x-public-footer
            wrapperClass="border-t border-slate-200 pt-6 dark:border-slate-700"
            containerClass="flex flex-col gap-4 text-sm sm:flex-row sm:items-center sm:justify-between"
            brandClass="text-2xl font-bold tracking-tight text-indigo-700 dark:text-indigo-300"
            navClass="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-medium uppercase tracking-widest text-slate-500 dark:text-slate-300"
            yearClass="text-xs text-slate-500 dark:text-slate-300"
            yearLabel="StudyForge. Support Center."
        />
    </div>
</body>
</html>
