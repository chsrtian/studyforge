<?php

namespace App\Services;

use App\Models\GeneratedOutput;
use App\Models\StudySession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StudyMaterialGenerationService
{
    private const AI_CACHE_SCHEMA_VERSION = 'v3-grounded-content';
    private const MIN_ITEMS = 15;
    private const MAX_ITEMS = 50;
    private const SUMMARY_SOURCE_LIMIT = 22000;
    private const SECTION_SOURCE_LIMIT = 18000;
    private const AI_CACHE_TTL_SECONDS = 1800;

    public function __construct(
        protected AiService $aiService,
        protected ContentProcessor $contentProcessor
    ) {
    }

    public function generateForSession(StudySession $session, string $text, array $preferences = []): void
    {
        $this->generateSummaryForSession($session, $text);
        $this->generateFlashcardsForSession($session, $text, $preferences['flashcards'] ?? []);
        $this->generateQuizForSession($session, $text, $preferences['quiz'] ?? []);
    }

    public function generateSummaryForSession(StudySession $session, string $text): void
    {
        $source = $this->buildFocusedSource($text, self::SUMMARY_SOURCE_LIMIT);
        $cacheKey = $this->cacheKey('summary', [$source]);

        $summary = Cache::remember($cacheKey, self::AI_CACHE_TTL_SECONDS, function () use ($source) {
            return $this->aiService->generateSummary($source);
        });

        GeneratedOutput::updateOrCreate(
            [
                'study_session_id' => $session->id,
                'type' => 'summary',
            ],
            [
                'content' => ['markdown' => $summary],
            ]
        );
    }

    public function generateFlashcardsForSession(StudySession $session, string $text, array $preferences = []): void
    {
        $difficulty = $this->normalizeDifficulty((string) ($preferences['difficulty'] ?? data_get($session->metadata, 'generation_preferences.flashcards.difficulty', 'average')));
        $targetCount = $this->normalizeCount((int) ($preferences['count'] ?? data_get($session->metadata, 'generation_preferences.flashcards.count', self::MIN_ITEMS)));

        $cards = $this->generateFlashcardsFast($text, $difficulty, $targetCount);

        $session->flashcards()->delete();

        if (empty($cards)) {
            return;
        }

        $difficultyForStorage = $this->difficultyForStorage($difficulty);
        $now = now();
        $rows = [];

        foreach ($cards as $index => $card) {
            $rows[] = [
                'study_session_id' => $session->id,
                'question' => $card['question'],
                'answer' => $card['answer'],
                'order' => $index,
                'difficulty' => $difficultyForStorage,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('flashcards')->insert($rows);
    }

    public function generateQuizForSession(StudySession $session, string $text, array $preferences = []): void
    {
        $difficulty = $this->normalizeDifficulty((string) ($preferences['difficulty'] ?? data_get($session->metadata, 'generation_preferences.quiz.difficulty', 'average')));
        $targetCount = $this->normalizeCount((int) ($preferences['count'] ?? data_get($session->metadata, 'generation_preferences.quiz.count', self::MIN_ITEMS)));

        $questions = $this->generateQuizFast($text, $difficulty, $targetCount);

        $session->quizzes()->delete();

        if (empty($questions)) {
            return;
        }

        $quizDifficultyForStorage = $this->difficultyForStorage($difficulty);

        DB::transaction(function () use ($session, $difficulty, $questions, $quizDifficultyForStorage): void {
            $quiz = $session->quizzes()->create([
                'title' => 'Quiz for '.$session->title,
                'description' => ucfirst($difficulty).' difficulty quiz',
                'total_questions' => count($questions),
            ]);

            $now = now();
            $rows = [];

            foreach ($questions as $index => $question) {
                $rows[] = [
                    'quiz_id' => $quiz->id,
                    'question' => $question['question'],
                    'options' => json_encode(array_slice($question['options'], 0, 4), JSON_THROW_ON_ERROR),
                    'correct_answer' => $question['correct_answer'],
                    'explanation' => $question['explanation'] ?? null,
                    'order' => $index,
                    'difficulty' => $quizDifficultyForStorage,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('quiz_questions')->insert($rows);
        });
    }

    public function regenerateFlashcardsForSession(StudySession $session, string $text, array $preferences): void
    {
        $this->generateFlashcardsForSession($session, $text, $preferences);
    }

    public function regenerateQuizForSession(StudySession $session, string $text, array $preferences): void
    {
        $this->generateQuizForSession($session, $text, $preferences);
    }

    protected function generateFlashcardsFast(string $text, string $difficulty, int $targetCount): array
    {
        $cardsByKey = [];
        $primarySource = $this->buildFocusedSource($text, self::SECTION_SOURCE_LIMIT);

        $result = $this->cachedFlashcardCall($primarySource, $difficulty, $targetCount);
        $this->collectFlashcards($cardsByKey, $result['cards'] ?? []);

        if (count($cardsByKey) < $targetCount && mb_strlen($text) > mb_strlen($primarySource)) {
            $remainingRequestCount = $this->normalizeCount(max(self::MIN_ITEMS, $targetCount - count($cardsByKey) + 5));
            $alternateSource = $this->buildFocusedSource($text, self::SECTION_SOURCE_LIMIT, true);
            $fallbackResult = $this->cachedFlashcardCall($alternateSource, $difficulty, $remainingRequestCount);
            $this->collectFlashcards($cardsByKey, $fallbackResult['cards'] ?? []);
        }

        return array_slice(array_values($cardsByKey), 0, $targetCount);
    }

    protected function generateQuizFast(string $text, string $difficulty, int $targetCount): array
    {
        $questionsByKey = [];
        $primarySource = $this->buildFocusedSource($text, self::SECTION_SOURCE_LIMIT);

        $result = $this->cachedQuizCall($primarySource, $difficulty, $targetCount);
        $this->collectQuizQuestions($questionsByKey, $result['questions'] ?? []);

        if (count($questionsByKey) < $targetCount && mb_strlen($text) > mb_strlen($primarySource)) {
            $remainingRequestCount = $this->normalizeCount(max(self::MIN_ITEMS, $targetCount - count($questionsByKey) + 5));
            $alternateSource = $this->buildFocusedSource($text, self::SECTION_SOURCE_LIMIT, true);
            $fallbackResult = $this->cachedQuizCall($alternateSource, $difficulty, $remainingRequestCount);
            $this->collectQuizQuestions($questionsByKey, $fallbackResult['questions'] ?? []);
        }

        return array_slice(array_values($questionsByKey), 0, $targetCount);
    }

    private function normalizeDifficulty(string $difficulty): string
    {
        return in_array($difficulty, ['easy', 'average', 'hard'], true) ? $difficulty : 'average';
    }

    private function difficultyForStorage(string $difficulty): string
    {
        return $difficulty === 'average' ? 'medium' : $difficulty;
    }

    private function normalizeCount(int $count): int
    {
        if ($count < self::MIN_ITEMS) {
            return self::MIN_ITEMS;
        }

        if ($count > self::MAX_ITEMS) {
            return self::MAX_ITEMS;
        }

        return $count;
    }

    private function buildFocusedSource(string $text, int $maxChars, bool $alternate = false): string
    {
        $clean = trim($text);
        if ($clean === '') {
            return '';
        }

        if (mb_strlen($clean) <= $maxChars) {
            return $clean;
        }

        $segmentLength = max(1, intdiv($maxChars, 3));
        $textLength = mb_strlen($clean);

        if (! $alternate) {
            $head = mb_substr($clean, 0, $segmentLength);
            $middleStart = max(0, intdiv($textLength, 2) - intdiv($segmentLength, 2));
            $middle = mb_substr($clean, $middleStart, $segmentLength);
            $tail = mb_substr($clean, max(0, $textLength - $segmentLength), $segmentLength);

            return trim($head."\n\n...\n\n".$middle."\n\n...\n\n".$tail);
        }

        $firstQuarterStart = max(0, intdiv($textLength, 4) - intdiv($segmentLength, 2));
        $thirdQuarterStart = max(0, intdiv($textLength * 3, 4) - intdiv($segmentLength, 2));
        $left = mb_substr($clean, $firstQuarterStart, $segmentLength);
        $center = mb_substr($clean, max(0, intdiv($textLength, 2) - intdiv($segmentLength, 2)), $segmentLength);
        $right = mb_substr($clean, $thirdQuarterStart, $segmentLength);

        return trim($left."\n\n...\n\n".$center."\n\n...\n\n".$right);
    }

    private function cachedFlashcardCall(string $source, string $difficulty, int $itemCount): array
    {
        $cacheKey = $this->cacheKey('flashcards', [$source, $difficulty, $itemCount]);

        return Cache::remember($cacheKey, self::AI_CACHE_TTL_SECONDS, function () use ($source, $difficulty, $itemCount) {
            return $this->aiService->generateFlashcards($source, $difficulty, $itemCount);
        });
    }

    private function cachedQuizCall(string $source, string $difficulty, int $itemCount): array
    {
        $cacheKey = $this->cacheKey('quiz', [$source, $difficulty, $itemCount]);

        return Cache::remember($cacheKey, self::AI_CACHE_TTL_SECONDS, function () use ($source, $difficulty, $itemCount) {
            return $this->aiService->generateQuiz($source, $difficulty, $itemCount);
        });
    }

    private function collectFlashcards(array &$cardsByKey, array $cards): void
    {
        foreach ($cards as $card) {
            $question = trim((string) ($card['question'] ?? ''));
            $answer = trim((string) ($card['answer'] ?? ''));

            if ($question === '' || $answer === '') {
                continue;
            }

            if ($this->looksLikePlaceholder($question) || $this->looksLikePlaceholder($answer)) {
                continue;
            }

            $cardsByKey[strtolower($question.'|'.$answer)] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }
    }

    private function collectQuizQuestions(array &$questionsByKey, array $questions): void
    {
        foreach ($questions as $questionData) {
            $question = trim((string) ($questionData['question'] ?? ''));
            $options = array_values($questionData['options'] ?? []);

            if ($question === '' || count($options) < 4) {
                continue;
            }

            $normalizedOptions = array_slice(array_map(fn ($option) => trim((string) $option), $options), 0, 4);
            if (in_array('', $normalizedOptions, true)) {
                continue;
            }

            if ($this->looksLikePlaceholder($question) || $this->looksLikePlaceholder(implode(' ', $normalizedOptions))) {
                continue;
            }

            $correctAnswer = strtoupper((string) ($questionData['correct_answer'] ?? 'A'));
            if (! in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
                $matchedIndex = array_search((string) ($questionData['correct_answer'] ?? ''), $normalizedOptions, true);
                $correctAnswer = $matchedIndex === false ? 'A' : chr(65 + $matchedIndex);
            }

            $questionsByKey[strtolower($question)] = [
                'question' => $question,
                'options' => $normalizedOptions,
                'correct_answer' => $correctAnswer,
                'explanation' => isset($questionData['explanation']) ? trim((string) $questionData['explanation']) : null,
            ];
        }
    }

    private function cacheKey(string $type, array $parts): string
    {
        return 'study-forge:ai:'.self::AI_CACHE_SCHEMA_VERSION.':'.$type.':'.hash('sha256', json_encode($parts, JSON_THROW_ON_ERROR));
    }

    private function looksLikePlaceholder(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return str_contains($normalized, '[hard]')
            || str_contains($normalized, '[easy]')
            || str_contains($normalized, '[average]')
            || str_contains($normalized, 'flashcard 1')
            || str_contains($normalized, 'question 1')
            || str_contains($normalized, 'explain this concept')
            || str_contains($normalized, 'which option best matches the study material')
            || str_contains($normalized, 'insufficient context to determine');
    }
}
