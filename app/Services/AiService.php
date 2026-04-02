<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private const OUTPUT_ATTEMPTS = 3;

    /**
     * Generic placeholder patterns that indicate low-quality or template output.
     *
     * @var array<int, string>
     */
    private const PLACEHOLDER_PATTERNS = [
        '/\[(easy|average|hard)\]/i',
        '/\bflashcard\s*\d+\b/i',
        '/\bquestion\s*\d+\b/i',
        '/\bexplain this concept\b/i',
        '/\bwhich option best matches the study material\b/i',
        '/\binsufficient context to determine\b/i',
        '/\bunrelated concept from a different topic\b/i',
        '/\bplaceholder\b/i',
    ];

    /**
     * Repetitive stems often returned by weak generations.
     *
     * @var array<int, string>
     */
    private const GENERIC_STEM_PATTERNS = [
        '/^what (is|does) (the|this|that)/i',
        '/^according to the (material|text), which option/i',
        '/^which option best (matches|reflects)/i',
        '/^explain (this|the) concept/i',
        '/^describe the main idea/i',
    ];

    /**
     * @var array<int, string>
     */
    private const STOP_WORDS = [
        'the', 'and', 'for', 'with', 'from', 'that', 'this', 'were', 'was', 'are', 'have', 'has',
        'your', 'their', 'into', 'about', 'because', 'which', 'while', 'where', 'when', 'what',
        'will', 'would', 'there', 'here', 'using', 'used', 'than', 'then', 'them', 'they', 'also',
        'such', 'each', 'over', 'under', 'between', 'after', 'before', 'within', 'without', 'through',
        'during', 'across', 'these', 'those', 'being', 'been', 'only', 'very', 'more', 'most', 'some',
    ];

    protected string $provider;
    protected string $apiKey;
    protected string $model;
    protected bool $mockMode;
    protected int $textTimeout;
    protected int $jsonTimeout;
    protected int $connectTimeout;
    protected int $requestRetries;
    protected int $requestRetryDelayMs;
    protected bool $fallbackOnProviderFailure;

    public function __construct()
    {
        $this->provider = strtolower((string) env('AI_PROVIDER', 'gemini'));

        if ($this->provider === 'gemini') {
            $this->apiKey = (string) config('services.gemini.api_key', '');
            $this->model = (string) config('services.gemini.model', 'gemini-2.5-flash');
            $this->textTimeout = (int) config('services.gemini.text_timeout', 30);
            $this->jsonTimeout = (int) config('services.gemini.json_timeout', 40);
            $this->connectTimeout = (int) config('services.gemini.connect_timeout', 10);
            $this->requestRetries = (int) config('services.gemini.retries', 0);
            $this->requestRetryDelayMs = (int) config('services.gemini.retry_delay_ms', 500);
        } else {
            $this->apiKey = (string) config('services.openai.api_key', '');
            $this->model = (string) config('services.openai.model', 'gpt-4o-mini');
            $this->textTimeout = (int) config('services.openai.text_timeout', 30);
            $this->jsonTimeout = (int) config('services.openai.json_timeout', 40);
            $this->connectTimeout = (int) config('services.openai.connect_timeout', 10);
            $this->requestRetries = (int) config('services.openai.retries', 0);
            $this->requestRetryDelayMs = (int) config('services.openai.retry_delay_ms', 500);
        }

        // If no provider key is available, default to mock mode.
        $this->mockMode = filter_var(env('AI_MOCK_MODE', empty($this->apiKey)), FILTER_VALIDATE_BOOL);
        $this->fallbackOnProviderFailure = filter_var(env('AI_FALLBACK_ON_PROVIDER_FAILURE', true), FILTER_VALIDATE_BOOL);
    }

    /**
     * Generate summary given an input text.
     */
    public function generateSummary(string $text): string
    {
        if ($this->mockMode) {
            return $this->getMockSummary();
        }

        $systemPrompt = <<<PROMPT
You are a study assistant AI that creates concise, accurate summaries of educational content. Your summaries should:
1. Capture the main ideas and key concepts
2. Use clear, simple language appropriate for students
3. Maintain factual accuracy — never add information not in the original
4. Be concise but comprehensive
5. Include a "Key Points" section
PROMPT;

        $userPrompt = "Please summarize the following study material:\n\n" . $text;

        try {
            return $this->callAiText($systemPrompt, $userPrompt, 0.3);
        } catch (\Throwable $e) {
            if (! $this->shouldFallbackFromException($e)) {
                throw $e;
            }

            Log::warning('Falling back to local summary generation.', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);

            return $this->buildFallbackSummary($text);
        }
    }

    /**
     * Generate flashcards.
     */
    public function generateFlashcards(string $text, string $difficulty = 'average', int $itemCount = 15): array
    {
        $difficulty = $this->normalizeDifficulty($difficulty);
        $itemCount = $this->normalizeItemCount($itemCount);
        $source = $this->prepareSourceText($text);

        if ($this->mockMode) {
            sleep(1);
            return [
                'cards' => $this->buildFallbackFlashcards($source, $difficulty, $itemCount),
            ];
        }

        $systemPrompt = $this->buildFlashcardSystemPrompt($difficulty, $itemCount);
        $lastError = null;

        for ($attempt = 1; $attempt <= self::OUTPUT_ATTEMPTS; $attempt++) {
            $userPrompt = $this->buildFlashcardUserPrompt($source, $difficulty, $itemCount, $attempt);

            try {
                $result = $this->callAiJson($systemPrompt, $userPrompt, 0.25);
                $cards = $this->normalizeFlashcardPayload($result, $source, $difficulty, $itemCount);

                if ($this->isFlashcardSetValid($cards, $source, $difficulty, $itemCount)) {
                    return ['cards' => $cards];
                }

                throw new Exception('Flashcard payload failed validation checks.');
            } catch (\Throwable $e) {
                $lastError = $e;

                Log::warning('Flashcard generation attempt failed.', [
                    'provider' => $this->provider,
                    'difficulty' => $difficulty,
                    'count' => $itemCount,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $this->fallbackOnProviderFailure && $lastError !== null) {
            throw $lastError;
        }

        Log::warning('Falling back to local flashcard generation.', [
            'provider' => $this->provider,
            'difficulty' => $difficulty,
            'count' => $itemCount,
            'error' => $lastError?->getMessage() ?? 'validation failure',
        ]);

        return [
            'cards' => $this->buildFallbackFlashcards($source, $difficulty, $itemCount),
        ];
    }

    /**
     * Generate quiz.
     */
    public function generateQuiz(string $text, string $difficulty = 'average', int $itemCount = 15): array
    {
        $difficulty = $this->normalizeDifficulty($difficulty);
        $itemCount = $this->normalizeItemCount($itemCount);
        $source = $this->prepareSourceText($text);

        if ($this->mockMode) {
            sleep(1);

            return [
                'questions' => $this->buildFallbackQuizQuestions($source, $difficulty, $itemCount),
            ];
        }

        $systemPrompt = $this->buildQuizSystemPrompt($difficulty, $itemCount);
        $lastError = null;

        for ($attempt = 1; $attempt <= self::OUTPUT_ATTEMPTS; $attempt++) {
            $userPrompt = $this->buildQuizUserPrompt($source, $difficulty, $itemCount, $attempt);

            try {
                $result = $this->callAiJson($systemPrompt, $userPrompt, 0.2);
                $questions = $this->normalizeQuizPayload($result, $source, $difficulty, $itemCount);

                if ($this->isQuizSetValid($questions, $source, $difficulty, $itemCount)) {
                    return ['questions' => $questions];
                }

                throw new Exception('Quiz payload failed validation checks.');
            } catch (\Throwable $e) {
                $lastError = $e;

                Log::warning('Quiz generation attempt failed.', [
                    'provider' => $this->provider,
                    'difficulty' => $difficulty,
                    'count' => $itemCount,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $this->fallbackOnProviderFailure && $lastError !== null) {
            throw $lastError;
        }

        Log::warning('Falling back to local quiz generation.', [
            'provider' => $this->provider,
            'difficulty' => $difficulty,
            'count' => $itemCount,
            'error' => $lastError?->getMessage() ?? 'validation failure',
        ]);

        return [
            'questions' => $this->buildFallbackQuizQuestions($source, $difficulty, $itemCount),
        ];
    }

    /**
     * Generate response for the Chat Tutor.
     */
    public function generateChatResponse(string $prompt): string
    {
        if ($this->mockMode) {
            sleep(1);
            return "This is a simulated chatbot context-aware response based on your question.";
        }

        // We embed context directly in the prompt built by SessionContextBuilder,
        // so we don't strictly need a separate system prompt here, but we pass
        // a basic identity to fulfill the existing method signature.
        $systemPrompt = "You are a helpful and expert AI Study Tutor. Respond formatting as Markdown.";
        
        return $this->callAiText($systemPrompt, $prompt, 0.7);
    }

    /**
     * Call active provider for text output.
     */
    protected function callAiText(string $systemPrompt, string $userPrompt, float $temperature = 0.5): string
    {
        if ($this->provider === 'gemini') {
            return $this->callGeminiText($systemPrompt, $userPrompt, $temperature);
        }

        return $this->callOpenAiText($systemPrompt, $userPrompt, $temperature);
    }

    /**
     * Call active provider for structured JSON output.
     */
    protected function callAiJson(string $systemPrompt, string $userPrompt, float $temperature = 0.5): array
    {
        if ($this->provider === 'gemini') {
            return $this->callGeminiJson($systemPrompt, $userPrompt, $temperature);
        }

        return $this->callOpenAiJson($systemPrompt, $userPrompt, $temperature);
    }

    /**
     * Call the OpenAI API chat completions endpoint.
     */
    protected function callOpenAiText(string $systemPrompt, string $userPrompt, float $temperature = 0.5): string
    {
        try {
            $startedAt = microtime(true);
            $response = $this->baseRequest($this->textTimeout)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $temperature,
            ]);

            if ($response->successful()) {
                $this->logRequestDuration('openai-text', $startedAt, $response->status());
                return trim((string) ($response->json('choices.0.message.content') ?? ''));
            }

            $this->logRequestDuration('openai-text', $startedAt, $response->status());

            Log::error('OpenAI API Error', [
                'provider' => 'openai',
                'model' => $this->model,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
            throw new Exception("AI request failed: " . $response->status());

        } catch (Exception $e) {
            Log::error('AiService Exception: ' . $e->getMessage());
            throw new Exception("Unable to generate content at this time. Please try again later.", 0, $e);
        }
    }

    /**
     * Return a mock summary for testing without an API key.
     */
    protected function getMockSummary(): string
    {
        sleep(2); // Simulate network delay
        return "Summary:\nThis is a mock summary of the provided text. It simulates the AI processing the study material and extracting the core meaning, while saving API costs and bypassing the need for a real API key during development phase.\n\nKey Points:\n- First important concept\n- Second crucial detail\n- The overarching theme\n- Mock test data integration";
    }

    /**
     * Generate structured JSON outputs. Expected format given in system prompt.
     */
    protected function callOpenAiJson(string $systemPrompt, string $userPrompt, float $temperature = 0.5): array
    {
        try {
            $startedAt = microtime(true);
            $response = $this->baseRequest($this->jsonTimeout)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $temperature,
            ]);

            if ($response->successful()) {
                $this->logRequestDuration('openai-json', $startedAt, $response->status());
                $content = (string) ($response->json('choices.0.message.content') ?? '');
                return json_decode($content, true) ?? [];
            }

            $this->logRequestDuration('openai-json', $startedAt, $response->status());

            Log::error('OpenAI API Error', [
                'provider' => 'openai',
                'model' => $this->model,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
            throw new Exception("AI request failed: " . $response->status());

        } catch (Exception $e) {
            Log::error('AiService Exception: ' . $e->getMessage());
            throw new Exception("Unable to generate structured content at this time.", 0, $e);
        }
    }

    protected function callGeminiText(string $systemPrompt, string $userPrompt, float $temperature = 0.5): string
    {
        try {
            $prompt = $systemPrompt . "\n\n" . $userPrompt;
            $startedAt = microtime(true);
            $response = $this->baseRequest($this->textTimeout)->withHeaders([
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->geminiEndpoint(), [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                ],
            ]);

            if ($response->successful()) {
                $this->logRequestDuration('gemini-text', $startedAt, $response->status());
                return trim((string) ($response->json('candidates.0.content.parts.0.text') ?? ''));
            }

            $this->logRequestDuration('gemini-text', $startedAt, $response->status());

            Log::error('Gemini API Error', [
                'provider' => 'gemini',
                'model' => $this->model,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new Exception("AI request failed: " . $response->status());
        } catch (Exception $e) {
            Log::error('AiService Exception: ' . $e->getMessage());
            throw new Exception("Unable to generate content at this time. Please try again later.", 0, $e);
        }
    }

    protected function callGeminiJson(string $systemPrompt, string $userPrompt, float $temperature = 0.5): array
    {
        try {
            $prompt = $systemPrompt . "\n\n" . $userPrompt . "\n\nReturn only valid JSON. No markdown fences.";
            $startedAt = microtime(true);
            $response = $this->baseRequest($this->jsonTimeout)->withHeaders([
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->geminiEndpoint(), [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $this->logRequestDuration('gemini-json', $startedAt, $response->status());
                $content = (string) ($response->json('candidates.0.content.parts.0.text') ?? '');
                $decoded = json_decode($this->extractJsonString($content), true);
                return is_array($decoded) ? $decoded : [];
            }

            $this->logRequestDuration('gemini-json', $startedAt, $response->status());

            Log::error('Gemini API Error', [
                'provider' => 'gemini',
                'model' => $this->model,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new Exception("AI request failed: " . $response->status());
        } catch (Exception $e) {
            Log::error('AiService Exception: ' . $e->getMessage());
            throw new Exception("Unable to generate structured content at this time.", 0, $e);
        }
    }

    protected function geminiEndpoint(): string
    {
        return 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent';
    }

    protected function extractJsonString(string $content): string
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*/', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
        }

        return trim($trimmed);
    }

    private function normalizeItemCount(int $count): int
    {
        if ($count < 15) {
            return 15;
        }

        if ($count > 50) {
            return 50;
        }

        return $count;
    }

    private function normalizeDifficulty(string $difficulty): string
    {
        return match (strtolower(trim($difficulty))) {
            'easy', 'average', 'hard' => strtolower(trim($difficulty)),
            default => 'average',
        };
    }

    private function difficultyGuide(string $difficulty): string
    {
        return match ($difficulty) {
            'easy' => '- Use straightforward language.\n- Focus on definitions, basics, and direct recall.',
            'hard' => '- Use analytical and applied reasoning grounded in the source text.\n- Include comparisons, causal links, tradeoffs, or failure conditions from the material.\n- Avoid broad prompts and generic wording.',
            default => '- Use balanced difficulty.\n- Mix foundational understanding and practical application.',
        };
    }

    private function baseRequest(int $timeout)
    {
        return Http::connectTimeout($this->connectTimeout)
            ->timeout($timeout)
            ->retry(max(1, $this->requestRetries + 1), $this->requestRetryDelayMs, throw: false);
    }

    private function logRequestDuration(string $operation, float $startedAt, int $status): void
    {
        Log::info('AI request completed.', [
            'provider' => $this->provider,
            'model' => $this->model,
            'operation' => $operation,
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }

    private function shouldFallbackFromException(\Throwable $e): bool
    {
        if (! $this->fallbackOnProviderFailure) {
            return false;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'ai request failed')
            || str_contains($message, 'unable to generate')
            || str_contains($message, 'quota')
            || str_contains($message, 'resource_exhausted')
            || str_contains($message, 'timed out')
            || str_contains($message, 'curl error');
    }

    private function buildFallbackSummary(string $text): string
    {
        $sentences = $this->extractSentences($text);
        $summaryLines = array_slice($sentences, 0, 3);
        $keyPoints = array_slice($sentences, 0, 5);

        $summaryBody = implode(' ', $summaryLines);
        if ($summaryBody === '') {
            $summaryBody = 'This material is ready for review, but AI summarization is temporarily unavailable.';
        }

        $points = array_map(fn (string $sentence) => '- '.$sentence, $keyPoints);
        if (empty($points)) {
            $points = ['- Review the uploaded material and extract key concepts manually for now.'];
        }

        return "Summary:\n".$summaryBody."\n\nKey Points:\n".implode("\n", $points);
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function buildFallbackFlashcards(string $text, string $difficulty, int $itemCount): array
    {
        $sentences = $this->extractSentences($text);

        if ($difficulty === 'hard') {
            return $this->buildHardFallbackFlashcards($sentences, $itemCount);
        }

        $cards = [];
        $templates = $this->flashcardTemplatesByDifficulty($difficulty);

        for ($i = 0; $i < $itemCount; $i++) {
            $source = $sentences[$i % max(1, count($sentences))] ?? 'Key study concept';
            $concept = $this->extractPrimaryConcept($source);
            $questionTemplate = $templates[$i % count($templates)];
            $cards[] = [
                'question' => sprintf($questionTemplate, $concept),
                'answer' => $this->clipText($source, 220),
            ];
        }

        return $cards;
    }

    /**
     * @return array<int, array{question: string, options: array<int, string>, correct_answer: string, explanation: string}>
     */
    private function buildFallbackQuizQuestions(string $text, string $difficulty, int $itemCount): array
    {
        $sentences = $this->extractSentences($text);

        if ($difficulty === 'hard') {
            return $this->buildHardFallbackQuizQuestions($sentences, $itemCount);
        }

        $questions = [];
        $templates = $this->quizTemplatesByDifficulty($difficulty);

        for ($i = 0; $i < $itemCount; $i++) {
            $source = $sentences[$i % max(1, count($sentences))] ?? 'Core concept from the study material';
            $altOne = $sentences[($i + 3) % max(1, count($sentences))] ?? $source;
            $altTwo = $sentences[($i + 7) % max(1, count($sentences))] ?? $source;
            $concept = $this->extractPrimaryConcept($source);
            $questionTemplate = $templates[$i % count($templates)];

            $correctOption = $this->clipText($source, 170);
            $options = [
                $this->clipText('It states the opposite of the source claim: '.$this->extractPrimaryConcept($source).' is not used in the process.', 170),
                $this->clipText('It shifts focus to another point from the material: '.$altOne, 170),
                $this->clipText('It removes a key condition described by the text: '.$altTwo, 170),
                $correctOption,
            ];

            $correctIndex = $i % 4;
            $correctValue = array_pop($options);
            array_splice($options, $correctIndex, 0, [$correctValue]);

            $questions[] = [
                'question' => sprintf($questionTemplate, $concept),
                'options' => $options,
                'correct_answer' => chr(65 + $correctIndex),
                'explanation' => 'The correct option restates the source material while the other options distort or dilute the original context.',
            ];
        }

        return $questions;
    }

    /**
     * @param array<int, string> $sentences
     * @return array<int, array{question: string, answer: string}>
     */
    private function buildHardFallbackFlashcards(array $sentences, int $itemCount): array
    {
        if ($sentences === []) {
            return [];
        }

        $cards = [];
        $sentenceCount = count($sentences);

        for ($i = 0; $i < $itemCount; $i++) {
            $primary = $sentences[$i % $sentenceCount];
            $secondary = $sentences[($i + 1) % $sentenceCount] ?? $primary;

            $conceptA = $this->extractPrimaryConcept($primary);
            $conceptB = $this->extractPrimaryConcept($secondary);

            $cards[] = [
                'question' => $this->ensureSentence(sprintf('In the source, how does %s interact with %s within the described process', $conceptA, $conceptB)),
                'answer' => $this->clipText($primary.' '.$secondary, 260),
            ];
        }

        return $cards;
    }

    /**
     * @param array<int, string> $sentences
     * @return array<int, array{question: string, options: array<int, string>, correct_answer: string, explanation: string}>
     */
    private function buildHardFallbackQuizQuestions(array $sentences, int $itemCount): array
    {
        if ($sentences === []) {
            return [];
        }

        $questions = [];
        $sentenceCount = count($sentences);

        for ($i = 0; $i < $itemCount; $i++) {
            $primary = $sentences[$i % $sentenceCount];
            $secondary = $sentences[($i + 1) % $sentenceCount] ?? $primary;

            $conceptA = $this->extractPrimaryConcept($primary);
            $conceptB = $this->extractPrimaryConcept($secondary);

            $correctOption = $this->clipText($primary.' '.$secondary, 170);
            $options = [
                $this->clipText(sprintf('The source says %s has no relationship with %s and does not affect outcomes.', $conceptA, $conceptB), 170),
                $this->clipText(sprintf('The material treats %s only as a label and excludes process implications involving %s.', $conceptA, $conceptB), 170),
                $this->clipText(sprintf('The text presents %s as the sole mechanism and removes any role for %s.', $conceptB, $conceptA), 170),
                $correctOption,
            ];

            $correctIndex = $i % 4;
            $correctValue = array_pop($options);
            array_splice($options, $correctIndex, 0, [$correctValue]);

            $questions[] = [
                'question' => $this->ensureSentence(sprintf('Which option best preserves how %s is described relative to %s in the source', $conceptA, $conceptB)),
                'options' => $options,
                'correct_answer' => chr(65 + $correctIndex),
                'explanation' => 'The correct option directly preserves the relationships stated in the source, while the alternatives remove or invert key conditions.',
            ];
        }

        return $questions;
    }

    /**
     * @return array<int, string>
     */
    private function extractSentences(string $text): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/(?<=[.!?])\s+/', $normalized) ?: [];
        $sentences = [];

        foreach ($parts as $part) {
            $line = trim((string) $part);
            if (mb_strlen($line) < 15) {
                continue;
            }

            $sentences[] = $line;
            if (count($sentences) >= 60) {
                break;
            }
        }

        if (empty($sentences)) {
            $sentences[] = $normalized;
        }

        return $sentences;
    }

    private function buildFlashcardSystemPrompt(string $difficulty, int $itemCount): string
    {
        $difficultyGuide = $this->difficultyGuide($difficulty);
        $hardRules = $difficulty === 'hard'
            ? "6) HARD mode: each card must include at least two concrete source terms and one causal or comparative relation.\n"
              . "7) HARD mode: never answer with only headings, titles, or isolated keywords."
            : '';

        return <<<PROMPT
You are a study assistant that writes high-quality, source-grounded flashcards.
Difficulty level: {$difficulty}
Difficulty guidance:
{$difficultyGuide}

Strict rules:
1) Use only facts that appear in SOURCE MATERIAL.
2) Never use placeholders or labels like [Hard], Flashcard 1, or "Explain this concept".
3) Questions must reference concrete terms from the source.
4) Answers must be specific, complete, and tied to source wording.
5) Each card must be distinct (no repetitive templates).
{$hardRules}

Output JSON only with exactly {$itemCount} cards:
{
  "cards": [
    { "question": "...", "answer": "..." }
  ]
}
PROMPT;
    }

    private function buildQuizSystemPrompt(string $difficulty, int $itemCount): string
    {
        $difficultyGuide = $this->difficultyGuide($difficulty);
        $hardRules = $difficulty === 'hard'
            ? "8) HARD mode: question, correct option, and explanation must each include concrete source terms.\n"
              . "9) HARD mode: options must be complete statements; no fragments or placeholders."
            : '';

        return <<<PROMPT
You are an expert educator writing source-grounded multiple choice questions.
Difficulty level: {$difficulty}
Difficulty guidance:
{$difficultyGuide}

Strict rules:
1) Use only SOURCE MATERIAL facts.
2) Never output placeholders such as [Hard], Question 1, or generic stems.
3) Questions must be contextual and specific to the source.
4) Provide exactly 4 distinct options per question.
5) Distractors must be plausible but incorrect.
6) correct_answer must be A, B, C, or D only.
7) Provide a concise explanation tied to source evidence.
{$hardRules}

Output JSON only:
{
  "questions": [
    {
      "question": "...",
      "options": ["...", "...", "...", "..."],
      "correct_answer": "A",
      "explanation": "..."
    }
  ]
}

Generate exactly {$itemCount} questions.
PROMPT;
    }

    private function buildFlashcardUserPrompt(string $source, string $difficulty, int $itemCount, int $attempt): string
    {
        $retryInstruction = $attempt > 1
            ? "Retry note: previous output was rejected for generic, title-like, or repetitive wording. Use source-grounded terms and process-level reasoning."
            : '';
        $keywords = implode(', ', $this->extractSourceKeywords($source, 18));

        return <<<PROMPT
Create exactly {$itemCount} {$difficulty}-difficulty flashcards from the SOURCE MATERIAL below.
{$retryInstruction}
Anchor terms from source: {$keywords}

Schema reminder:
{"cards":[{"question":"...","answer":"..."}]}

SOURCE MATERIAL START
{$source}
SOURCE MATERIAL END
PROMPT;
    }

    private function buildQuizUserPrompt(string $source, string $difficulty, int $itemCount, int $attempt): string
    {
        $retryInstruction = $attempt > 1
            ? "Retry note: previous output was rejected for malformed options or generic phrasing. Keep every option complete and source-grounded."
            : '';
        $keywords = implode(', ', $this->extractSourceKeywords($source, 18));

        return <<<PROMPT
Create exactly {$itemCount} {$difficulty}-difficulty multiple-choice questions using the SOURCE MATERIAL.
{$retryInstruction}
Anchor terms from source: {$keywords}

Schema reminder:
{"questions":[{"question":"...","options":["...","...","...","..."],"correct_answer":"A","explanation":"..."}]}

SOURCE MATERIAL START
{$source}
SOURCE MATERIAL END
PROMPT;
    }

    private function prepareSourceText(string $text): string
    {
        $normalized = $this->cleanSourceForPrompt($text);
        if ($normalized === '') {
            return '';
        }

        if (mb_strlen($normalized) > 14000) {
            $head = mb_substr($normalized, 0, 4500);
            $middleStart = max(0, intdiv(mb_strlen($normalized), 2) - 2500);
            $middle = mb_substr($normalized, $middleStart, 5000);
            $tail = mb_substr($normalized, -4500);

            return trim($head."\n\n...\n\n".$middle."\n\n...\n\n".$tail);
        }

        return $normalized;
    }

    private function cleanSourceForPrompt(string $text): string
    {
        $lines = preg_split('/\R/', (string) $text) ?: [];
        $cleanLines = [];
        $seen = [];

        foreach ($lines as $line) {
            $normalized = trim((string) preg_replace('/\s+/', ' ', $line));
            if ($normalized === '') {
                continue;
            }

            if (preg_match('/^\d+$/', $normalized) === 1) {
                continue;
            }

            $hash = strtolower($normalized);
            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $cleanLines[] = $normalized;
        }

        return trim(implode("\n", $cleanLines));
    }

    private function normalizeFlashcardPayload(array $payload, string $source, string $difficulty, int $itemCount): array
    {
        $cards = is_array($payload['cards'] ?? null) ? $payload['cards'] : [];
        $keywords = $this->extractSourceKeywords($source);
        $normalized = [];
        $seen = [];

        foreach ($cards as $card) {
            $question = $this->normalizeLine((string) ($card['question'] ?? ''));
            $answer = $this->normalizeLine((string) ($card['answer'] ?? ''));

            if ($question === '' || $answer === '') {
                continue;
            }

            if ($this->containsPlaceholderLanguage($question) || $this->containsPlaceholderLanguage($answer)) {
                continue;
            }

            if ($this->containsGenericStem($question) || $this->containsGenericStem($answer)) {
                continue;
            }

            if ($difficulty === 'hard') {
                if (! $this->containsSourceKeyword($question, $keywords) || ! $this->containsSourceKeyword($answer, $keywords)) {
                    continue;
                }

                if ($this->wordCount($answer) < 8) {
                    continue;
                }

                if (! $this->containsAtLeastDistinctKeywords($question.' '.$answer, $keywords, 2)) {
                    continue;
                }
            }

            if (mb_strlen($question) < 20 || mb_strlen($answer) < 20) {
                continue;
            }

            $key = strtolower($question.'|'.$answer);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = [
                'question' => $this->ensureSentence($question),
                'answer' => $this->ensureSentence($answer),
            ];
        }

        if (count($normalized) < $itemCount) {
            $fallback = $this->buildFallbackFlashcards($source, $difficulty, $itemCount);
            foreach ($fallback as $card) {
                if (count($normalized) >= $itemCount) {
                    break;
                }

                $question = $this->normalizeLine((string) ($card['question'] ?? ''));
                $answer = $this->normalizeLine((string) ($card['answer'] ?? ''));
                $key = strtolower($question.'|'.$answer);

                if ($question === '' || $answer === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $normalized[] = [
                    'question' => $this->ensureSentence($question),
                    'answer' => $this->ensureSentence($answer),
                ];
            }
        }

        return array_slice($normalized, 0, $itemCount);
    }

    private function normalizeQuizPayload(array $payload, string $source, string $difficulty, int $itemCount): array
    {
        $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];
        $keywords = $this->extractSourceKeywords($source);
        $normalized = [];
        $seen = [];

        foreach ($questions as $questionData) {
            $question = $this->normalizeLine((string) ($questionData['question'] ?? ''));
            $options = array_values(is_array($questionData['options'] ?? null) ? $questionData['options'] : []);
            $options = array_slice(array_map(fn ($value) => $this->normalizeLine((string) $value), $options), 0, 4);
            $explanation = $this->normalizeLine((string) ($questionData['explanation'] ?? ''));

            if ($question === '' || count($options) < 4 || in_array('', $options, true)) {
                continue;
            }

            if ($this->containsPlaceholderLanguage($question) || $this->containsPlaceholderLanguage(implode(' ', $options)) || $this->containsPlaceholderLanguage($explanation)) {
                continue;
            }

            if ($this->containsGenericStem($question) || $this->containsGenericStem($explanation)) {
                continue;
            }

            if ($difficulty === 'hard' && ! $this->containsSourceKeyword($question.' '.implode(' ', $options), $keywords)) {
                continue;
            }

            if (count(array_unique(array_map('mb_strtolower', $options))) < 4) {
                continue;
            }

            $correctRaw = strtoupper($this->normalizeLine((string) ($questionData['correct_answer'] ?? '')));
            $correctAnswer = $correctRaw;

            if (! in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
                $matched = array_search($this->normalizeLine((string) ($questionData['correct_answer'] ?? '')), $options, true);
                $correctAnswer = $matched === false ? 'A' : chr(65 + $matched);
            }

            if ($difficulty === 'hard') {
                $correctIndex = ord($correctAnswer) - 65;
                $correctOption = $options[$correctIndex] ?? '';

                if ($this->wordCount($question) < 9 || $this->wordCount($correctOption) < 6 || $this->wordCount($explanation) < 9) {
                    continue;
                }

                if (! $this->containsAtLeastDistinctKeywords($question.' '.$correctOption.' '.$explanation, $keywords, 2)) {
                    continue;
                }

                foreach ($options as $option) {
                    if ($this->wordCount($option) < 5) {
                        continue 2;
                    }
                }
            }

            $key = strtolower($question);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = [
                'question' => $this->ensureSentence($question),
                'options' => array_map(fn (string $option) => $this->ensureSentence($option), $options),
                'correct_answer' => $correctAnswer,
                'explanation' => $this->ensureSentence($explanation !== '' ? $explanation : 'The correct option best matches the source material.'),
            ];
        }

        if (count($normalized) < $itemCount) {
            $fallback = $this->buildFallbackQuizQuestions($source, $difficulty, $itemCount);
            foreach ($fallback as $questionData) {
                if (count($normalized) >= $itemCount) {
                    break;
                }

                $question = $this->normalizeLine((string) ($questionData['question'] ?? ''));
                $key = strtolower($question);
                if ($question === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $normalized[] = $questionData;
            }
        }

        return array_slice($normalized, 0, $itemCount);
    }

    private function isFlashcardSetValid(array $cards, string $source, string $difficulty, int $itemCount): bool
    {
        if (count($cards) < $itemCount) {
            return false;
        }

        $questions = array_map(fn (array $card) => strtolower($this->normalizeLine((string) ($card['question'] ?? ''))), $cards);

        if ($this->hasRepetitiveStarts($questions)) {
            return false;
        }

        if ($difficulty === 'hard') {
            $keywords = $this->extractSourceKeywords($source);
            $matches = 0;
            foreach ($cards as $card) {
                $text = ($card['question'] ?? '').' '.($card['answer'] ?? '');
                if ($this->containsAtLeastDistinctKeywords($text, $keywords, 2)) {
                    $matches++;
                }
            }

            return $matches >= max(10, (int) floor($itemCount * 0.8));
        }

        return true;
    }

    private function isQuizSetValid(array $questions, string $source, string $difficulty, int $itemCount): bool
    {
        if (count($questions) < $itemCount) {
            return false;
        }

        $stems = array_map(fn (array $q) => strtolower($this->normalizeLine((string) ($q['question'] ?? ''))), $questions);
        if ($this->hasRepetitiveStarts($stems)) {
            return false;
        }

        if ($difficulty === 'hard') {
            $keywords = $this->extractSourceKeywords($source);
            $matches = 0;
            foreach ($questions as $question) {
                $text = ($question['question'] ?? '').' '.implode(' ', $question['options'] ?? []).' '.($question['explanation'] ?? '');
                if ($this->containsAtLeastDistinctKeywords($text, $keywords, 2)) {
                    $matches++;
                }
            }

            return $matches >= max(10, (int) floor($itemCount * 0.75));
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function flashcardTemplatesByDifficulty(string $difficulty): array
    {
        return match ($difficulty) {
            'easy' => [
                'What does the material say about %s?',
                'How is %s defined in the study material?',
                'Which key point about %s should be remembered?',
            ],
            'hard' => [
                'How does %s influence the process described in the material?',
                'Why is %s important for the outcome discussed in the source?',
                'What tradeoff or implication involving %s appears in the material?',
                'How would changing %s alter the result described in the source?',
            ],
            default => [
                'What is the central idea related to %s in the material?',
                'How is %s applied in the source content?',
                'What practical takeaway about %s is given in the material?',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    private function quizTemplatesByDifficulty(string $difficulty): array
    {
        return match ($difficulty) {
            'easy' => [
                'According to the material, which statement about %s is correct?',
                'Which option best reflects the source explanation of %s?',
            ],
            'hard' => [
                'Based on the material, which option most accurately explains %s in context?',
                'Which statement best captures the source-level implication of %s?',
                'According to the material, which choice preserves the intended meaning of %s?',
            ],
            default => [
                'Which option best matches the source discussion of %s?',
                'According to the material, which statement about %s is most accurate?',
            ],
        };
    }

    private function extractPrimaryConcept(string $sentence): string
    {
        $keywords = $this->extractSourceKeywords($sentence, 6);

        return $keywords[0] ?? 'the main concept';
    }

    /**
     * @return array<int, string>
     */
    private function extractSourceKeywords(string $text, int $limit = 25): array
    {
        preg_match_all('/[A-Za-z][A-Za-z0-9\-]{3,}/', strtolower($text), $matches);
        $words = $matches[0] ?? [];
        $counts = [];

        foreach ($words as $word) {
            if (in_array($word, self::STOP_WORDS, true)) {
                continue;
            }

            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, $limit);
    }

    private function containsSourceKeyword(string $text, array $keywords): bool
    {
        $normalized = strtolower($text);

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($normalized, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function containsPlaceholderLanguage(string $text): bool
    {
        foreach (self::PLACEHOLDER_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    private function containsGenericStem(string $text): bool
    {
        $normalized = $this->normalizeLine($text);

        foreach (self::GENERIC_STEM_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    private function containsAtLeastDistinctKeywords(string $text, array $keywords, int $minimum): bool
    {
        return $this->countSourceKeywordMatches($text, $keywords) >= $minimum;
    }

    private function countSourceKeywordMatches(string $text, array $keywords): int
    {
        $normalized = strtolower($text);
        $matches = [];

        foreach ($keywords as $keyword) {
            if ($keyword === '') {
                continue;
            }

            if (str_contains($normalized, strtolower($keyword))) {
                $matches[$keyword] = true;
            }
        }

        return count($matches);
    }

    private function wordCount(string $text): int
    {
        $normalized = $this->normalizeLine($text);
        if ($normalized === '') {
            return 0;
        }

        $parts = preg_split('/\s+/', $normalized) ?: [];

        return count(array_filter($parts, fn (string $part) => $part !== ''));
    }

    /**
     * @param array<int, string> $stems
     */
    private function hasRepetitiveStarts(array $stems): bool
    {
        if (count($stems) < 6) {
            return false;
        }

        $prefixes = [];
        foreach ($stems as $stem) {
            $prefix = mb_substr($stem, 0, 28);
            $prefixes[$prefix] = ($prefixes[$prefix] ?? 0) + 1;
        }

        $max = max($prefixes);

        return $max > (int) floor(count($stems) * 0.45);
    }

    private function normalizeLine(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function ensureSentence(string $text): string
    {
        $normalized = $this->normalizeLine($text);
        if ($normalized === '') {
            return $normalized;
        }

        if (! preg_match('/[.!?]$/', $normalized)) {
            return $normalized.'.';
        }

        return $normalized;
    }

    private function clipText(string $text, int $maxChars): string
    {
        $normalized = $this->normalizeLine($text);
        if (mb_strlen($normalized) <= $maxChars) {
            return $this->ensureSentence($normalized);
        }

        $clipped = rtrim(mb_substr($normalized, 0, $maxChars - 1));

        return $this->ensureSentence($clipped);
    }
}