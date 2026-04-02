<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\StudySessionController;
use App\Http\Controllers\StudySessionActionsController;
use App\Http\Controllers\StudyHistoryController;
use App\Services\QueueHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Study Sessions
    Route::get('/study-sessions/create', [StudySessionController::class, 'create'])->name('study_sessions.create');
    Route::post('/study-sessions', [StudySessionController::class, 'store'])->name('study_sessions.store');
    Route::get('/study-sessions/{studySession}', [StudySessionController::class, 'show'])->name('study_sessions.show');
    Route::post('/study-sessions/{studySession}/review', [StudySessionActionsController::class, 'markReviewed'])->name('study_sessions.review');
    Route::post('/study-sessions/{studySession}/bookmark', [StudySessionActionsController::class, 'toggleBookmark'])->name('study_sessions.bookmark');
    Route::post('/study-sessions/{studySession}/pin', [StudySessionActionsController::class, 'togglePin'])->name('study_sessions.pin');
    Route::put('/study-sessions/{studySession}/tags', [StudySessionActionsController::class, 'updateTags'])->name('study_sessions.tags');
    Route::post('/study-sessions/{studySession}/regenerate', [StudySessionActionsController::class, 'regenerateSection'])->name('study_sessions.regenerate');
    Route::get('/study-sessions/{studySession}/generation-status', [StudySessionActionsController::class, 'generationStatus'])->name('study_sessions.generation_status');

    // Chat Tutor
    Route::get('/study-sessions/{studySession}/chat', [\App\Http\Controllers\ChatController::class, 'getHistory'])->name('chat.history');
    Route::post('/study-sessions/{studySession}/chat', [\App\Http\Controllers\ChatController::class, 'sendMessage'])
        ->middleware('throttle:chat-send')
        ->name('chat.send');

    // History
    Route::get('/history', [StudyHistoryController::class, 'index'])->name('history.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleLoginController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::get('/health/queue', function (Request $request, QueueHealthService $queueHealthService) {
    $expectedToken = (string) env('QUEUE_HEALTH_TOKEN', '');
    if ($expectedToken !== '') {
        $provided = (string) $request->query('token', '');
        abort_unless(hash_equals($expectedToken, $provided), 403);
    }

    return response()->json([
        'success' => true,
        'queue' => $queueHealthService->snapshot(),
    ]);
})->name('health.queue');

require __DIR__.'/auth.php';
