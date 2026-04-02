<x-app-layout>
    <x-slot name="header">
        <div class="sf-dashboard-hero">
            <div class="min-w-0">
                <h1 class="sf-dashboard-hero-title">
                    {{ ($greeting['salutation'] ?? 'Hello') . ', ' . \Illuminate\Support\Str::title(Auth::user()->name) }}
                </h1>
                <p class="sf-dashboard-hero-subtitle">{{ $greeting['message'] ?? 'Scan your progress and start your next focused session.' }}</p>
            </div>

            <a href="{{ route('study_sessions.create') }}" data-nav-loading class="sf-btn sf-btn-primary sf-btn-hero w-full sm:w-auto">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 5v14m7-7H5"></path>
                </svg>
                Start New Session
            </a>
        </div>
    </x-slot>

    @php
        $streakUnit = $currentStreak === 1 ? 'day' : 'days';
        $longestStreakUnit = $longestStreak === 1 ? 'day' : 'days';
        $dueUnit = $dueReviewsCount === 1 ? 'review' : 'reviews';
        $bookmarkedUnit = $bookmarkedCount === 1 ? 'session' : 'sessions';
        $sessionsUnit = $totalSessions === 1 ? 'session' : 'sessions';
        $flashcardsUnit = $totalFlashcards === 1 ? 'card' : 'cards';
        $quizzesUnit = $totalQuizzes === 1 ? 'quiz' : 'quizzes';
        $dueToneClass = $dueReviewsCount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300';
        $dueBadgeClass = $dueReviewsCount > 0
            ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200'
            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200';

        $trendSeries = [
            'streak' => $kpiTrends['streak'] ?? [0, 0, 0, 0, 0, 0, 0],
            'activity' => $kpiTrends['activity'] ?? [0, 0, 0, 0, 0, 0, 0],
            'due' => $kpiTrends['due'] ?? [0, 0, 0, 0, 0, 0, 0],
            'bookmarked' => $kpiTrends['bookmarked'] ?? [0, 0, 0, 0, 0, 0, 0],
        ];

        $buildSparklinePath = function (array $series): string {
            if (count($series) < 2) {
                return 'M0,26 L100,26';
            }

            $max = max($series);
            $max = $max > 0 ? $max : 1;
            $step = 100 / (count($series) - 1);
            $points = [];

            foreach ($series as $index => $value) {
                $x = $index * $step;
                $y = 26 - (($value / $max) * 20);
                $points[] = number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
            }

            return 'M'.implode(' L', $points);
        };

        $trendDelta = function (array $series): int {
            if (count($series) < 2) {
                return 0;
            }

            return (int) end($series) - (int) prev($series);
        };
    @endphp

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <section aria-labelledby="kpi-heading" class="space-y-6">
                <h2 id="kpi-heading" class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-slate-100">Progress Overview</h2>

                <div class="grid grid-cols-12 gap-6">
                    <article class="sf-dashboard-card sf-kpi-card col-span-12 sm:col-span-6 xl:col-span-3">
                        <div class="flex items-center justify-between">
                            <p class="sf-kpi-label">Current Streak</p>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-200" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3c.8 2.8 2.2 4.4 4.3 5 2.6.7 4.2 3 4.2 5.9A6.5 6.5 0 0 1 14 20.4h-4A6.5 6.5 0 0 1 3.5 14c0-2.9 1.6-5.2 4.2-5.9C9.8 7.5 11.2 5.8 12 3Z"></path>
                                </svg>
                            </span>
                        </div>
                        <p class="sf-kpi-value text-orange-600 dark:text-orange-200 mt-1">{{ $currentStreak }}</p>
                        <p class="sf-kpi-meta">{{ $streakUnit }} in a row</p>
                        <div class="mt-3 sf-kpi-sparkline">
                            <svg viewBox="0 0 100 30" class="h-11 w-full" role="img" aria-label="Streak trend over seven days">
                                <path d="{{ $buildSparklinePath($trendSeries['streak']) }}" fill="none" stroke="currentColor" stroke-width="2.25" class="text-orange-500 dark:text-orange-300"></path>
                            </svg>
                        </div>
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Longest streak: <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $longestStreak }} {{ $longestStreakUnit }}</span></p>
                    </article>

                    <article class="sf-dashboard-card sf-kpi-card col-span-12 sm:col-span-6 xl:col-span-3">
                        <div class="flex items-center justify-between">
                            <p class="sf-kpi-label">Weekly Progress</p>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-200" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M7 6v12m5-8v8m5-5v5"></path>
                                </svg>
                            </span>
                        </div>
                        <p class="sf-kpi-value text-sky-700 dark:text-sky-200 mt-1">{{ $weeklyProgressPercent }}%</p>
                        <p class="sf-kpi-meta">{{ $sessionsThisWeek }} of {{ $goal->weekly_session_target }} sessions</p>
                        <div class="mt-3 sf-kpi-sparkline">
                            <svg viewBox="0 0 100 30" class="h-11 w-full" role="img" aria-label="Weekly activity trend over seven days">
                                <path d="{{ $buildSparklinePath($trendSeries['activity']) }}" fill="none" stroke="currentColor" stroke-width="2.25" class="text-sky-500 dark:text-sky-300"></path>
                            </svg>
                        </div>
                        <div class="mt-3 h-2.5 w-full rounded-full bg-sky-100 dark:bg-sky-500/15 overflow-hidden" role="progressbar" aria-label="Weekly progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $weeklyProgressPercent }}">
                            <div class="h-full rounded-full bg-sky-500 transition-all duration-500" style="width: {{ $weeklyProgressPercent }}%"></div>
                        </div>
                    </article>

                    <article class="sf-dashboard-card sf-kpi-card col-span-12 sm:col-span-6 xl:col-span-3">
                        <div class="flex items-center justify-between">
                            <p class="sf-kpi-label">Due Reviews</p>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $dueReviewsCount > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200' }}" aria-hidden="true">
                                @if($dueReviewsCount > 0)
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v4m0 4h.01M4.9 18h14.2c1.2 0 2-1.3 1.3-2.3L13.3 4.8a1.5 1.5 0 0 0-2.6 0L3.6 15.7C2.9 16.7 3.7 18 4.9 18Z"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m5 13 4 4L19 7"></path>
                                    </svg>
                                @endif
                            </span>
                        </div>
                        @if($dueReviewsCount > 0)
                            <p class="sf-kpi-value {{ $dueToneClass }} mt-1">{{ $dueReviewsCount }}</p>
                            <p class="sf-kpi-meta">{{ $dueUnit }} right now</p>
                        @else
                            <p class="mt-2 text-2xl font-extrabold text-emerald-700 dark:text-emerald-200">All caught up!</p>
                            <p class="sf-kpi-meta">No reviews due right now</p>
                        @endif
                        <div class="mt-3 sf-kpi-sparkline">
                            <svg viewBox="0 0 100 30" class="h-11 w-full" role="img" aria-label="Due reviews trend over seven days">
                                <path d="{{ $buildSparklinePath($trendSeries['due']) }}" fill="none" stroke="currentColor" stroke-width="2.25" class="text-amber-500 dark:text-amber-300"></path>
                            </svg>
                        </div>
                        <span class="mt-3 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $dueBadgeClass }}">
                            {{ $dueReviewsCount > 0 ? 'Action needed' : 'All clear' }}
                        </span>
                    </article>

                    <article class="sf-dashboard-card sf-kpi-card col-span-12 sm:col-span-6 xl:col-span-3">
                        <div class="flex items-center justify-between">
                            <p class="sf-kpi-label">Bookmarked</p>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 4h12a1 1 0 0 1 1 1v15l-7-4-7 4V5a1 1 0 0 1 1-1Z"></path>
                                </svg>
                            </span>
                        </div>
                        <p class="sf-kpi-value text-emerald-700 dark:text-emerald-200 mt-1">{{ $bookmarkedCount }}</p>
                        <p class="sf-kpi-meta">Saved {{ $bookmarkedUnit }}</p>
                        <div class="mt-3 sf-kpi-sparkline">
                            <svg viewBox="0 0 100 30" class="h-11 w-full" role="img" aria-label="Bookmarked sessions trend over seven days">
                                <path d="{{ $buildSparklinePath($trendSeries['bookmarked']) }}" fill="none" stroke="currentColor" stroke-width="2.25" class="text-emerald-500 dark:text-emerald-300"></path>
                            </svg>
                        </div>
                        @php $bookmarkDelta = $trendDelta($trendSeries['bookmarked']); @endphp
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ $bookmarkDelta >= 0 ? '+' : '' }}{{ $bookmarkDelta }} from yesterday</p>
                    </article>
                </div>
            </section>

            <section aria-labelledby="summary-stats-heading" class="space-y-6">
                <h2 id="summary-stats-heading" class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-slate-100">Library Snapshot</h2>
                <div class="grid grid-cols-12 gap-6">
                    <article class="sf-dashboard-card col-span-12 md:col-span-4 flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200" aria-hidden="true">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a2 2 0 0 1 2-2h5.5a2 2 0 0 1 1.4.6l5.5 5.5a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Z"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="sf-stat-label">Total Sessions</p>
                            <p class="sf-stat-value">{{ $totalSessions ?? 0 }}</p>
                            <p class="sf-stat-meta">{{ $sessionsUnit }} created</p>
                        </div>
                    </article>

                    <article class="sf-dashboard-card col-span-12 md:col-span-4 flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-200" aria-hidden="true">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7.5 12 4l9 3.5L12 11 3 7.5Zm0 5 9 3.5 9-3.5"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="sf-stat-label">Flashcards</p>
                            <p class="sf-stat-value">{{ $totalFlashcards ?? 0 }}</p>
                            <p class="sf-stat-meta">{{ $flashcardsUnit }} generated</p>
                        </div>
                    </article>

                    <article class="sf-dashboard-card col-span-12 md:col-span-4 flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200" aria-hidden="true">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m9 12 2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="sf-stat-label">Quizzes</p>
                            <p class="sf-stat-value">{{ $totalQuizzes ?? 0 }}</p>
                            <p class="sf-stat-meta">{{ $quizzesUnit }} attempted</p>
                        </div>
                    </article>
                </div>
            </section>

            <section aria-labelledby="recent-sessions-heading" class="space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 id="recent-sessions-heading" class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-slate-100">Recent Sessions</h2>
                    <a href="{{ route('history.index') }}" data-nav-loading class="sf-btn sf-btn-secondary min-h-11">View Full History</a>
                </div>

                @if($recentSessions->isNotEmpty())
                    <div class="grid grid-cols-12 gap-6">
                        @foreach($recentSessions as $session)
                            <article class="sf-dashboard-card col-span-12 md:col-span-6 xl:col-span-4 flex h-full flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 line-clamp-1" title="{{ $session->title ?? 'Study Session' }}">{{ $session->title ?? 'Study Session' }}</h3>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Created {{ $session->created_at->format('M d, Y') }} · {{ $session->created_at->diffForHumans() }}</p>
                                    </div>
                                    <a href="{{ route('study_sessions.show', $session->id) }}" data-nav-loading class="sf-btn sf-btn-ghost text-xs px-3 py-2">Open session</a>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2" aria-label="Session badges">
                                    @if($session->is_pinned)
                                        <span class="sf-pill-warning">Pinned</span>
                                    @endif
                                    @if($session->is_bookmarked)
                                        <span class="sf-pill-success">Bookmarked</span>
                                    @endif
                                    @if($session->next_review_at && $session->next_review_at->isPast())
                                        <span class="sf-pill-warning">Review due</span>
                                    @endif
                                </div>

                                <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300 line-clamp-3 break-words whitespace-pre-wrap">
                                    {{ Str::limit($session->extracted_text ?? $session->input_text, 150) }}
                                </p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($session->tags as $tag)
                                        <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-700 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200">#{{ $tag->name }}</span>
                                    @endforeach
                                </div>

                                <div class="mt-auto pt-5 border-t border-slate-100 dark:border-slate-800">
                                    <div class="flex items-center gap-2 overflow-x-auto" role="toolbar" aria-label="Session quick actions">
                                        <a href="{{ route('study_sessions.show', ['studySession' => $session->id, 'tab' => 'summary']) }}" data-nav-loading class="sf-icon-action group relative" aria-label="Open summary tab" title="Open Summary">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 4h7l5 5v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm8 0v5h5"></path>
                                            </svg>
                                            <span class="sf-tooltip">Summary</span>
                                        </a>
                                        <a href="{{ route('study_sessions.show', ['studySession' => $session->id, 'tab' => 'flashcards']) }}" data-nav-loading class="sf-icon-action group relative" aria-label="Open flashcards tab" title="Open Flashcards">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7.5 12 4l8 3.5-8 3.5L4 7.5Zm0 5 8 3.5 8-3.5"></path>
                                            </svg>
                                            <span class="sf-tooltip">Flashcards</span>
                                        </a>
                                        <a href="{{ route('study_sessions.show', ['studySession' => $session->id, 'tab' => 'quiz']) }}" data-nav-loading class="sf-icon-action group relative" aria-label="Open quiz tab" title="Open Quiz">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m9 12 2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                                            </svg>
                                            <span class="sf-tooltip">Quiz</span>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="sf-dashboard-card flex flex-col items-center justify-center p-12 sm:p-16 text-center">
                        <div class="h-16 w-16 rounded-2xl bg-primary/10 dark:bg-indigo-500/20 text-primary dark:text-indigo-200 flex items-center justify-center mb-5" aria-hidden="true">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 5v14m7-7H5"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-2">No sessions yet</h3>
                        <p class="text-slate-600 dark:text-slate-300 mb-8 max-w-md">Create your first study session to generate summaries, flashcards, quizzes, and guided tutor chat from your own material.</p>
                        <a href="{{ route('study_sessions.create') }}" data-nav-loading class="sf-btn sf-btn-primary min-h-11">
                            Create Session
                        </a>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
