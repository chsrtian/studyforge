<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StudyForge') }} - Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-indigo-50 font-sans text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 pt-6 sm:px-10 sm:pt-7">
        <header class="flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-3xl font-bold tracking-tight text-indigo-700">
                StudyForge
            </a>

            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="text-base font-medium text-indigo-700 transition hover:text-indigo-600">
                    Create Account
                </a>
            @endif
        </header>

        <main class="flex flex-1 items-center justify-center py-10 sm:py-14">
            <section class="w-full max-w-lg">
                <div class="relative rounded-2xl border border-slate-200 bg-white px-8 pb-8 pt-10 shadow-lg sm:px-10 sm:pb-10 sm:pt-12">
                    <div class="absolute -left-3 -top-3 h-8 w-8 rounded-lg bg-indigo-100"></div>

                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 text-indigo-600" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 2L13.92 7.08L19 9L13.92 10.92L12 16L10.08 10.92L5 9L10.08 7.08L12 2Z" fill="currentColor" />
                            <path d="M18 12L18.92 14.58L21.5 15.5L18.92 16.42L18 19L17.08 16.42L14.5 15.5L17.08 14.58L18 12Z" fill="currentColor" opacity="0.8" />
                        </svg>
                    </div>

                    <div class="mt-6 text-center">
                        <h1 class="text-5xl font-bold tracking-tight text-slate-900 sm:text-6xl">
                            Welcome Back
                        </h1>
                        <p class="mx-auto mt-4 max-w-sm text-base text-slate-600 sm:text-lg">
                            Continue your intellectual journey with StudyForge AI.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <ul class="list-disc space-y-1 pl-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    <a href="{{ route('auth.google.redirect') }}" class="mt-8 inline-flex w-full items-center justify-center gap-3 rounded-lg bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <svg viewBox="0 0 48 48" class="h-5 w-5" aria-hidden="true">
                            <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.655 32.659 29.303 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.959 3.041l5.657-5.657C34.046 6.053 29.368 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.651-.389-3.917z"/>
                            <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.959 3.041l5.657-5.657C34.046 6.053 29.368 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                            <path fill="#4CAF50" d="M24 44c5.237 0 9.924-2.003 13.509-5.27l-6.237-5.272C29.278 35.091 26.76 36 24 36c-5.282 0-9.619-3.317-11.281-7.946l-6.522 5.025C9.493 39.556 16.227 44 24 44z"/>
                            <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-1.245 3.411-3.732 6.123-6.031 7.458l.004-.003 6.237 5.272C34.807 39.097 40 34 40 24c0-1.341-.138-2.651-.389-3.917z"/>
                        </svg>
                        <span>Sign in with Google</span>
                    </a>

                    <div class="my-7 flex items-center gap-4">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">OR USE EMAIL</p>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="text-xs font-semibold uppercase tracking-widest text-slate-700">EMAIL ADDRESS</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="name@university.edu"
                                class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-100 px-4 py-3 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-indigo-500"
                            >
                            @error('email')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="text-xs font-semibold uppercase tracking-widest text-slate-700">PASSWORD</label>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-700 transition hover:text-indigo-500">
                                        Forgot Password?
                                    </a>
                                @endif
                            </div>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Password"
                                class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-100 px-4 py-3 text-base text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-indigo-500"
                            >
                            @error('password')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <label for="remember_me" class="flex items-center gap-2 text-sm text-slate-700">
                            <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" {{ old('remember') ? 'checked' : '' }}>
                            <span>Remember me</span>
                        </label>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 py-3.5 text-lg font-medium text-white shadow-md transition hover:from-indigo-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <span>Sign In</span>
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M4 10h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 6l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </form>

                    <div class="mt-8 flex justify-center">
                        <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.25em] text-indigo-700">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M12 2L13.92 7.08L19 9L13.92 10.92L12 16L10.08 10.92L5 9L10.08 7.08L12 2Z" fill="currentColor" />
                            </svg>
                            AI PULSE ACTIVE
                        </span>
                    </div>
                </div>

                <div class="mt-6 flex items-start justify-between gap-6 px-1">
                    <div class="max-w-[16rem]">
                        <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700">Academic Integrity</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Our AI models are trained to augment, not replace, human cognitive labor.
                        </p>
                    </div>

                    <div class="pt-1 text-xs text-slate-500">
                        <a href="#" class="font-medium text-slate-500 transition hover:text-slate-700">Trust Center</a>
                        <span class="mx-2">Status</span>
                    </div>
                </div>
            </section>
        </main>

        <footer class="mt-8 border-t border-slate-200 py-6">
            <div class="flex flex-col gap-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ url('/') }}" class="text-3xl font-bold tracking-tight text-indigo-700">
                    StudyForge
                </a>

                <nav class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-medium uppercase tracking-widest text-slate-500">
                    <a href="#contact" class="transition hover:text-slate-700">CONTACT</a>
                    <a href="#privacy" class="transition hover:text-slate-700">PRIVACY</a>
                    <a href="#terms" class="transition hover:text-slate-700">TERMS</a>
                    <a href="#support" class="transition hover:text-slate-700">SUPPORT</a>
                </nav>

                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">
                    &copy; 2026 STUDYFORGE. ACADEMIC PRECISION.
                </p>
            </div>
        </footer>
    </div>
</body>
</html>