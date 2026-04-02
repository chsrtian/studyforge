<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 leading-tight">
            Study History
        </h2>
    </x-slot>

    @php
        $isDueView = request()->boolean('due') || request('sort') === 'review_due';
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="mb-6 sf-card p-4 sm:p-5">
                <form action="{{ route('history.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-5">
                        <label class="text-xs font-semibold tracking-[0.08em] text-slate-500 dark:text-slate-400 mb-1 block">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search topics, keywords..." class="block w-full text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg shadow-sm focus:ring-primary focus:border-primary">
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-xs font-semibold tracking-[0.08em] text-slate-500 dark:text-slate-400 mb-1 block">Tag</label>
                        <select name="tag" class="block w-full text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg shadow-sm focus:ring-primary focus:border-primary">
                            <option value="">All tags</option>
                            @foreach($availableTags as $tag)
                                <option value="{{ $tag }}" @selected(request('tag') === $tag)>#{{ $tag }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold tracking-[0.08em] text-slate-500 dark:text-slate-400 mb-1 block">Sort</label>
                        <select name="sort" class="block w-full text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg shadow-sm focus:ring-primary focus:border-primary">
                            <option value="recent" @selected(request('sort', 'recent') === 'recent')>Recent</option>
                            <option value="review_due" @selected(request('sort') === 'review_due')>Review Due</option>
                            <option value="title" @selected(request('sort') === 'title')>Title</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center text-sm text-slate-600 dark:text-slate-300">
                            <input type="checkbox" name="bookmarked" value="1" @checked(request('bookmarked')) class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-primary focus:ring-primary dark:bg-slate-900">
                            <span class="ml-2">Bookmarked</span>
                        </label>
                        <label class="inline-flex items-center text-sm text-slate-600 dark:text-slate-300">
                            <input type="checkbox" name="due" value="1" @checked(request('due')) class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-primary focus:ring-primary dark:bg-slate-900">
                            <span class="ml-2">Due now</span>
                        </label>
                    </div>
                    <div class="md:col-span-12 flex justify-end gap-2">
                        <a href="{{ route('history.index') }}" class="sf-btn sf-btn-secondary" data-nav-loading>Reset</a>
                        <button type="submit" class="sf-btn sf-btn-primary">Apply</button>
                    </div>
                </form>
            </div>

            @if($sessions->count() > 0)
                <div class="sf-card overflow-hidden">
                    <ul role="list" class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach($sessions as $session)
                            <li>
                                <a href="{{ route('study_sessions.show', $session->id) }}" data-nav-loading class="block hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                                    <div class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-between">
                                            <p class="text-lg font-semibold text-primary line-clamp-1 truncate">{{ $session->title }}</p>
                                            <div class="ml-2 flex-shrink-0 flex items-center gap-2">
                                                @if($session->is_pinned)
                                                    <span class="sf-pill-warning">Pinned</span>
                                                @endif
                                                @if($session->is_bookmarked)
                                                    <span class="sf-pill-success">Bookmarked</span>
                                                @endif
                                                @if($session->next_review_at && $session->next_review_at->isPast())
                                                    <span class="sf-pill-warning">Review due</span>
                                                @endif
                                                <span class="{{ $session->status === 'completed' ? 'sf-pill-success' : 'sf-pill-warning' }}">
                                                    {{ ucfirst($session->status) }}
                                                </span>
                                                <span class="sf-btn sf-btn-ghost text-xs px-3 py-1.5">Open session</span>
                                            </div>
                                        </div>
                                        <div class="mt-2 sm:flex sm:justify-between">
                                            <div class="sm:flex">
                                                <p class="flex items-center text-sm text-slate-600 dark:text-slate-300 line-clamp-2 break-words w-full max-w-xl">
                                                    {{ Str::limit($session->extracted_text ?? $session->input_text, 120) }}
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach($session->tags as $tag)
                                                        <span class="sf-pill-neutral">#{{ $tag->name }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="mt-2 flex items-center text-sm text-slate-500 dark:text-slate-400 sm:mt-0 sm:ml-6 flex-shrink-0">
                                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <p>
                                                    <time datetime="{{ $session->created_at->toIso8601String() }}">{{ $session->created_at->format('M d, Y') }} · {{ $session->created_at->diffForHumans() }}</time>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-6">
                    {{ $sessions->links() }}
                </div>
            @else
                <div class="text-center py-16 sf-card mt-6">
                    <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 0a2 2 0 104 0m-4 0a2 2 0 014 0m-4 8h8m-8 4h5"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $isDueView ? 'No reviews due right now' : 'No sessions found' }}</h3>
                    <p class="mt-1 text-slate-500 dark:text-slate-400">{{ $isDueView ? 'You are caught up. New due reviews will appear here based on your schedule.' : "We couldn't find anything matching your search." }}</p>
                    <div class="mt-6">
                        <a href="{{ route('study_sessions.create') }}" class="sf-btn sf-btn-primary" data-nav-loading>
                            Create New Session
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>