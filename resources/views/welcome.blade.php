<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudyForge - AI Study Companion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-800 bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-gray-100 py-4 px-6 sm:px-12 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <svg viewBox="0 0 24 24" class="h-8 w-8 text-primary" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="text-xl font-bold text-gray-900 tracking-tight">StudyForge</span>
            </div>
            <nav class="flex items-center space-x-6">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-gray-700 hover:text-primary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-primary">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark">Get Started Free</a>
                        @endif
                    @endauth
                @endif
            </nav>
        </header>

        <main class="flex-grow flex flex-col items-center justify-center py-20 px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 tracking-tight leading-tight mb-6 max-w-3xl">
                Transform Your Study Materials into <span class="text-primary">Learning Tools</span>
            </h1>
            <p class="mt-4 text-xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
                Paste your notes, get summaries, flashcards, and quizzes instantly. Master any subject with your AI-powered study companion.
            </p>
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                @if (Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow text-white bg-primary hover:bg-primary-dark transition-colors">
                    Get Started Free
                </a>
                @endif
                <a href="#how-it-works-section" class="scroll-smooth inline-flex items-center justify-center px-8 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    How it works
                </a>
            </div>

            <div class="mt-32 max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-12 text-left">
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-blue-100 text-primary rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Instant Summaries</h3>
                    <p class="text-gray-600">Get concise overviews of your notes. Focus on what really matters instead of reading endlessly.</p>
                </div>
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-100 text-accent rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Smart Flashcards</h3>
                    <p class="text-gray-600">Train active recall automatically. We'll generate essential Q&A cards to help you memorize key concepts.</p>
                </div>
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-green-100 text-success rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Practice Quizzes</h3>
                    <p class="text-gray-600">Test your knowledge before the real exam. AI-generated multiple choice questions with explanations.</p>
                </div>
            </div>

            <section id="how-it-works-section" class="mt-20 w-full max-w-4xl rounded-2xl border border-gray-200 bg-white p-8 text-left shadow-sm">
                <h2 class="text-3xl font-bold text-gray-900">How StudyForge Works</h2>
                <ol class="mt-5 list-decimal space-y-3 pl-6 text-lg text-gray-700">
                    <li>Paste or upload your study material in a new session.</li>
                    <li>StudyForge generates summaries, flashcards, and quizzes in one workflow.</li>
                    <li>Review daily and use Chat Tutor to deepen understanding.</li>
                </ol>
            </section>
        </main>

        <x-public-footer
            wrapperClass="border-t border-gray-200 bg-white py-12 dark:border-slate-700 dark:bg-slate-900"
            containerClass="mx-auto flex w-full max-w-7xl flex-col gap-4 px-6 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-12"
            brandClass="text-2xl font-bold tracking-tight text-gray-900 dark:text-slate-100"
            navClass="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-medium uppercase tracking-widest text-gray-500 dark:text-slate-300"
            yearClass="text-sm text-gray-500 dark:text-slate-300"
            yearLabel="StudyForge. All rights reserved."
        />
    </div>
</body>
</html>
