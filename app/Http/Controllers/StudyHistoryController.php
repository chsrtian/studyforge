<?php

namespace App\Http\Controllers;

use App\Models\StudySession;
use App\Models\SessionTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = StudySession::where('user_id', Auth::id())->with('tags');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('input_text', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tag')) {
            $tag = mb_strtolower((string) $request->input('tag'));
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', $tag);
            });
        }

        if ($request->boolean('bookmarked')) {
            $query->where('is_bookmarked', true);
        }

        if ($request->boolean('due')) {
            $query->whereNotNull('next_review_at')->where('next_review_at', '<=', now());
        }

        $sort = $request->input('sort', 'recent');
        if ($sort === 'review_due') {
            $query->orderByRaw('CASE WHEN next_review_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('next_review_at', 'asc');
        } elseif ($sort === 'title') {
            $query->orderBy('title', 'asc');
        } else {
            $query->orderByDesc('is_pinned')->orderBy('created_at', 'desc');
        }

        $sessions = $query->paginate(12)->withQueryString();
        $availableTags = SessionTag::where('user_id', Auth::id())->orderBy('name')->pluck('name');

        return view('history.index', compact('sessions', 'availableTags'));
    }
}
