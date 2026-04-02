<?php

use App\Jobs\GenerateStudySessionFlashcardsJob;
use App\Jobs\GenerateStudySessionQuizJob;
use App\Jobs\GenerateStudySessionSummaryJob;
use App\Models\SessionInputSource;
use App\Models\StudySession;
use App\Models\User;
use App\Services\QueueHealthService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('deploy:validate-env', function () {
    $errors = [];

    $required = [
        'APP_ENV',
        'APP_DEBUG',
        'APP_URL',
        'APP_KEY',
        'DB_CONNECTION',
        'QUEUE_CONNECTION',
        'SESSION_SECURE_COOKIE',
        'AI_PROVIDER',
    ];

    foreach ($required as $key) {
        if ((string) env($key, '') === '') {
            $errors[] = "Missing env variable: {$key}";
        }
    }

    if ((string) env('APP_ENV') !== 'production') {
        $errors[] = 'APP_ENV must be production.';
    }

    if (filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL)) {
        $errors[] = 'APP_DEBUG must be false.';
    }

    if ((string) env('QUEUE_CONNECTION') === 'sync') {
        $errors[] = 'QUEUE_CONNECTION must not be sync.';
    }

    if (! filter_var(env('SESSION_SECURE_COOKIE', false), FILTER_VALIDATE_BOOL)) {
        $errors[] = 'SESSION_SECURE_COOKIE must be true.';
    }

    if (filter_var(env('AI_MOCK_MODE', false), FILTER_VALIDATE_BOOL)) {
        $errors[] = 'AI_MOCK_MODE must be false.';
    }

    $provider = strtolower((string) env('AI_PROVIDER', ''));
    if ($provider === 'openai' && (string) env('OPENAI_API_KEY', '') === '') {
        $errors[] = 'OPENAI_API_KEY is required when AI_PROVIDER=openai.';
    }

    if ($provider === 'gemini' && (string) env('GEMINI_API_KEY', '') === '') {
        $errors[] = 'GEMINI_API_KEY is required when AI_PROVIDER=gemini.';
    }

    if ($errors !== []) {
        foreach ($errors as $error) {
            $this->error('[FAIL] '.$error);
        }

        return 1;
    }

    $this->info('[PASS] Environment validation passed.');

    return 0;
})->purpose('Validate production environment variables and safety flags');

Artisan::command('deploy:preflight', function () {
    $this->line('[STEP] Running environment validation...');
    if ($this->call('deploy:validate-env') !== 0) {
        return 1;
    }

    $this->line('[STEP] Running config cache checks...');
    if ($this->call('config:cache') !== 0) {
        return 1;
    }
    if ($this->call('route:cache') !== 0) {
        return 1;
    }
    if ($this->call('view:cache') !== 0) {
        return 1;
    }

    $this->line('[STEP] Running test suite...');
    if ($this->call('test') !== 0) {
        return 1;
    }

    $this->line('[STEP] Running migration safety check (--pretend)...');
    if ($this->call('migrate', ['--pretend' => true, '--force' => true]) !== 0) {
        return 1;
    }

    $this->info('[PASS] Preflight completed successfully.');

    return 0;
})->purpose('Run full pre-deploy validation gate with fail-fast behavior');

Artisan::command('deploy:smoke-test', function () {
    $this->line('[STEP] Starting deployment smoke test...');

    putenv('AI_MOCK_MODE=true');
    $_ENV['AI_MOCK_MODE'] = 'true';
    $_SERVER['AI_MOCK_MODE'] = 'true';

    $id = Str::uuid()->toString();
    $material = str_repeat('Smoke test material for queue processing and generation validation. ', 20);

    $user = User::query()->create([
        'name' => 'Deploy Smoke '.$id,
        'email' => 'deploy-smoke-'.$id.'@example.test',
        'password' => Hash::make(Str::random(32)),
        'email_verified_at' => now(),
    ]);

    $textSession = StudySession::query()->create([
        'user_id' => $user->id,
        'title' => 'Smoke Text '.$id,
        'input_text' => $material,
        'input_source_type' => 'text',
        'extracted_text' => $material,
        'metadata' => [
            'generation_status' => [
                'summary' => ['status' => 'queued', 'updated_at' => now()->toIso8601String()],
                'flashcards' => ['status' => 'queued', 'updated_at' => now()->toIso8601String()],
                'quiz' => ['status' => 'queued', 'updated_at' => now()->toIso8601String()],
            ],
        ],
        'status' => 'processing',
    ]);

    SessionInputSource::query()->create([
        'study_session_id' => $textSession->id,
        'source_type' => 'text',
        'extracted_text' => $material,
        'extraction_status' => 'success',
        'file_size_bytes' => strlen($material),
    ]);

    $pdfSession = StudySession::query()->create([
        'user_id' => $user->id,
        'title' => 'Smoke PDF '.$id,
        'input_text' => null,
        'input_source_type' => 'pdf',
        'extracted_text' => $material,
        'metadata' => [],
        'status' => 'pending',
    ]);

    SessionInputSource::query()->create([
        'study_session_id' => $pdfSession->id,
        'source_type' => 'pdf',
        'original_filename' => 'smoke-test.pdf',
        'file_path' => 'session_pdfs/smoke-test.pdf',
        'extracted_text' => $material,
        'extraction_status' => 'success',
        'file_size_bytes' => 4096,
    ]);

    GenerateStudySessionSummaryJob::dispatch($textSession->id);
    GenerateStudySessionFlashcardsJob::dispatch($textSession->id);
    GenerateStudySessionQuizJob::dispatch($textSession->id);

    for ($i = 0; $i < 4; $i++) {
        if ($this->call('queue:work', [
            '--once' => true,
            '--queue' => 'default,regeneration',
            '--sleep' => 1,
            '--tries' => 4,
            '--timeout' => 180,
        ]) !== 0) {
            $this->error('[FAIL] Queue worker command failed during smoke test.');
            return 1;
        }
    }

    $textSession->refresh();
    $summaryExists = $textSession->generatedOutputs()->where('type', 'summary')->exists();
    $flashcardCount = $textSession->flashcards()->count();
    $quizQuestionsCount = (int) optional($textSession->quizzes()->withCount('questions')->latest('id')->first())->questions_count;

    if (! $summaryExists) {
        $this->error('[FAIL] Summary generation verification failed.');
        return 1;
    }

    if ($flashcardCount <= 0) {
        $this->error('[FAIL] Flashcard generation verification failed.');
        return 1;
    }

    if ($quizQuestionsCount <= 0) {
        $this->error('[FAIL] Quiz generation verification failed.');
        return 1;
    }

    $this->info('[PASS] Text session creation: OK');
    $this->info('[PASS] PDF session creation: OK');
    $this->info('[PASS] Queue execution: OK');
    $this->info('[PASS] Database writes: OK');

    $this->line('[STEP] Queue health snapshot:');
    $health = app(QueueHealthService::class)->snapshot();
    $this->line(json_encode($health, JSON_PRETTY_PRINT));

    $this->info('[PASS] Smoke test completed.');

    return 0;
})->purpose('Run post-deploy smoke test for session creation, queue execution, and DB writes');

Artisan::command('deploy:queue-status', function () {
    $health = app(QueueHealthService::class)->snapshot();
    $this->line(json_encode($health, JSON_PRETTY_PRINT));

    if (($health['status'] ?? '') === 'degraded') {
        $this->error('[FAIL] Queue health degraded.');
        return 1;
    }

    $this->info('[PASS] Queue health is acceptable.');
    return 0;
})->purpose('Check queue worker health status');
