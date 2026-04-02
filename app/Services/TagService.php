<?php

namespace App\Services;

use App\Models\SessionTag;
use App\Models\StudySession;
use Illuminate\Support\Collection;

class TagService
{
    private const MAX_TAGS = 8;
    private const MAX_TAG_LENGTH = 40;

    public function syncTagsForSession(StudySession $studySession, ?string $tagsInput): void
    {
        $tagNames = collect(explode(',', (string) $tagsInput))
            ->map(fn (string $tag) => $this->sanitizeTag($tag))
            ->filter()
            ->map(fn (string $tag) => mb_strtolower($tag))
            ->unique()
            ->take(self::MAX_TAGS)
            ->values();

        if ($tagNames->isEmpty()) {
            $studySession->tags()->sync([]);
            return;
        }

        $tagIds = $tagNames->map(function (string $name) use ($studySession) {
            return SessionTag::firstOrCreate([
                'user_id' => $studySession->user_id,
                'name' => $name,
            ])->id;
        });

        $studySession->tags()->sync($tagIds->all());
    }

    private function sanitizeTag(string $tag): string
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $tag));
        $normalized = trim((string) preg_replace('/[^\pL\pN\s\-._]/u', '', $normalized));

        if ($normalized === '') {
            return '';
        }

        return mb_substr($normalized, 0, self::MAX_TAG_LENGTH);
    }
}
