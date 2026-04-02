<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatSendMessageRequest;
use App\Models\StudySession;
use App\Services\ChatTutorService;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected ChatTutorService $chatService;

    public function __construct(ChatTutorService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function sendMessage(ChatSendMessageRequest $request, StudySession $studySession)
    {
        $this->authorize('view', $studySession);
        $validated = $request->validated();

        try {
            $result = $this->chatService->sendMessage($studySession, (string) $validated['message']);
        } catch (\Throwable $exception) {
            $message = strtolower($exception->getMessage());
            $isQuotaError = str_contains($message, 'quota');

            Log::warning('Chat message processing failed.', [
                'study_session_id' => $studySession->id,
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $isQuotaError
                    ? $exception->getMessage()
                    : 'Unable to process your message right now. Please try again in a moment.',
            ], $isQuotaError ? 429 : 503);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'role' => $result['assistant']->role,
                'content' => $result['assistant']->content,
                'created_at' => $result['assistant']->created_at->toISOString(),
            ]
        ]);
    }

    public function getHistory(StudySession $studySession)
    {
        $this->authorize('view', $studySession);

        try {
            $history = $this->chatService->getChatHistory($studySession);
        } catch (\Throwable $exception) {
            Log::warning('Failed to retrieve chat history.', [
                'study_session_id' => $studySession->id,
                'user_id' => request()->user()?->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'messages' => [],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'messages' => $history->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'created_at' => $msg->created_at->toISOString(),
                ];
            })
        ]);
    }
}
