@php
    $isDueReviewsView = request()->routeIs('history.index') && (request('due') == 1 || request('sort') === 'review_due');
@endphp

<nav
    x-data="{
        open: false,
        darkMode: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            document.documentElement.classList.toggle('dark', this.darkMode);
            localStorage.setItem('studyforge-theme', this.darkMode ? 'dark' : 'light');
        }
    }"
    class="sf-navbar sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/85"
>
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center gap-2">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-xl px-2 py-1.5 text-slate-900 dark:text-white" data-nav-loading>
                        <x-application-logo class="block h-9 w-auto fill-current" />
                        <span class="hidden md:block text-sm font-semibold tracking-wide">StudyForge</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden gap-1 sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" data-nav-loading>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('study_sessions.create')" :active="request()->routeIs('study_sessions.create')" data-nav-loading>
                        {{ __('New Session') }}
                    </x-nav-link>
                    <x-nav-link :href="route('history.index')" :active="request()->routeIs('history.index') && ! $isDueReviewsView" data-nav-loading>
                        {{ __('History') }}
                    </x-nav-link>
                    <x-nav-link :href="route('history.index', ['due' => 1, 'sort' => 'review_due'])" :active="$isDueReviewsView" data-nav-loading>
                        {{ __('Due Reviews') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                <button
                    type="button"
                    @click="toggleTheme"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:-translate-y-0.5 hover:border-primary/30 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:text-indigo-200"
                    :aria-pressed="darkMode"
                    :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                >
                    <svg x-show="!darkMode" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1.5m0 15V21m8.5-9H19m-14 0H3.5m14.2 6.2-1.1-1.1M7.4 7.4 6.3 6.3m11.4 0-1.1 1.1M7.4 16.6l-1.1 1.1M15.5 12A3.5 3.5 0 1 1 8.5 12a3.5 3.5 0 0 1 7 0Z"></path>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 14.7A8.8 8.8 0 1 1 9.3 3a7 7 0 1 0 11.7 11.7Z"></path>
                    </svg>
                </button>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex h-10 items-center gap-2 px-3 py-2 border border-slate-200 text-sm leading-4 font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30 transition ease-in-out duration-150">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-indigo-500/20 dark:text-indigo-200 text-xs font-semibold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 text-slate-500 dark:text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>

                <span class="sf-pulse-badge" aria-label="AI pulse status">
                    <span class="sf-pulse-dot" aria-hidden="true"></span>
                    AI Pulse Active
                </span>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden gap-2">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-700 dark:text-slate-300 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus:bg-slate-100 dark:focus:bg-slate-800 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="px-4 pt-3">
            <span class="sf-pulse-badge">
                <span class="sf-pulse-dot" aria-hidden="true"></span>
                AI Pulse Active
            </span>
        </div>
        <div class="pt-2 pb-3 space-y-1 px-4">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" data-nav-loading>
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('study_sessions.create')" :active="request()->routeIs('study_sessions.create')" data-nav-loading>
                {{ __('New Session') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('history.index')" :active="request()->routeIs('history.index') && ! $isDueReviewsView" data-nav-loading>
                {{ __('History') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('history.index', ['due' => 1, 'sort' => 'review_due'])" :active="$isDueReviewsView" data-nav-loading>
                {{ __('Due Reviews') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-4 border-t border-slate-200 dark:border-slate-800">
            <div class="px-4">
                <div class="font-medium text-base text-slate-800 dark:text-slate-100">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-500 dark:text-slate-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1 px-4">
                <button
                    type="button"
                    @click="toggleTheme"
                    class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                    :aria-pressed="darkMode"
                >
                    <svg x-show="!darkMode" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1.5m0 15V21m8.5-9H19m-14 0H3.5m14.2 6.2-1.1-1.1M7.4 7.4 6.3 6.3m11.4 0-1.1 1.1M7.4 16.6l-1.1 1.1M15.5 12A3.5 3.5 0 1 1 8.5 12a3.5 3.5 0 0 1 7 0Z"></path>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 14.7A8.8 8.8 0 1 1 9.3 3a7 7 0 1 0 11.7 11.7Z"></path>
                    </svg>
                    <span x-text="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"></span>
                </button>

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
