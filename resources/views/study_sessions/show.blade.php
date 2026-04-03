<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-start gap-3">
                <a href="{{ route('history.index') }}" class="sf-btn sf-btn-ghost px-3 py-2" data-nav-loading aria-label="Back to history">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10 19 3 12m0 0 7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <p class="text-xs tracking-[0.08em] font-semibold text-primary dark:text-indigo-200">Study session</p>
                    <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 leading-tight">
                        {{ $studySession->title }}
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Created {{ $studySession->created_at->format('M d, Y') }} · {{ $studySession->created_at->diffForHumans() }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('study_sessions.bookmark', $studySession->id) }}">
                    @csrf
                    <button type="submit" class="sf-btn text-xs px-3 py-2 {{ $studySession->is_bookmarked ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-400/30' : 'sf-btn-secondary' }}">
                        {{ $studySession->is_bookmarked ? 'Bookmarked' : 'Bookmark' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('study_sessions.pin', $studySession->id) }}">
                    @csrf
                    <button type="submit" class="sf-btn text-xs px-3 py-2 {{ $studySession->is_pinned ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-400/30' : 'sf-btn-secondary' }}">
                        {{ $studySession->is_pinned ? 'Pinned' : 'Pin' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('study_sessions.review', $studySession->id) }}">
                    @csrf
                    <button type="submit" class="sf-btn sf-btn-secondary text-xs px-3 py-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m5 13 4 4L19 7"></path>
                        </svg>
                        Mark Reviewed
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    @php
        $prefs = is_array($studySession->metadata ?? null) ? ($studySession->metadata['generation_preferences'] ?? []) : [];
        $generationStatus = is_array($studySession->metadata ?? null) ? ($studySession->metadata['generation_status'] ?? []) : [];
        $flashPrefs = $prefs['flashcards'] ?? null;
        $quizPrefs = $prefs['quiz'] ?? null;
        $summaryStatus = $generationStatus['summary']['status'] ?? null;
        $flashGenerationStatus = $generationStatus['flashcards']['status'] ?? null;
        $quizGenerationStatus = $generationStatus['quiz']['status'] ?? null;
        $regenStatus = is_array($studySession->metadata ?? null) ? ($studySession->metadata['regeneration_status'] ?? []) : [];
        $flashStatus = is_array($regenStatus['flashcards'] ?? null) ? $regenStatus['flashcards'] : [];
        $quizStatus = is_array($regenStatus['quiz'] ?? null) ? $regenStatus['quiz'] : [];
        $flashStatusValue = $flashStatus['status'] ?? null;
        $quizStatusValue = $quizStatus['status'] ?? null;
        $formatStatusTime = function (?string $value): ?string {
            if (! is_string($value) || $value === '') {
                return null;
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->diffForHumans();
            } catch (\Throwable $e) {
                return null;
            }
        };
        $flashStatusUpdated = $formatStatusTime($flashStatus['updated_at'] ?? null);
        $quizStatusUpdated = $formatStatusTime($quizStatus['updated_at'] ?? null);
        $flashRegenerationLocked = in_array($flashStatusValue, ['queued', 'processing'], true);
        $quizRegenerationLocked = in_array($quizStatusValue, ['queued', 'processing'], true);
        $flashcardFormDifficulty = $flashPrefs['difficulty'] ?? 'average';
        $flashcardFormCount = (int) ($flashPrefs['count'] ?? max($studySession->flashcards->count(), 15));
        $quizQuestionCount = $studySession->quizzes->first()?->questions->count() ?? 0;
        $quizFormDifficulty = $quizPrefs['difficulty'] ?? 'average';
        $quizFormCount = (int) ($quizPrefs['count'] ?? max($quizQuestionCount, 15));
        $sourceMaterial = (string) ($studySession->extracted_text ?? $studySession->input_text ?? '');
    @endphp

    <div class="pt-8 pb-12"
        x-data="sessionProgress({
            initialTab: '{{ in_array(request('tab'), ['summary', 'flashcards', 'quiz', 'chat'], true) ? request('tab') : 'summary' }}',
            pollUrl: '{{ route('study_sessions.generation_status', $studySession->id) }}',
            initialSessionStatus: '{{ $studySession->status }}',
            initialSummaryStatus: '{{ (string) $summaryStatus }}',
            initialFlashGenerationStatus: '{{ (string) $flashGenerationStatus }}',
            initialQuizGenerationStatus: '{{ (string) $quizGenerationStatus }}',
            initialFlashRegStatus: '{{ (string) data_get($studySession->metadata, 'regeneration_status.flashcards.status', '') }}',
            initialQuizRegStatus: '{{ (string) data_get($studySession->metadata, 'regeneration_status.quiz.status', '') }}'
        })"
        x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="sf-alert-success" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="sf-alert-error" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div x-show="isGenerating()" x-cloak class="sf-alert-info space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold">Generating study materials...</p>
                    <span class="text-xs font-semibold" x-text="materialProgress() + '%'">0%</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-blue-200/70 dark:bg-blue-500/20 overflow-hidden" role="progressbar" aria-label="Material generation progress" :aria-valuenow="materialProgress()" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-blue-500 transition-all duration-500" :style="'width: ' + materialProgress() + '%'" ></div>
                </div>
                <p class="text-xs">This page refreshes automatically once summary, flashcards, and quiz reach a final state.</p>
            </div>

            @if($studySession->next_review_at)
                <div class="p-4 rounded-2xl border shadow-sm {{ $studySession->next_review_at->isPast() ? 'border-amber-300/80 bg-amber-100/80 dark:border-amber-400/60 dark:bg-amber-900/40' : 'border-indigo-300/80 bg-indigo-100/80 dark:border-indigo-400/60 dark:bg-indigo-900/45' }}">
                    <p class="text-sm font-semibold text-black dark:text-white">
                        @if($studySession->next_review_at->isPast())
                            Review due now to strengthen retention.
                        @else
                            Next review scheduled {{ $studySession->next_review_at->diffForHumans() }}.
                        @endif
                    </p>
                    <p class="text-xs mt-1 font-medium text-gray-900 dark:text-gray-100">Review count: {{ $studySession->review_count }}</p>
                </div>
            @endif

            <div class="sf-tab-bar" role="tablist" aria-label="Study session sections">
                <button @click="setTab('summary')" type="button" :class="tabClass('summary')" class="sf-tab" role="tab" :aria-selected="activeTab === 'summary'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path></svg>
                    Summary
                </button>
                <button @click="setTab('flashcards')" type="button" :class="tabClass('flashcards')" class="sf-tab" role="tab" :aria-selected="activeTab === 'flashcards'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10"></path></svg>
                    Flashcards ({{ $studySession->flashcards->count() }})
                </button>
                <button @click="setTab('quiz')" type="button" :class="tabClass('quiz')" class="sf-tab" role="tab" :aria-selected="activeTab === 'quiz'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path></svg>
                    Quiz
                </button>
                <button @click="setTab('chat')" type="button" :class="tabClass('chat')" class="sf-tab" role="tab" :aria-selected="activeTab === 'chat'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-4l-4 4z"></path></svg>
                    Chat Tutor
                </button>
            </div>

            <div x-show="isTabSwitching" x-cloak class="sf-alert-info text-xs" x-transition.opacity.duration.150ms>
                Switching section...
            </div>

            <section x-show="activeTab === 'summary'" x-transition.opacity.duration.200ms class="sf-card overflow-hidden">
                <div class="p-6 sm:p-8 space-y-6">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('study_sessions.show', ['studySession' => $studySession->id, 'tab' => 'flashcards']) }}" data-nav-loading class="sf-btn sf-btn-secondary text-xs px-3 py-2">Open Flashcards</a>
                            <a href="{{ route('study_sessions.show', ['studySession' => $studySession->id, 'tab' => 'quiz']) }}" data-nav-loading class="sf-btn sf-btn-secondary text-xs px-3 py-2">Open Quiz</a>
                        </div>
                        <a href="{{ route('history.index') }}" data-nav-loading class="sf-btn sf-btn-ghost text-xs px-3 py-2">Back to History</a>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-indigo-100 dark:border-indigo-400/30 bg-indigo-50 dark:bg-indigo-500/10 px-4 py-3">
                            <p class="text-xs tracking-[0.08em] text-indigo-700 dark:text-indigo-200 font-semibold">Flashcards</p>
                            <p class="text-sm text-indigo-900 dark:text-indigo-100 mt-1">
                                Difficulty: <span class="font-semibold">{{ ucfirst($flashPrefs['difficulty'] ?? 'average') }}</span>
                                · Target: <span class="font-semibold">{{ (int) ($flashPrefs['count'] ?? max($studySession->flashcards->count(), 15)) }}</span>
                            </p>
                            <p class="text-xs text-indigo-700 dark:text-indigo-300 mt-1">
                                Regenerated: {{ $studySession->flashcards_regenerated_at?->format('M d, Y H:i') ?? 'Not yet' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-violet-100 dark:border-violet-400/30 bg-violet-50 dark:bg-violet-500/10 px-4 py-3">
                            <p class="text-xs tracking-[0.08em] text-violet-700 dark:text-violet-200 font-semibold">Quiz</p>
                            <p class="text-sm text-violet-900 dark:text-violet-100 mt-1">
                                Difficulty: <span class="font-semibold">{{ ucfirst($quizPrefs['difficulty'] ?? 'average') }}</span>
                                · Target: <span class="font-semibold">{{ (int) ($quizPrefs['count'] ?? max($quizQuestionCount, 15)) }}</span>
                            </p>
                            <p class="text-xs text-violet-700 dark:text-violet-300 mt-1">
                                Regenerated: {{ $studySession->quiz_regenerated_at?->format('M d, Y H:i') ?? 'Not yet' }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 flex flex-wrap gap-2">
                            @forelse($studySession->tags as $tag)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">#{{ $tag->name }}</span>
                            @empty
                                <span class="text-sm text-slate-500 dark:text-slate-400">No tags yet</span>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('study_sessions.tags', $studySession->id) }}" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-4">
                            @csrf
                            @method('PUT')
                            <label for="tags" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Update tags</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input
                                    id="tags"
                                    type="text"
                                    name="tags"
                                    class="flex-1 rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-primary focus:ring-primary text-sm"
                                    value="{{ $studySession->tags->pluck('name')->implode(', ') }}"
                                    placeholder="biology, exam prep"
                                >
                                <button type="submit" class="sf-btn sf-btn-primary sm:min-w-32">Save tags</button>
                            </div>
                            @error('tags')
                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                            @enderror
                        </form>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-3">Summary</h3>
                        @if($summary)
                            <div class="prose max-w-none prose-indigo dark:prose-invert prose-headings:scroll-mt-24">
                                {!! Str::markdown($summary->content['markdown'] ?? '', [
                                    'html_input' => 'strip',
                                    'allow_unsafe_links' => false,
                                ]) !!}
                            </div>
                        @else
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-6 text-sm text-slate-600 dark:text-slate-300">
                                @if(in_array($summaryStatus, ['queued', 'processing'], true))
                                    Summary is still generating. Status: {{ ucfirst((string) $summaryStatus) }}
                                @elseif($summaryStatus === 'failed')
                                    Summary generation failed. Try creating a new session or regenerating content.
                                @else
                                    No summary available for this session. (Status: {{ $studySession->status }})
                                @endif
                            </div>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2">
                            Original Material
                            @if($studySession->input_source_type === 'pdf')
                                <span class="text-xs bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-200 px-2 py-1 rounded ml-2">PDF Extracted</span>
                            @endif
                        </h3>
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-900/50 p-4 h-64 overflow-y-auto">
                            <div class="prose prose-sm max-w-none dark:prose-invert text-slate-700 dark:text-slate-300 leading-7">
                                {!! nl2br(e($sourceMaterial)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'flashcards'" x-cloak x-transition.opacity.duration.200ms class="sf-card overflow-hidden">
                <div class="p-6 sm:p-8 text-center space-y-6" x-data="{ 
                    currentCard: 0,
                    showAnswer: false,
                    isFlipping: false,
                    isSubmitting: false,
                    cards: {{ Js::from($studySession->flashcards->map(fn($card) => [
                        'question' => $card->question,
                        'answer' => $card->answer,
                    ])) }},
                    flipCard() {
                        this.showAnswer = !this.showAnswer;
                        this.isFlipping = true;
                        setTimeout(() => this.isFlipping = false, 260);
                    },
                    previousCard() {
                        if (this.currentCard > 0) {
                            this.currentCard--;
                            this.showAnswer = false;
                        }
                    },
                    nextCard() {
                        if (this.currentCard < this.cards.length - 1) {
                            this.currentCard++;
                            this.showAnswer = false;
                        }
                    },
                    progressPercent() {
                        if (this.cards.length === 0) {
                            return 0;
                        }
                        return Math.round(((this.currentCard + 1) / this.cards.length) * 100);
                    }
                }">
                    <div class="flex flex-wrap justify-between items-center gap-3">
                        <a href="{{ route('study_sessions.show', ['studySession' => $studySession->id, 'tab' => 'summary']) }}" data-nav-loading class="sf-btn sf-btn-secondary text-xs px-3 py-2">Back to Summary</a>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Use Enter/Space to flip and Left/Right arrow keys to move.</span>
                    </div>

                    <form method="POST" action="{{ route('study_sessions.regenerate', $studySession->id) }}" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-4 text-left" @submit="if (!isSubmitting) { isSubmitting = true; }">
                        @csrf
                        <input type="hidden" name="section" value="flashcards">
                        <div class="flex flex-col gap-3 md:flex-row md:items-end">
                            <div class="md:w-48">
                                <label for="flashcard_regen_difficulty" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Difficulty</label>
                                <select id="flashcard_regen_difficulty" name="difficulty" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 text-sm focus:border-primary focus:ring-primary">
                                    <option value="easy" @selected($flashcardFormDifficulty === 'easy')>Easy</option>
                                    <option value="average" @selected($flashcardFormDifficulty === 'average')>Average</option>
                                    <option value="hard" @selected($flashcardFormDifficulty === 'hard')>Hard</option>
                                </select>
                            </div>
                            <div class="md:w-40">
                                <label for="flashcard_regen_count" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Items (15 to 50)</label>
                                <input id="flashcard_regen_count" type="number" name="count" min="15" max="50" step="1" value="{{ max(15, min(50, $flashcardFormCount)) }}" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 text-sm focus:border-primary focus:ring-primary">
                            </div>
                            <button type="submit" :disabled="isSubmitting || @js($flashRegenerationLocked)" class="sf-btn sf-btn-primary min-h-11">
                                <span x-show="!isSubmitting">Regenerate flashcards</span>
                                <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"></circle><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3"></path></svg>
                                    Queuing...
                                </span>
                            </button>
                        </div>
                        <div class="mt-3 text-xs">
                            @if(in_array($flashStatusValue, ['queued', 'processing'], true))
                                <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-500/15 px-2.5 py-1 font-medium text-amber-800 dark:text-amber-200">{{ ucfirst($flashStatusValue) }}</span>
                                @if($flashStatusUpdated)
                                    <span class="ml-2 text-amber-700 dark:text-amber-300">Updated {{ $flashStatusUpdated }}</span>
                                @endif
                            @elseif($flashStatusValue === 'failed')
                                <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-500/15 px-2.5 py-1 font-medium text-red-700 dark:text-red-200">Failed</span>
                                @if($flashStatusUpdated)
                                    <span class="ml-2 text-red-600 dark:text-red-300">{{ $flashStatusUpdated }}</span>
                                @endif
                            @elseif($studySession->flashcards_regenerated_at)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-500/15 px-2.5 py-1 font-medium text-emerald-700 dark:text-emerald-200">Regenerated at {{ $studySession->flashcards_regenerated_at->format('M d, Y H:i') }}</span>
                            @endif
                        </div>
                    </form>

                    <template x-if="cards.length > 0">
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <div class="text-sm text-slate-500 dark:text-slate-400">Card <span x-text="currentCard + 1"></span> of <span x-text="cards.length"></span></div>
                                <div class="text-sm font-semibold text-primary dark:text-indigo-200" x-text="progressPercent() + '%'">0%</div>
                            </div>
                            <div class="mb-5 h-2.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden" role="progressbar" aria-label="Flashcard progress" :aria-valuenow="progressPercent()" aria-valuemin="0" aria-valuemax="100">
                                <div class="h-full rounded-full bg-primary transition-all duration-300" :style="'width: ' + progressPercent() + '%'" ></div>
                            </div>

                            <div
                                class="sf-flashcard-scene relative w-full max-w-3xl mx-auto h-72 cursor-pointer"
                                tabindex="0"
                                @click="flipCard()"
                                @keydown.enter.prevent="flipCard()"
                                @keydown.space.prevent="flipCard()"
                                @keydown.arrow-right.prevent="nextCard()"
                                @keydown.arrow-left.prevent="previousCard()"
                                aria-label="Interactive flashcard"
                            >
                                <div class="sf-flashcard-inner w-full h-full" :class="{ 'is-flipped': showAnswer, 'is-flipping': isFlipping }">
                                    <div class="sf-flashcard-face sf-flashcard-front absolute w-full h-full flex items-center justify-center p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700">
                                        <h3 class="text-2xl font-semibold text-slate-900 dark:text-slate-100 break-words" x-text="cards[currentCard].question"></h3>
                                    </div>
                                    <div class="sf-flashcard-face sf-flashcard-back absolute w-full h-full flex items-center justify-center p-6 bg-blue-50 dark:bg-blue-500/15 border border-blue-100 dark:border-blue-400/30 text-primary-dark dark:text-blue-200 rounded-xl">
                                        <p class="text-xl break-words" x-text="cards[currentCard].answer"></p>
                                    </div>
                                </div>
                            </div>

                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-4 mb-6">Click or press Enter to flip the card</p>

                            <div class="flex justify-center gap-3">
                                <button @click="previousCard()" :disabled="currentCard === 0" class="sf-btn sf-btn-secondary">Previous</button>
                                <button @click="nextCard()" :disabled="currentCard === cards.length - 1" class="sf-btn sf-btn-primary">Next</button>
                            </div>
                        </div>
                    </template>
                    <template x-if="cards.length === 0">
                        <div class="py-12 text-slate-500 dark:text-slate-400 text-sm">
                            @if(in_array($flashGenerationStatus, ['queued', 'processing'], true))
                                Flashcards are being generated. Please wait...
                            @elseif($flashGenerationStatus === 'failed')
                                Flashcard generation failed. You can regenerate this section.
                            @else
                                No flashcards available.
                            @endif
                        </div>
                    </template>
                </div>
            </section>

            <section x-show="activeTab === 'quiz'" x-cloak x-transition.opacity.duration.200ms class="sf-card overflow-hidden">
                <div class="p-6 sm:p-8 space-y-6" x-data="{ isRegenSubmitting: false }">
                    <div class="flex flex-wrap justify-between items-center gap-3">
                        <a href="{{ route('study_sessions.show', ['studySession' => $studySession->id, 'tab' => 'summary']) }}" data-nav-loading class="sf-btn sf-btn-secondary text-xs px-3 py-2">Back to Summary</a>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Answer each question for instant feedback and explanation.</span>
                    </div>

                    <form method="POST" action="{{ route('study_sessions.regenerate', $studySession->id) }}" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-4" @submit="if (!isRegenSubmitting) { isRegenSubmitting = true; }">
                        @csrf
                        <input type="hidden" name="section" value="quiz">
                        <div class="flex flex-col gap-3 md:flex-row md:items-end">
                            <div class="md:w-48">
                                <label for="quiz_regen_difficulty" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Difficulty</label>
                                <select id="quiz_regen_difficulty" name="difficulty" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 text-sm focus:border-primary focus:ring-primary">
                                    <option value="easy" @selected($quizFormDifficulty === 'easy')>Easy</option>
                                    <option value="average" @selected($quizFormDifficulty === 'average')>Average</option>
                                    <option value="hard" @selected($quizFormDifficulty === 'hard')>Hard</option>
                                </select>
                            </div>
                            <div class="md:w-40">
                                <label for="quiz_regen_count" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Items (15 to 50)</label>
                                <input id="quiz_regen_count" type="number" name="count" min="15" max="50" step="1" value="{{ max(15, min(50, $quizFormCount)) }}" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 text-sm focus:border-primary focus:ring-primary">
                            </div>
                            <button type="submit" :disabled="isRegenSubmitting || @js($quizRegenerationLocked)" class="sf-btn sf-btn-primary min-h-11">
                                <span x-show="!isRegenSubmitting">Regenerate quiz</span>
                                <span x-show="isRegenSubmitting" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"></circle><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3"></path></svg>
                                    Queuing...
                                </span>
                            </button>
                        </div>
                        <div class="mt-3 text-xs">
                            @if(in_array($quizStatusValue, ['queued', 'processing'], true))
                                <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-500/15 px-2.5 py-1 font-medium text-amber-800 dark:text-amber-200">{{ ucfirst($quizStatusValue) }}</span>
                                @if($quizStatusUpdated)
                                    <span class="ml-2 text-amber-700 dark:text-amber-300">Updated {{ $quizStatusUpdated }}</span>
                                @endif
                            @elseif($quizStatusValue === 'failed')
                                <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-500/15 px-2.5 py-1 font-medium text-red-700 dark:text-red-200">Failed</span>
                                @if($quizStatusUpdated)
                                    <span class="ml-2 text-red-600 dark:text-red-300">{{ $quizStatusUpdated }}</span>
                                @endif
                            @elseif($studySession->quiz_regenerated_at)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-500/15 px-2.5 py-1 font-medium text-emerald-700 dark:text-emerald-200">Regenerated at {{ $studySession->quiz_regenerated_at->format('M d, Y H:i') }}</span>
                            @endif
                        </div>
                    </form>

                    @if($studySession->quizzes->count() > 0)
                        @php $quiz = $studySession->quizzes->first(); @endphp
                        <div x-data="{ 
                            currentQs: 0,
                            questions: {{ Js::from($quiz->questions) }},
                            selectedOption: null,
                            selectedIndex: null,
                            showExplanation: false,
                            score: 0,
                            isFinished: false,
                            answerState: null,
                            optionLetter(index) {
                                return String.fromCharCode(65 + index);
                            },
                            isCorrectOption(option, index) {
                                const expected = this.questions[this.currentQs].correct_answer;
                                if (/^[A-D]$/i.test(expected)) {
                                    return this.optionLetter(index) === expected.toUpperCase();
                                }
                                return option === expected;
                            },
                            submitAnswer() {
                                const expected = this.questions[this.currentQs].correct_answer;
                                const isCorrect = /^[A-D]$/i.test(expected)
                                    ? this.optionLetter(this.selectedIndex) === expected.toUpperCase()
                                    : this.selectedOption === expected;

                                this.answerState = isCorrect ? 'correct' : 'wrong';
                                if (isCorrect) {
                                    this.score++;
                                }
                                this.showExplanation = true;
                            },
                            nextQuestion() {
                                if (this.currentQs < this.questions.length - 1) {
                                    this.currentQs++;
                                    this.selectedOption = null;
                                    this.selectedIndex = null;
                                    this.showExplanation = false;
                                    this.answerState = null;
                                } else {
                                    this.isFinished = true;
                                }
                            },
                            progressPercent() {
                                if (this.questions.length === 0) {
                                    return 0;
                                }
                                return Math.round(((this.currentQs + 1) / this.questions.length) * 100);
                            }
                        }">
                            <template x-if="!isFinished">
                                <div>
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <div class="text-sm text-slate-500 dark:text-slate-400">Question <span x-text="currentQs + 1"></span> of <span x-text="questions.length"></span></div>
                                        <div class="text-sm font-semibold text-primary dark:text-indigo-200" x-text="progressPercent() + '%'">0%</div>
                                    </div>
                                    <div class="mb-6 h-2.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden" role="progressbar" aria-label="Quiz progress" :aria-valuenow="progressPercent()" aria-valuemin="0" aria-valuemax="100">
                                        <div class="h-full rounded-full bg-primary transition-all duration-300" :style="'width: ' + progressPercent() + '%'" ></div>
                                    </div>

                                    <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-6 break-words" x-text="questions[currentQs].question"></h3>
                                    
                                    <div class="space-y-3 mb-8" :class="{ 'quiz-shake': showExplanation && answerState === 'wrong' }">
                                        <template x-for="(option, idx) in questions[currentQs].options" :key="idx">
                                            <div @click="!showExplanation ? (selectedOption = option, selectedIndex = idx) : null" 
                                                 :class="{
                                                    'border-primary bg-primary/10 dark:bg-primary/20': selectedOption === option && !showExplanation,
                                                    'border-slate-200 dark:border-slate-700 hover:border-primary/60': selectedOption !== option && !showExplanation,
                                                    'border-success bg-success/10': showExplanation && isCorrectOption(option, idx),
                                                    'border-error bg-error/10': showExplanation && selectedOption === option && !isCorrectOption(option, idx),
                                                    'opacity-60 border-slate-200 dark:border-slate-700': showExplanation && !isCorrectOption(option, idx) && selectedOption !== option,
                                                    'quiz-option-correct': showExplanation && isCorrectOption(option, idx),
                                                    'quiz-option-wrong': showExplanation && selectedOption === option && !isCorrectOption(option, idx),
                                                 }"
                                                 class="p-4 border rounded-xl cursor-pointer transition-colors flex items-center bg-white dark:bg-slate-900">
                                                <input type="radio" :name="'q_'+currentQs" :value="option" x-model="selectedOption" class="h-4 w-4 text-primary border-slate-300 focus:ring-primary mr-3" :disabled="showExplanation">
                                                <span class="text-slate-800 dark:text-slate-200 break-words" x-text="option"></span>
                                                
                                                <svg x-show="showExplanation && isCorrectOption(option, idx)" class="ml-auto w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                <svg x-show="showExplanation && selectedOption === option && !isCorrectOption(option, idx)" class="ml-auto w-5 h-5 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </div>
                                        </template>
                                    </div>

                                    <div x-show="showExplanation" class="mb-6 p-4 rounded-xl border"
                                         :class="answerState === 'correct' ? 'bg-green-50 dark:bg-green-500/10 text-green-900 dark:text-green-200 border-green-200 dark:border-green-400/30' : 'bg-red-50 dark:bg-red-500/10 text-red-900 dark:text-red-200 border-red-200 dark:border-red-400/30'">
                                        <div class="font-semibold mb-1">Explanation:</div>
                                        <div x-text="questions[currentQs].explanation"></div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button x-show="!showExplanation" @click="submitAnswer" :disabled="!selectedOption" class="sf-btn sf-btn-primary">Check Answer</button>
                                        <button x-show="showExplanation" @click="nextQuestion" class="sf-btn sf-btn-primary">Continue</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="isFinished">
                                <div class="text-center py-12">
                                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-success/15 mb-6">
                                        <svg class="w-12 h-12 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path></svg>
                                    </div>
                                    <h2 class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">Quiz Completed</h2>
                                    <p class="text-xl text-slate-600 dark:text-slate-300 mb-8">You scored <span x-text="score" class="font-bold text-primary"></span> out of <span x-text="questions.length"></span></p>
                                    <button @click="currentQs = 0; score = 0; isFinished = false; selectedOption = null; selectedIndex = null; showExplanation = false; answerState = null" class="sf-btn sf-btn-secondary">Retake Quiz</button>
                                </div>
                            </template>
                        </div>
                    @else
                        <div class="text-center py-12 text-slate-500 dark:text-slate-400 text-sm">
                            @if(in_array($quizGenerationStatus, ['queued', 'processing'], true))
                                Quiz is being generated. Please wait...
                            @elseif($quizGenerationStatus === 'failed')
                                Quiz generation failed. You can regenerate this section.
                            @else
                                No quiz available for this session.
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            <section x-show="activeTab === 'chat'" x-cloak x-transition.opacity.duration.200ms class="sf-card overflow-hidden flex flex-col min-h-[60vh] h-[70vh] max-h-[82vh]">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex items-center justify-between gap-3">
                    <a href="{{ route('study_sessions.show', ['studySession' => $studySession->id, 'tab' => 'summary']) }}" data-nav-loading class="sf-btn sf-btn-secondary text-xs px-3 py-2">Back to Summary</a>
                    <div id="preset-prompts" x-data="{
                        prompts: ['Explain this simply', 'Give me real world examples', 'Quiz me on a core concept'],
                        insertPrompt(text) {
                            $dispatch('set-message', text);
                        }
                    }" class="flex space-x-2 overflow-x-auto">
                        <template x-for="p in prompts">
                            <button @click="insertPrompt(p)" class="sf-btn sf-btn-ghost whitespace-nowrap text-xs px-3 py-1.5" x-text="p"></button>
                        </template>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 space-y-4" id="chat-messages" x-data="{
                    messages: [],
                    isLoadingHistory: true,
                    historyError: '',
                    init() {
                        fetch('{{ route('chat.history', $studySession->id) }}')
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    this.messages = data.messages;
                                } else {
                                    this.historyError = 'Unable to load chat history right now.';
                                }
                                this.scrollToBottom();
                            })
                            .catch(() => {
                                this.historyError = 'Unable to load chat history right now. Please refresh and try again.';
                            })
                            .finally(() => {
                                this.isLoadingHistory = false;
                            });
                    },
                    scrollToBottom() {
                        setTimeout(() => {
                            const chatContainer = document.getElementById('chat-messages');
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                        }, 120);
                    },
                    formatTime(value) {
                        if (!value) {
                            return '';
                        }
                        return new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    }
                }" @message-added.window="messages.push($event.detail); scrollToBottom()">

                    <template x-if="isLoadingHistory">
                        <div class="space-y-3">
                            <div class="sf-skeleton h-12 w-2/3"></div>
                            <div class="sf-skeleton h-12 w-1/2 ml-auto"></div>
                            <div class="sf-skeleton h-12 w-3/4"></div>
                        </div>
                    </template>

                    <template x-if="!isLoadingHistory && historyError">
                        <div class="sf-alert-error" x-text="historyError"></div>
                    </template>

                    <template x-if="!isLoadingHistory && !historyError && messages.length === 0">
                        <div class="text-center text-slate-500 dark:text-slate-400 py-12 text-sm">Ask me anything about your study material.</div>
                    </template>

                    <template x-for="(msg, i) in messages" :key="i">
                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div class="max-w-[78%]">
                                <div :class="msg.role === 'user' ? 'sf-message-bubble-user' : 'sf-message-bubble-assistant'">
                                    <div class="text-sm whitespace-pre-wrap break-words" x-text="msg.content"></div>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400" :class="msg.role === 'user' ? 'text-right' : 'text-left'" x-text="formatTime(msg.created_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900" x-data="{
                    message: '',
                    isSending: false,
                    send() {
                        if (!this.message.trim() || this.isSending) return;
                        
                        const userMsg = this.message;
                        this.message = '';
                        this.isSending = true;
                        
                        $dispatch('message-added', {
                            role: 'user',
                            content: userMsg,
                            created_at: new Date().toISOString(),
                        });
                        
                        fetch('{{ route('chat.send', $studySession->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ message: userMsg })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                $dispatch('message-added', data.message);
                            } else {
                                $dispatch('message-added', {
                                    role: 'assistant',
                                    content: 'I could not process that message. Please try again.',
                                    created_at: new Date().toISOString(),
                                });
                            }
                        })
                        .catch(() => {
                            $dispatch('message-added', {
                                role: 'assistant',
                                content: 'Network error while sending your message. Please try again.',
                                created_at: new Date().toISOString(),
                            });
                        })
                        .finally(() => {
                            this.isSending = false;
                        });
                    }
                }" @set-message.window="message = $event.detail; $refs.input.focus()">
                    <form @submit.prevent="send" class="flex flex-col sm:flex-row gap-3">
                        <input type="text" x-ref="input" x-model="message" :disabled="isSending" placeholder="Type your question..." class="flex-1 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-primary focus:ring-primary shadow-sm">
                        <button type="submit" :disabled="isSending" class="sf-btn sf-btn-primary min-w-28">
                            <span x-show="!isSending">Send</span>
                            <span x-show="isSending" x-cloak class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"></circle><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3"></path></svg>
                                Sending...
                            </span>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        function sessionProgress(config) {
            return {
                activeTab: config.initialTab,
                sessionStatus: config.initialSessionStatus,
                summaryStatus: config.initialSummaryStatus,
                flashGenerationStatus: config.initialFlashGenerationStatus,
                quizGenerationStatus: config.initialQuizGenerationStatus,
                flashRegStatus: config.initialFlashRegStatus,
                quizRegStatus: config.initialQuizRegStatus,
                isTabSwitching: false,
                pollTimer: null,
                init() {
                    if (this.shouldPoll()) {
                        this.startPolling();
                    }
                },
                setTab(tab) {
                    if (this.activeTab === tab) {
                        return;
                    }

                    this.isTabSwitching = true;
                    this.activeTab = tab;
                    setTimeout(() => {
                        this.isTabSwitching = false;
                    }, 160);
                },
                tabClass(tab) {
                    return this.activeTab === tab ? 'sf-tab-active' : 'sf-tab-inactive';
                },
                isGenerating() {
                    return ['pending', 'processing'].includes(this.sessionStatus)
                        || ['queued', 'processing'].includes(this.summaryStatus)
                        || ['queued', 'processing'].includes(this.flashGenerationStatus)
                        || ['queued', 'processing'].includes(this.quizGenerationStatus);
                },
                materialProgress() {
                    const statuses = [
                        this.summaryStatus,
                        this.flashGenerationStatus,
                        this.quizGenerationStatus,
                    ];

                    let complete = 0;
                    statuses.forEach((status) => {
                        if (['completed', 'failed'].includes(status)) {
                            complete += 1;
                        }
                    });

                    if (!this.isGenerating() && this.sessionStatus === 'completed') {
                        return 100;
                    }

                    return Math.round((complete / statuses.length) * 100);
                },
                shouldPoll() {
                    return ['pending', 'processing'].includes(this.sessionStatus)
                        || ['queued', 'processing'].includes(this.summaryStatus)
                        || ['queued', 'processing'].includes(this.flashGenerationStatus)
                        || ['queued', 'processing'].includes(this.quizGenerationStatus)
                        || ['queued', 'processing'].includes(this.flashRegStatus)
                        || ['queued', 'processing'].includes(this.quizRegStatus);
                },
                startPolling() {
                    if (this.pollTimer !== null) {
                        return;
                    }

                    this.pollNow();
                    this.pollTimer = setInterval(() => this.pollNow(), 5000);
                },
                stopPolling() {
                    if (this.pollTimer !== null) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                },
                async pollNow() {
                    try {
                        const previousSessionStatus = this.sessionStatus;
                        const previousSummaryStatus = this.summaryStatus;
                        const previousFlashGenerationStatus = this.flashGenerationStatus;
                        const previousQuizGenerationStatus = this.quizGenerationStatus;
                        const previousFlashRegStatus = this.flashRegStatus;
                        const previousQuizRegStatus = this.quizRegStatus;

                        const response = await fetch(config.pollUrl, {
                            headers: {
                                Accept: 'application/json'
                            }
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();
                        this.sessionStatus = data.session_status || this.sessionStatus;
                        this.summaryStatus = data?.generation_status?.summary?.status || this.summaryStatus;
                        this.flashGenerationStatus = data?.generation_status?.flashcards?.status || this.flashGenerationStatus;
                        this.quizGenerationStatus = data?.generation_status?.quiz?.status || this.quizGenerationStatus;
                        this.flashRegStatus = data?.regeneration_status?.flashcards?.status || '';
                        this.quizRegStatus = data?.regeneration_status?.quiz?.status || '';

                        const sessionCompletedNow = previousSessionStatus !== this.sessionStatus
                            && ['completed', 'failed'].includes(this.sessionStatus);

                        const summaryCompletedNow = ['queued', 'processing'].includes(previousSummaryStatus)
                            && ['completed', 'failed'].includes(this.summaryStatus);

                        const flashGenerationCompletedNow = ['queued', 'processing'].includes(previousFlashGenerationStatus)
                            && ['completed', 'failed'].includes(this.flashGenerationStatus);

                        const quizGenerationCompletedNow = ['queued', 'processing'].includes(previousQuizGenerationStatus)
                            && ['completed', 'failed'].includes(this.quizGenerationStatus);

                        const flashcardRegeneratedNow = ['queued', 'processing'].includes(previousFlashRegStatus)
                            && ['completed', 'failed', ''].includes(this.flashRegStatus);

                        const quizRegeneratedNow = ['queued', 'processing'].includes(previousQuizRegStatus)
                            && ['completed', 'failed', ''].includes(this.quizRegStatus);

                        if (
                            sessionCompletedNow
                            || summaryCompletedNow
                            || flashGenerationCompletedNow
                            || quizGenerationCompletedNow
                            || flashcardRegeneratedNow
                            || quizRegeneratedNow
                        ) {
                            window.location.reload();
                            return;
                        }

                        if (!this.shouldPoll()) {
                            this.stopPolling();
                        }
                    } catch (error) {
                        // Keep polling on transient network errors.
                    }
                }
            };
        }
    </script>
</x-app-layout>
