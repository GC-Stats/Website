{{--
    GC-Stats — Unverified email banner

    Shown at the top of every page to a signed-in user whose email isn't
    verified yet. Same "resend" action as the full verify-email notice page
    (Auth\FortifyServiceProvider / routes/verification.send), just reachable
    from anywhere instead of only after being redirected there.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@auth
    @unless (auth()->user()->hasVerifiedEmail())
        <div role="alert" class="relative z-[60] bg-[var(--brand-yellow)] text-black mb-4">
            <div class="max-w-7xl mx-auto px-4 md:px-6 py-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center">
                @if (session('status') === 'verification-link-sent')
                    <span class="text-xs font-bold uppercase tracking-widest">{{ __('auth.verify_email.sent') }}</span>
                @else
                    <span class="text-xs font-bold uppercase tracking-widest">{{ __('auth.verify_email.banner_message') }}</span>
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="text-xs font-black uppercase tracking-widest underline underline-offset-2 hover:opacity-70 transition">
                            {{ __('auth.verify_email.resend_submit') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endunless
@endauth
