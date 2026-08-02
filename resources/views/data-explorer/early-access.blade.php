{{--
    GC-Stats — Data Explorer early access placeholder

    Shown instead of the actual Data Explorer screens while the feature is
    gated behind services.data_explorer.enabled (DATA_EXPLORER_ENABLED).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('data_explorer.early_access.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 text-center space-y-6 py-12">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[var(--brand-yellow)]/40 bg-[var(--brand-yellow)]/10 text-[11px] font-bold uppercase tracking-widest text-[var(--brand-yellow)]">
                @svg('fas-flask', 'w-3 h-3', ['aria-hidden' => 'true'])
                {{ __('data_explorer.early_access.badge') }}
            </span>

            <h1 class="text-4xl font-black uppercase tracking-tighter text-white">
                {{ __('data_explorer.early_access.title') }}
            </h1>

            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4 text-left">
                <p class="text-sm text-gray-300">{{ __('data_explorer.early_access.body') }}</p>
                <p class="text-sm text-gray-500">{{ __('data_explorer.early_access.contact') }}</p>
            </div>

            <a href="{{ route('developers') }}" class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition-all group">
                <span class="w-0 h-[1px] bg-[var(--brand-yellow)] transition-all group-hover:w-3"></span>
                {{ __('data_explorer.early_access.dev_doc_link') }}
            </a>
        </section>
    </div>
@endsection
