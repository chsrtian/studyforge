<?php

namespace App\Services;

use App\Models\LoginAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LoginAuditService
{
    public function log(
        string $eventType,
        ?User $user,
        ?string $email,
        Request $request,
        array $metadata = []
    ): void {
        try {
            if (! Schema::hasTable('login_audit_logs')) {
                return;
            }

            LoginAuditLog::create([
                'user_id' => $user?->id,
                'email' => $email,
                'event_type' => $eventType,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => empty($metadata) ? null : $metadata,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Login audit logging skipped due to database issue.', [
                'event_type' => $eventType,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
