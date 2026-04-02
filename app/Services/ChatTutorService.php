<?php

namespace App\Services;

use App\Models\StudySession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ChatTutorService
{
    private const MAX_HISTORY_MESSAGES = 200;
    private const DEFAULT_DAILY_TOKEN_LIMIT = 25000;
    private const DEFAULT_MONTHLY_TOKEN_LIMIT = 500000;

    protected AiService $aiService;
    protected SessionContextBuilder $contextBuilder;

    public function __construct(AiService $aiService, SessionContextBuilder $contextBuilder)
    {
        $this->aiService = $aiService;
        $this->contextBuilder = $contextBuilder;
    }

    public function sendMessage(StudySession $session, string $message): array
    {
        $userTokenEstimate = $this->estimateTokenCount($message);
        $this->assertQuotaAvailable($session->user_id, $userTokenEstimate);

        // 1. Find or create a chat thread for this session (MVP: just use one main thread per session)
        $thread = $session->chatThreads()->firstOrCreate([
            'title' => 'Main Tutor Chat'
        ]);

        // 2. Save user message
        $userMsg = $thread->messages()->create([
            'role' => 'user',
            'content' => $message,
            'tokens_used' => $userTokenEstimate,
        ]);
        $this->consumeQuota($session->user_id, $userTokenEstimate);

        // 3. Build context-grounded prompt
        $prompt = $this->contextBuilder->buildChatContext($session, $message);

        // 4. Call AI via AiService (we'll add a new method to AiService to handle raw chat completions)
        // Wait! Let's reuse existing provider logic in AiService
        $aiResponseText = $this->aiService->generateChatResponse($prompt);
        $assistantTokenEstimate = $this->estimateTokenCount($aiResponseText);
        $this->assertQuotaAvailable($session->user_id, $assistantTokenEstimate);

        // 5. Save assistant message
        $assistantMsg = $thread->messages()->create([
            'role' => 'assistant',
            'content' => $aiResponseText,
            'tokens_used' => $assistantTokenEstimate,
        ]);
        $this->consumeQuota($session->user_id, $assistantTokenEstimate);

        return [
            'user' => $userMsg,
            'assistant' => $assistantMsg,
        ];
    }

    public function getChatHistory(StudySession $session)
    {
        $thread = $session->chatThreads()->first();
        if (!$thread) {
            return collect([]);
        }

        return $thread->messages()
            ->latest('id')
            ->limit(self::MAX_HISTORY_MESSAGES)
            ->get()
            ->sortBy('id')
            ->values();
    }

    private function estimateTokenCount(string $content): int
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $content));

        if ($normalized === '') {
            return 0;
        }

        return (int) max(1, ceil(mb_strlen($normalized) / 4));
    }

    private function assertQuotaAvailable(int $userId, int $additionalTokens): void
    {
        if ($additionalTokens <= 0) {
            return;
        }

        $dailyLimit = (int) env('AI_DAILY_TOKEN_LIMIT', self::DEFAULT_DAILY_TOKEN_LIMIT);
        $monthlyLimit = (int) env('AI_MONTHLY_TOKEN_LIMIT', self::DEFAULT_MONTHLY_TOKEN_LIMIT);

        $dailyUsage = (int) Cache::get($this->dailyQuotaKey($userId), 0);
        if ($dailyUsage + $additionalTokens > $dailyLimit) {
            throw new \RuntimeException('AI daily token quota reached. Please try again tomorrow.');
        }

        $monthlyUsage = (int) Cache::get($this->monthlyQuotaKey($userId), 0);
        if ($monthlyUsage + $additionalTokens > $monthlyLimit) {
            throw new \RuntimeException('AI monthly token quota reached. Please try again next month.');
        }
    }

    private function consumeQuota(int $userId, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        $dailyKey = $this->dailyQuotaKey($userId);
        $dailyExpiry = Carbon::tomorrow()->startOfDay();
        $dailyUsage = (int) Cache::get($dailyKey, 0);
        Cache::put($dailyKey, $dailyUsage + $tokens, $dailyExpiry);

        $monthlyKey = $this->monthlyQuotaKey($userId);
        $monthlyExpiry = Carbon::now()->addMonthNoOverflow()->startOfMonth();
        $monthlyUsage = (int) Cache::get($monthlyKey, 0);
        Cache::put($monthlyKey, $monthlyUsage + $tokens, $monthlyExpiry);
    }

    private function dailyQuotaKey(int $userId): string
    {
        return 'ai:quota:daily:'.$userId.':'.Carbon::today()->format('Ymd');
    }

    private function monthlyQuotaKey(int $userId): string
    {
        return 'ai:quota:monthly:'.$userId.':'.Carbon::today()->format('Ym');
    }
}
