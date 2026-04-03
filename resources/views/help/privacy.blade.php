<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StudyForge') }} - Privacy</title>

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
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:text-indigo-300">Privacy</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Privacy Policy</h1>
                <p class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-200">
                    StudyForge protects personal data and uses it only to operate and improve your study experience.
                    We collect account details, study session content, and service logs necessary for authentication,
                    AI processing, and reliability monitoring.
                </p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-xl font-bold">Data We Process</h2>
                <ul class="mt-4 list-disc space-y-2 pl-6 text-sm leading-6 text-slate-700 dark:text-slate-200">
                    <li>Profile information such as name and email.</li>
                    <li>Study content submitted for summaries, flashcards, quizzes, and tutor chat.</li>
                    <li>Operational logs for abuse prevention, diagnostics, and performance.</li>
                </ul>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-xl font-bold">Retention and Security</h2>
                <p class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-200">
                    Data is retained only as long as required for account functionality, security, and compliance.
                    We apply access controls, transport encryption, and production safeguards to protect user information.
                </p>
            </section>
        </main>

        <x-public-footer
            wrapperClass="border-t border-slate-200 pt-6 dark:border-slate-700"
            containerClass="flex flex-col gap-4 text-sm sm:flex-row sm:items-center sm:justify-between"
            brandClass="text-2xl font-bold tracking-tight text-indigo-700 dark:text-indigo-300"
            navClass="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-medium uppercase tracking-widest text-slate-500 dark:text-slate-300"
            yearClass="text-xs text-slate-500 dark:text-slate-300"
            yearLabel="StudyForge. Privacy Policy."
        />
    </div>
</body>
</html>
