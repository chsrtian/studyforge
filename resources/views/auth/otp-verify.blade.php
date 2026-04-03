<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StudyForge') }} - OTP Verification</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f1eff7] font-sans text-slate-900 antialiased">
    <div
        x-data="{
            secondsLeft: {{ (int) $secondsLeft }},
            tick() {
                const current = Number(this.secondsLeft);
                const normalized = Number.isFinite(current) ? Math.floor(current) : 0;
                if (normalized > 0) this.secondsLeft = normalized - 1;
            },
            format() {
                const current = Number(this.secondsLeft);
                const total = Number.isFinite(current) ? Math.max(0, Math.floor(current)) : 0;
                const m = Math.floor(total / 60);
                const s = total % 60;
                return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            },
            otpValues: ['', '', '', '', '', ''],
            get otpCombined() { return this.otpValues.join(''); },
            focusAt(index) {
                const element = document.getElementById('otp-digit-' + index);
                if (element) element.focus();
            },
            handleInput(e, index) {
                const sanitized = (e.target.value || '').replace(/\D/g, '').slice(-1);
                this.otpValues[index] = sanitized;
                if (sanitized && index < 5) this.focusAt(index + 1);
            },
            handleKey(e, index) {
                if (e.key === 'Backspace' && !this.otpValues[index] && index > 0) this.focusAt(index - 1);
            },
            handlePaste(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6).split('');
                if (pastedData.length) {
                    pastedData.forEach((char, i) => { if (i < 6) this.otpValues[i] = char; });
                    this.focusAt(Math.min(pastedData.length, 5));
                }
            }
        }"
        x-init="
            const timer = setInterval(() => tick(), 1000);
            $watch('otpValues', () => { document.getElementById('otp').value = otpCombined; });
            $watch('secondsLeft', value => { if (value <= 0) clearInterval(timer); });
            document.getElementById('otp').value = otpCombined;
        "
        class="min-h-screen"
    >
        <header class="h-14 border-b border-[#eceaf2] bg-white">
            <div class="mx-auto flex h-full w-full max-w-7xl items-center justify-between px-5 sm:px-8">
                <a href="{{ url('/') }}" class="text-3xl font-bold tracking-tight text-indigo-700">StudyForge</a>
                <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-500 text-white" aria-label="Help">
                    <span class="text-sm font-semibold leading-none">?</span>
                </button>
            </div>
        </header>

        <main class="relative px-4 pb-12 pt-12 sm:pt-16">
            <section class="mx-auto w-full max-w-[360px]">
                <div class="mb-6 flex justify-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-[#8462e2] to-[#9b8bf5] px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-white shadow-[0_8px_20px_rgba(120,94,224,0.32)]">
                        <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 2L13.92 7.08L19 9L13.92 10.92L12 16L10.08 10.92L5 9L10.08 7.08L12 2Z" fill="currentColor" />
                        </svg>
                        SECURE VERIFICATION ACTIVE
                    </span>
                </div>

                <div class="rounded-xl border border-[#eae7f2] border-l-[#d7d4ec] border-l-2 bg-[#fefefe] px-7 pb-8 pt-8 shadow-[0_12px_24px_rgba(15,23,42,0.06)]">
                    <h1 class="text-[34px] font-extrabold tracking-tight text-slate-800">Verify Your Identity</h1>
                    <p class="mt-3 text-[14px] leading-8 text-slate-600">
                        To maintain your academic integrity, we've sent a 6-digit secure code to your registered email address.
                    </p>

                    @if (session('status') === 'otp_sent')
                        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                            Verification code sent successfully.
                        </div>
                    @endif

                    @if (session('status') === 'otp_resent')
                        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                            New verification code sent.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('otp.verify.store') }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="otp" id="otp" :value="otpCombined">

                        <div>
                            <div class="flex items-center justify-between gap-2" role="group" aria-label="One-time password input">
                                <template x-for="(val, index) in 6" :key="index">
                                    <input
                                        type="password"
                                        maxlength="1"
                                        x-model="otpValues[index]"
                                        x-bind:id="'otp-digit-' + index"
                                        @input="handleInput($event, index)"
                                        @keydown="handleKey($event, index)"
                                        @paste="handlePaste($event)"
                                        autocomplete="one-time-code"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        placeholder="•"
                                        class="h-[38px] w-[38px] border-0 bg-[#e3e2e9] p-0 text-center text-xl font-bold text-slate-700 placeholder:text-slate-500 focus:bg-[#dddae8] focus:outline-none focus:ring-0 sm:h-[40px] sm:w-[40px]"
                                    >
                                </template>
                            </div>
                            @error('otp')
                                <p class="mt-2 text-center text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#5960ce] to-[#7f87f4] px-4 py-3.5 text-[17px] font-semibold text-white shadow-[0_10px_24px_rgba(89,96,206,0.35)] transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <span>Verify &amp; Continue</span>
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M4 10h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 6l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="inline-flex items-center gap-2 text-[13px] font-medium uppercase tracking-[0.14em] text-slate-600">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11a.75.75 0 10-1.5 0v3.2c0 .2.08.39.22.53l2 2a.75.75 0 101.06-1.06l-1.78-1.78V7z" clip-rule="evenodd"/>
                            </svg>
                            RESEND CODE IN
                            <span class="font-semibold text-indigo-700" x-text="format()"></span>
                        </p>

                        <form method="POST" action="{{ route('otp.verify.resend') }}" class="mt-3">
                            @csrf
                            <button
                                type="submit"
                                :disabled="secondsLeft > 0"
                                class="text-sm font-semibold transition"
                                :class="secondsLeft > 0 ? 'cursor-not-allowed text-slate-400' : 'text-indigo-700 hover:text-indigo-600'"
                            >
                                Resend Code
                            </button>
                        </form>
                    </div>

                    <div class="mt-8 border-t border-[#ebe8f2] pt-7 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.35em] text-slate-400">SECURED BY STUDYFORGE INTELLECT*</p>
                    </div>
                </div>

                <div class="mx-auto mt-6 h-9 w-full rounded-full bg-[radial-gradient(circle_at_center,#f9f9fb_0%,#d7d7df_36%,#9e9ea7_100%)] opacity-90"></div>

                <p class="mt-28 text-center text-xs text-slate-400">&copy; 2024 StudyForge. Institutional Security Standards V2.4</p>
            </section>
        </main>
    </div>
</body>
</html>
