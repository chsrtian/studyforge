<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StudyForge') }} - Create Account</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f3f1f8] font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col">
        <main class="flex-1 px-4 pb-10 pt-10 sm:px-6 sm:pt-12">
            <section class="mx-auto w-full max-w-[520px] text-center">
                <h1 class="text-[50px] font-extrabold tracking-tight text-slate-900 sm:text-[56px]">StudyForge</h1>
                <p class="mt-3 text-[18px] text-slate-600 sm:text-[19px]">Join the next generation of academic excellence.</p>

                <div class="mt-5 flex justify-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-[#8f6ce7] to-[#9f8bf4] px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.22em] text-white shadow-[0_8px_20px_rgba(123,97,225,0.35)]">
                        <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 2L13.92 7.08L19 9L13.92 10.92L12 16L10.08 10.92L5 9L10.08 7.08L12 2Z" fill="currentColor" />
                        </svg>
                        AI PULSE ACTIVE
                    </span>
                </div>

                <div class="mt-6 rounded-2xl border border-[#ebe8f3] bg-[#fdfdfe] px-7 pb-7 pt-8 text-left shadow-[0_10px_24px_rgba(15,23,42,0.04)] sm:px-8 sm:pb-8 sm:pt-9">
                    @if ($errors->any())
                        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <ul class="list-disc space-y-1 pl-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <a
                        href="{{ route('auth.google.redirect') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#f2eff8] px-4 py-3 text-[17px] font-medium text-slate-800 transition hover:bg-[#ebe7f3]"
                    >
                        <svg viewBox="0 0 48 48" class="h-5 w-5" aria-hidden="true">
                            <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.655 32.659 29.303 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.959 3.041l5.657-5.657C34.046 6.053 29.368 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.651-.389-3.917z"/>
                            <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.959 3.041l5.657-5.657C34.046 6.053 29.368 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                            <path fill="#4CAF50" d="M24 44c5.237 0 9.924-2.003 13.509-5.27l-6.237-5.272C29.278 35.091 26.76 36 24 36c-5.282 0-9.619-3.317-11.281-7.946l-6.522 5.025C9.493 39.556 16.227 44 24 44z"/>
                            <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-1.245 3.411-3.732 6.123-6.031 7.458l.004-.003 6.237 5.272C34.807 39.097 40 34 40 24c0-1.341-.138-2.651-.389-3.917z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </a>

                    <div class="my-8 flex items-center gap-4">
                        <div class="h-px flex-1 bg-[#e3dfec]"></div>
                        <span class="text-xs font-medium uppercase tracking-[0.24em] text-slate-500">OR</span>
                        <div class="h-px flex-1 bg-[#e3dfec]"></div>
                    </div>

                    <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ password: '', showPassword: false }">
                        @csrf

                        <div>
                            <label for="name" class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">FULL NAME</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Dr. Julian Vane"
                                class="mt-2 block w-full rounded-none border-0 bg-[#e8e5ee] px-4 py-3.5 text-[15px] text-slate-700 placeholder:text-slate-400 focus:bg-[#e4e0eb] focus:outline-none focus:ring-0"
                            >
                            @error('name')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">UNIVERSITY EMAIL</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="username"
                                placeholder="scholar@oxford.ac.uk"
                                class="mt-2 block w-full rounded-none border-0 bg-[#e8e5ee] px-4 py-3.5 text-[15px] text-slate-700 placeholder:text-slate-400 focus:bg-[#e4e0eb] focus:outline-none focus:ring-0"
                            >
                            @error('email')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">PASSWORD</label>
                            <div class="mt-2 flex items-center bg-[#e8e5ee]">
                                <input
                                    id="password"
                                    name="password"
                                    x-model="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    placeholder="............"
                                    class="block w-full border-0 bg-transparent px-4 py-3.5 text-[15px] text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                                >
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="me-3 inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-500 transition hover:text-slate-700"
                                    aria-label="Toggle password visibility"
                                >
                                    <svg x-show="!showPassword" viewBox="0 0 24 24" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                    </svg>
                                    <svg x-show="showPassword" x-cloak viewBox="0 0 24 24" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M10.6 10.7a2 2 0 1 0 2.8 2.8" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M6.7 6.8C4.2 8.4 2.8 11 2 12c.8 1 3.5 6 10 6 2 0 3.7-.5 5.2-1.3" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M14.9 5.3c4.2 1.1 6.5 5.2 7.1 6.7-.5.8-2.2 3.8-5.4 5.4" stroke="currentColor" stroke-width="1.5"/>
                                    </svg>
                                </button>
                            </div>
                            <input type="hidden" name="password_confirmation" x-model="password">
                            @error('password')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                            @error('password_confirmation')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#5960ce] to-[#7f87f4] px-5 py-4 text-[17px] font-semibold text-white shadow-[0_10px_22px_rgba(92,100,214,0.35)] transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Create Account
                        </button>
                    </form>

                    <p class="mt-8 text-center text-[16px] text-slate-600">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-semibold text-indigo-700 transition hover:text-indigo-600">Back to Login</a>
                    </p>
                </div>

                <p class="mx-auto mt-7 max-w-[360px] text-center text-[13px] leading-6 text-slate-500">
                    By creating an account, you agree to our
                    <a href="#" class="underline decoration-slate-400 underline-offset-2">Academic Integrity Policy</a>
                    and
                    <a href="#" class="underline decoration-slate-400 underline-offset-2">Terms of Service</a>.
                </p>
            </section>
        </main>

        <footer class="border-t border-[#dfddea] bg-[#eef0f6]">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-6 py-6 sm:px-10 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-[32px] font-semibold text-slate-900">StudyForge AI</p>
                    <p class="mt-2 text-[14px] text-slate-500">&copy; 2024 StudyForge AI. Scholarly Excellence Defined.</p>
                </div>

                <nav class="flex flex-wrap items-center gap-x-8 gap-y-2 text-[14px] text-slate-500">
                    <a href="#" class="transition hover:text-slate-700">Privacy Policy</a>
                    <a href="#" class="transition hover:text-slate-700">Terms of Service</a>
                    <a href="#" class="transition hover:text-slate-700">Academic Integrity</a>
                    <a href="#" class="transition hover:text-slate-700">Support</a>
                </nav>
            </div>
        </footer>
    </div>
</body>
</html>
