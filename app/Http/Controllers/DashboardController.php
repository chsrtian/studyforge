<?php

namespace App\Http\Controllers;

use App\Models\StudyGoal;
use App\Models\StudySession;
use App\Models\Flashcard;
use App\Models\Quiz;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Recent sessions
        $recentSessions = StudySession::where('user_id', $userId)
            ->with('tags')
            ->orderByDesc('is_pinned')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Stats
        $totalSessions = StudySession::where('user_id', $userId)->count();
        
        $totalFlashcards = Flashcard::whereHas('studySession', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();
        
        $totalQuizzes = Quiz::whereHas('studySession', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();

        $dueReviewsCount = StudySession::where('user_id', $userId)
            ->whereNotNull('next_review_at')
            ->where('next_review_at', '<=', now())
            ->count();

        $bookmarkedCount = StudySession::where('user_id', $userId)
            ->where('is_bookmarked', true)
            ->count();

        $goal = StudyGoal::firstOrCreate(
            ['user_id' => $userId],
            ['weekly_session_target' => 5]
        );

        $sessionsThisWeek = StudySession::where('user_id', $userId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $weeklyProgressPercent = min(100, (int) round(($sessionsThisWeek / max(1, $goal->weekly_session_target)) * 100));

        $currentStreak = (int) (Auth::user()->current_streak ?? 0);
        $longestStreak = (int) (Auth::user()->longest_streak ?? 0);
        $greeting = $this->buildGreeting($currentStreak, $userId);
        $kpiTrends = $this->buildKpiTrendSeries($userId);

        return view('dashboard', compact(
            'recentSessions',
            'totalSessions',
            'totalFlashcards',
            'totalQuizzes',
            'dueReviewsCount',
            'bookmarkedCount',
            'sessionsThisWeek',
            'weeklyProgressPercent',
            'currentStreak',
            'longestStreak',
            'goal',
            'greeting',
            'kpiTrends'
        ));
    }

    private function buildGreeting(int $currentStreak, int $userId): array
    {
        $hour = now()->hour;

        if ($hour < 12) {
            $salutation = 'Good morning';
        } elseif ($hour < 18) {
            $salutation = 'Good afternoon';
        } else {
            $salutation = 'Good evening';
        }

        if ($currentStreak >= 30) {
            $headline = "You're on a {$currentStreak}-day streak";
            $messages = [
                'This is elite consistency. Keep stacking focused sessions.',
                'Momentum is on your side. Protect the streak with one deep session today.',
                'You have built a powerful habit. Stay deliberate and keep it alive.',
            ];
        } elseif ($currentStreak >= 14) {
            $headline = "{$currentStreak} days strong";
            $messages = [
                'You are in rhythm. One high-quality review today keeps growth compounding.',
                'Great consistency so far. Focus on difficult cards to accelerate retention.',
                'Your streak is becoming a habit. Keep the loop tight and intentional.',
            ];
        } elseif ($currentStreak >= 7) {
            $headline = "You're on a {$currentStreak}-day streak";
            $messages = [
                'Keep it up. Small daily wins are turning into long-term mastery.',
                'Strong first week. Revisit due reviews to lock in what you learned.',
                'Nice momentum. Keep sessions short and focused to stay consistent.',
            ];
        } elseif ($currentStreak > 0) {
            $headline = 'Welcome back';
            $messages = [
                'You are building consistency. Show up for another focused session today.',
                'Nice start. Keep the chain alive with one meaningful review.',
                'Progress is visible. Stay steady and your retention will climb.',
            ];
        } else {
            $headline = 'Welcome back';
            $messages = [
                'Ready to study again? Start a short session and build momentum.',
                'A quick session now will make tomorrow easier. Let us get started.',
                'Kick off your next streak with one focused study block.',
            ];
        }

        $index = (now()->dayOfYear + $userId) % count($messages);

        return [
            'salutation' => $salutation,
            'headline' => $headline,
            'message' => $messages[$index],
        ];
    }

    private function buildKpiTrendSeries(int $userId): array
    {
        $end = now()->startOfDay();
        $start = (clone $end)->subDays(6);

        $sessionByDay = StudySession::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, (clone $end)->endOfDay()])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $dueByDay = StudySession::query()
            ->where('user_id', $userId)
            ->whereNotNull('next_review_at')
            ->whereBetween('next_review_at', [$start, (clone $end)->endOfDay()])
            ->selectRaw('DATE(next_review_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $bookmarkedByCreatedDay = StudySession::query()
            ->where('user_id', $userId)
            ->where('is_bookmarked', true)
            ->where('created_at', '<=', (clone $end)->endOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $activitySeries = [];
        $streakSeries = [];
        $dueSeries = [];
        $bookmarkSeries = [];
        $rollingStreak = 0;
        $runningBookmarks = 0;

        for ($offset = 0; $offset < 7; $offset++) {
            $dayKey = Carbon::parse($start)->addDays($offset)->toDateString();
            $activityCount = (int) ($sessionByDay[$dayKey] ?? 0);
            $dueCount = (int) ($dueByDay[$dayKey] ?? 0);
            $bookmarkCount = (int) ($bookmarkedByCreatedDay[$dayKey] ?? 0);

            $rollingStreak = $activityCount > 0 ? $rollingStreak + 1 : 0;
            $runningBookmarks += $bookmarkCount;

            $activitySeries[] = $activityCount;
            $streakSeries[] = $rollingStreak;
            $dueSeries[] = $dueCount;
            $bookmarkSeries[] = $runningBookmarks;
        }

        return [
            'activity' => $activitySeries,
            'streak' => $streakSeries,
            'due' => $dueSeries,
            'bookmarked' => $bookmarkSeries,
        ];
    }
}
