{{--
    GC-Stats — Data Explorer settings

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@section('title', __('data_explorer.settings.title'))

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <section class="col-span-12 lg:col-span-6 lg:col-start-4 space-y-6">
            <div class="border-b border-border-subtle pb-6 text-center">
                <a href="{{ route('data-explorer.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-500 hover:text-white transition mb-4">
                    @svg('fas-arrow-left', 'w-3 h-3', ['aria-hidden' => 'true'])
                    {{ __('data_explorer.settings.back_to_query') }}
                </a>
                <h1 class="text-4xl font-black uppercase tracking-tighter text-white">{{ __('data_explorer.settings.title') }}</h1>
            </div>

            @php
                $statusKey = match (session('status')) {
                    'data-explorer-key-linked' => 'data_explorer.settings.linked',
                    'data-explorer-key-removed' => 'data_explorer.settings.removed',
                    'data-explorer-key-activated' => 'data_explorer.settings.activated',
                    default => null,
                };
            @endphp

            @if ($statusKey)
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm rounded-sm px-4 py-3">
                    {{ __($statusKey) }}
                </div>
            @endif

            @error('provider')
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-sm px-4 py-3">{{ $message }}</div>
            @enderror

            {{-- Cost / quota explainer --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-3">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('data_explorer.settings.about_title') }}</h2>
                <p class="text-xs text-gray-400 leading-relaxed">{{ __('data_explorer.settings.about_cost') }}</p>
                <p class="text-xs text-gray-400 leading-relaxed">{{ __('data_explorer.settings.about_shared_quota') }}</p>
                <a href="{{ route('data-explorer.docs') }}" class="inline-flex items-center gap-1.5 text-xs text-gc-yellow hover:underline">
                    {{ __('data_explorer.settings.about_docs_link') }}
                    @svg('fas-arrow-right', 'w-3 h-3', ['aria-hidden' => 'true'])
                </a>
            </div>

            {{-- Personal API keys, one card per provider --}}
            <div class="bg-bg-card border border-border-subtle rounded-sm p-6 shadow-xl space-y-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('data_explorer.settings.key_title') }}</h2>
                <p class="text-xs text-gray-500">{{ __('data_explorer.settings.key_body') }}</p>

                <div class="bg-gc-yellow/10 border border-gc-yellow/30 rounded-sm px-4 py-3 flex gap-3">
                    @svg('fas-triangle-exclamation', 'w-4 h-4 text-gc-yellow shrink-0 mt-0.5', ['aria-hidden' => 'true'])
                    <p class="text-xs text-gray-300 leading-relaxed">{{ __('data_explorer.settings.confidentiality_warning') }}</p>
                </div>

                <div class="space-y-3">
                    @foreach (\App\Models\DataExplorerApiKey::PROVIDER_MODELS as $provider => $model)
                        @php $key = $dataExplorerApiKeys->get($provider); @endphp

                        <div class="bg-[#050505] border border-border-subtle rounded-sm p-4 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-white">
                                    {{ ucfirst($provider) }}
                                    <span class="text-gray-500 font-normal">— {{ $model }}</span>
                                </p>

                                @if ($key)
                                    @if ($key->is_active)
                                        <span class="shrink-0 px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-green-500/10 text-green-400 border border-green-500/30">
                                            {{ __('data_explorer.settings.active') }}
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('data-explorer.settings.key.activate') }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="provider" value="{{ $provider }}">
                                            <button type="submit"
                                                    class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-border-subtle text-white hover:bg-white/10">
                                                {{ __('data_explorer.settings.activate_button') }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>

                            @if ($key)
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-xs text-gray-500">
                                        {{ __('data_explorer.settings.linked_since', ['date' => $key->linked_at->format('Y-m-d')]) }}
                                    </p>
                                    <form method="POST" action="{{ route('data-explorer.settings.key.destroy', $provider) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-confirm-modal
                                            :title="ucfirst($provider)"
                                            :body="__('data_explorer.settings.remove_confirm')"
                                            :trigger-label="__('data_explorer.settings.remove_button')"
                                            :submit-label="__('data_explorer.settings.remove_button')"
                                            trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                            submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                        />
                                    </form>
                                </div>
                            @else
                                <form method="POST" action="{{ route('data-explorer.settings.key.update') }}" class="flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="provider" value="{{ $provider }}">
                                    <input type="password" name="key" placeholder="{{ __('data_explorer.settings.key_placeholder') }}" autocomplete="off"
                                           class="flex-1 min-w-0 bg-black border border-border-subtle rounded-sm px-3 py-2 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                    <button type="submit"
                                            class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:opacity-90">
                                        {{ __('data_explorer.settings.link_button') }}
                                    </button>
                                </form>
                                @error('key')
                                    <p class="text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
