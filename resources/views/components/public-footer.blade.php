@props([
    'wrapperClass' => 'mt-8 border-t border-slate-200 py-6 dark:border-slate-700',
    'containerClass' => 'flex flex-col gap-4 text-sm sm:flex-row sm:items-center sm:justify-between',
    'brandClass' => 'text-3xl font-bold tracking-tight text-indigo-700 dark:text-indigo-300',
    'navClass' => 'flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-medium uppercase tracking-widest text-slate-500 dark:text-slate-300',
    'yearClass' => 'text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-300',
    'yearLabel' => 'STUDYFORGE. ACADEMIC PRECISION.',
])

<footer x-data="{ contactModalOpen: false }" class="{{ $wrapperClass }}" @keydown.escape.window="contactModalOpen = false">
    <div class="{{ $containerClass }}">
        <a href="{{ url('/') }}" class="{{ $brandClass }}">
            StudyForge
        </a>

        <nav class="{{ $navClass }}">
            <button type="button" @click="contactModalOpen = true" class="transition hover:text-slate-700 dark:hover:text-slate-100">CONTACT</button>
            <a href="{{ route('help.privacy') }}" class="transition hover:text-slate-700 dark:hover:text-slate-100">PRIVACY</a>
            <a href="{{ route('help.terms') }}" class="transition hover:text-slate-700 dark:hover:text-slate-100">TERMS</a>
            <a href="{{ route('help.support') }}" class="transition hover:text-slate-700 dark:hover:text-slate-100">SUPPORT</a>
        </nav>

        <p class="{{ $yearClass }}">
            &copy; {{ date('Y') }} {{ $yearLabel }}
        </p>
    </div>

    <div x-show="contactModalOpen" style="display: none;" class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-slate-950/60" @click="contactModalOpen = false"></div>

        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:text-indigo-300">Contact</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900 dark:text-slate-100">StudyForge Support Contact</h3>
                </div>

                <button type="button" @click="contactModalOpen = false" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                    CLOSE
                </button>
            </div>

            <div class="mt-5 space-y-3 text-sm text-slate-700 dark:text-slate-200">
                <p><span class="font-semibold">Name:</span> Christian Roble</p>
                <p><span class="font-semibold">Email:</span> roblechristian12@gmail.com</p>
                <p><span class="font-semibold">Contact:</span> +639911876871</p>
            </div>
        </div>
    </div>
</footer>