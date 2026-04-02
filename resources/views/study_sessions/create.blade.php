<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 leading-tight">Create New Study Session</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Turn notes or PDFs into a summary, flashcards, quiz, and guided chat.</p>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ mode: 'text', isSubmitting: false }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="sf-card overflow-hidden">      
                <div class="p-6 text-slate-900 dark:text-slate-100">

                    <div class="mb-6 rounded-xl bg-gradient-to-r from-indigo-50 to-sky-50 dark:from-indigo-500/10 dark:to-sky-500/10 border border-indigo-100 dark:border-indigo-400/30 p-4">
                        <p class="text-sm text-indigo-900 dark:text-indigo-200 font-medium">Study smarter with spaced repetition</p>
                        <p class="text-xs text-indigo-700 dark:text-indigo-300 mt-1">Every session automatically gets a review reminder schedule. Add tags now to keep your subjects organized.</p>
                    </div>

                    @if (session('error'))
                        <div class="mb-4 bg-error bg-opacity-10 border-l-4 border-error text-error p-4 rounded" role="alert">
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif

                    <form action="{{ route('study_sessions.store') }}" method="POST" id="create-session-form" enctype="multipart/form-data" x-show="!isSubmitting" @submit="if($el.checkValidity()) { isSubmitting = true; }" class="space-y-6">
                        @csrf
                        
                        <input type="hidden" name="input_source_type" x-model="mode">

                        <div class="mb-6 inline-flex rounded-xl border border-slate-200 dark:border-slate-700 p-1 bg-slate-50 dark:bg-slate-900/40">
                            <button type="button" 
                                @click="mode = 'text'" 
                                :class="mode === 'text' ? 'bg-primary text-white shadow-sm' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                class="px-4 py-2 rounded-lg font-medium transition-colors dark:text-slate-200 dark:hover:bg-slate-800">
                                📝 Paste Text
                            </button>
                            <button type="button" 
                                @click="mode = 'pdf'" 
                                :class="mode === 'pdf' ? 'bg-primary text-white shadow-sm' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                class="px-4 py-2 rounded-lg font-medium transition-colors dark:text-slate-200 dark:hover:bg-slate-800">
                                📄 Upload PDF
                            </button>
                        </div>

                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Session Title</label>
                            <input type="text" name="title" id="title" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg" placeholder="e.g. Chapter 4: Cell Biology" required value="{{ old('title') }}">
                            @error('title')
                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="tags" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tags (optional)</label>
                            <input type="text" name="tags" id="tags" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg" placeholder="biology, exam prep, chapter-4" value="{{ old('tags') }}">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Separate tags with commas. Up to 8 tags are kept.</p>
                            @error('tags')
                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 p-4 sm:p-5">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Generation settings</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Choose difficulty and item count for both Flashcards and Quiz. Minimum is 15 and maximum is 50.</p>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Flashcards</h4>
                                    <div class="mt-3 space-y-3">
                                        <div>
                                            <label for="flashcard_difficulty" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Difficulty</label>
                                            <select id="flashcard_difficulty" name="flashcard_difficulty" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg" required>
                                                <option value="easy" @selected(old('flashcard_difficulty', 'average') === 'easy')>Easy</option>
                                                <option value="average" @selected(old('flashcard_difficulty', 'average') === 'average')>Average</option>
                                                <option value="hard" @selected(old('flashcard_difficulty', 'average') === 'hard')>Hard</option>
                                            </select>
                                            @error('flashcard_difficulty')
                                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="flashcard_count" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Number of flashcards</label>
                                            <input
                                                type="number"
                                                id="flashcard_count"
                                                name="flashcard_count"
                                                min="15"
                                                max="50"
                                                step="1"
                                                required
                                                value="{{ old('flashcard_count', 15) }}"
                                                class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg"
                                            >
                                            @error('flashcard_count')
                                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Quiz</h4>
                                    <div class="mt-3 space-y-3">
                                        <div>
                                            <label for="quiz_difficulty" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Difficulty</label>
                                            <select id="quiz_difficulty" name="quiz_difficulty" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg" required>
                                                <option value="easy" @selected(old('quiz_difficulty', 'average') === 'easy')>Easy</option>
                                                <option value="average" @selected(old('quiz_difficulty', 'average') === 'average')>Average</option>
                                                <option value="hard" @selected(old('quiz_difficulty', 'average') === 'hard')>Hard</option>
                                            </select>
                                            @error('quiz_difficulty')
                                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="quiz_count" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Number of quiz questions</label>
                                            <input
                                                type="number"
                                                id="quiz_count"
                                                name="quiz_count"
                                                min="15"
                                                max="50"
                                                step="1"
                                                required
                                                value="{{ old('quiz_count', 15) }}"
                                                class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg"
                                            >
                                            @error('quiz_count')
                                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6" x-show="mode === 'text'" x-cloak>
                            <label for="input_text" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Study Material</label>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Paste your lecture notes, textbook excerpts, or article text here.</p>
                            <textarea name="input_text" id="input_text" rows="10" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg font-mono text-sm" placeholder="Paste your text here..." :required="mode === 'text'">{{ old('input_text') }}</textarea>
                            <div class="mt-2 flex justify-between text-xs text-slate-500 dark:text-slate-400">
                                <span>Minimum 50 characters required</span>     
                            </div>
                            @error('input_text')
                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6" x-show="mode === 'pdf'" x-cloak>
                            <label for="pdf_file" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Upload PDF Document</label>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Upload lecture slides, standard PDFs (up to 10MB). Scanned PDFs without OCR are not supported.</p>
                            <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 rounded-lg p-2 border" :required="mode === 'pdf'">
                            @error('pdf_file')
                                <p class="mt-2 text-sm text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                            <a href="{{ route('dashboard') }}" class="sf-btn sf-btn-secondary" data-nav-loading>
                                Cancel
                            </a>
                            <button type="submit" id="submit-btn" class="sf-btn sf-btn-primary">
                                Generate Study Materials
                            </button>
                        </div>
                    </form>

                    <!-- Loading State -->
                    <div id="loading-state" x-show="isSubmitting" x-cloak class="flex flex-col items-center justify-center py-12">
                        <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-primary mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">AI is analyzing your material...</h3>
                        <p class="text-slate-500 dark:text-slate-400 mt-2" x-show="mode === 'pdf'">Extracting text from PDF and generating summary and key concepts.</p>
                        <p class="text-slate-500 dark:text-slate-400 mt-2" x-show="mode === 'text'">Generating summary and key concepts. This might take a few seconds.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
