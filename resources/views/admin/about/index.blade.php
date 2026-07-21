{{--
    GC-Stats — Admin: About Us page content

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.about.title'))

@section('content')
    <div class="space-y-10">
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.about.sections_title') }}</h2>
                @can('about.manage')
                    <x-modal :title="__('admin.about.add_section')" max-width="max-w-2xl">
                        <x-slot:trigger>
                            <button type="button"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                {{ __('admin.about.add_section') }}
                            </button>
                        </x-slot:trigger>

                        <div x-data="{ key: '' }">
                            <form method="POST" :action="key ? `{{ url('admin/about/sections') }}/${key}` : '#'" class="space-y-4" @submit="if (!key) $event.preventDefault()">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                            Key
                                        </label>
                                        <input type="text" x-model="key" pattern="[a-z0-9_-]+" required
                                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                            {{ __('admin.about.order_label') }}
                                        </label>
                                        <input type="number" name="order" value="{{ $sections->count() }}"
                                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                    </div>
                                </div>

                                @foreach ($locales as $locale)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                                {{ __('admin.about.title_label') }} ({{ strtoupper($locale) }})
                                            </label>
                                            <input type="text" name="title[{{ $locale }}]"
                                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                                {{ __('admin.about.content_label') }} ({{ strtoupper($locale) }})
                                            </label>
                                            <textarea name="content[{{ $locale }}]" rows="3"
                                                      class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></textarea>
                                        </div>
                                    </div>
                                @endforeach

                                <button type="submit"
                                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                    {{ __('admin.about.save') }}
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endcan
            </div>

            <div class="space-y-4">
                @foreach ($sections as $key => $section)
                    @php
                        // Every section's form shares the same field names
                        // (title[en], content[en], ...), so old()/@error must
                        // be scoped to whichever section was actually
                        // submitted — otherwise a validation failure on one
                        // section would repopulate and flag every other
                        // section's form with its data instead.
                        $isResubmit = old('_key') === $key;
                    @endphp
                    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
                        <form method="POST" action="{{ route('admin.about.sections.update', $key) }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_key" value="{{ $key }}">
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ $key }}</p>
                                <div class="flex items-center gap-2 shrink-0">
                                    <label for="order-{{ $key }}" class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                        {{ __('admin.about.order_label') }}
                                    </label>
                                    <input type="number" id="order-{{ $key }}" name="order" value="{{ $isResubmit ? old('order') : $section->order }}"
                                           class="w-16 bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                </div>
                            </div>

                            @foreach ($locales as $locale)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                            {{ __('admin.about.title_label') }} ({{ strtoupper($locale) }})
                                        </label>
                                        <input type="text" name="title[{{ $locale }}]"
                                               value="{{ $isResubmit ? old('title.'.$locale) : ($section->title[$locale] ?? '') }}"
                                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                        @if ($isResubmit)
                                            @error('title.'.$locale)
                                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                            {{ __('admin.about.content_label') }} ({{ strtoupper($locale) }})
                                        </label>
                                        <textarea name="content[{{ $locale }}]" rows="3"
                                                  class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ $isResubmit ? old('content.'.$locale) : ($section->content[$locale] ?? '') }}</textarea>
                                        @if ($isResubmit)
                                            @error('content.'.$locale)
                                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @can('about.manage')
                                <button type="submit"
                                        class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                    {{ __('admin.about.save') }}
                                </button>
                            @endcan
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.about.team_title') }}</h2>
                @can('about.manage')
                    <x-modal :title="__('admin.about.add_member')" max-width="max-w-lg">
                        <x-slot:trigger>
                            <button type="button"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                {{ __('admin.about.add_member') }}
                            </button>
                        </x-slot:trigger>
                        @include('admin.about._member-form', ['member' => null])
                    </x-modal>
                @endcan
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($team as $member)
                    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
                        <div class="flex items-center gap-3 mb-3">
                            @if ($member->photo_url)
                                <img src="{{ $member->photo_url }}" alt="" class="w-12 h-12 rounded-full object-cover border border-white/10">
                            @else
                                <div class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-gray-600">
                                    @svg('fas-user', 'w-5 h-5', ['aria-hidden' => 'true'])
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm text-white font-semibold truncate">{{ $member->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $member->role[app()->getLocale()] ?? $member->role['en'] ?? '' }}</p>
                            </div>
                            @unless ($member->is_active)
                                <span class="ml-auto shrink-0 px-2 py-1 text-[9px] font-bold uppercase tracking-widest rounded-lg bg-gray-500/10 text-gray-400 border border-gray-500/30">
                                    {{ __('admin.about.inactive') }}
                                </span>
                            @endunless
                        </div>

                        @can('about.manage')
                            <div class="flex gap-2">
                                <x-modal :title="$member->name" max-width="max-w-lg">
                                    <x-slot:trigger>
                                        <button type="button"
                                                class="flex-1 font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                            {{ __('admin.about.edit') }}
                                        </button>
                                    </x-slot:trigger>
                                    @include('admin.about._member-form', ['member' => $member])

                                    <form method="POST" action="{{ route('admin.about.team.photo', $member) }}" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-t-white/10 space-y-2">
                                        @csrf
                                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                            {{ __('admin.about.member_photo') }}
                                        </label>
                                        <input type="file" name="image" accept="image/*" required
                                               class="w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-white/5 file:text-white">
                                        <button type="submit"
                                                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                            {{ __('admin.about.upload_photo') }}
                                        </button>
                                    </form>
                                </x-modal>

                                <form method="POST" action="{{ route('admin.about.team.destroy', $member) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-confirm-modal
                                        :title="__('admin.about.remove_member')"
                                        :body="__('admin.about.remove_member_confirm')"
                                        :trigger-label="__('admin.about.remove_member')"
                                        :submit-label="__('admin.about.remove_member')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            </div>
                        @endcan
                    </div>
                @empty
                    <p class="text-xs text-gray-500 col-span-full">{{ __('admin.about.no_members') }}</p>
                @endforelse
            </div>
        </section>

        <section>
            @php
                $projectTypeIcons = [
                    'Website' => 'fas-globe',
                    'API' => 'fas-plug',
                    'DiscordBot' => 'fab-discord',
                ];
            @endphp
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.about.projects_title') }}</h2>
                @can('about.manage')
                    <x-modal :title="__('admin.about.add_project')" max-width="max-w-lg">
                        <x-slot:trigger>
                            <button type="button"
                                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                {{ __('admin.about.add_project') }}
                            </button>
                        </x-slot:trigger>
                        @include('admin.about._project-form', ['project' => null])
                    </x-modal>
                @endcan
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($projects as $project)
                    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-4">
                        <div class="flex items-center gap-3 mb-3">
                            @if ($project->logo_url)
                                <img src="{{ $project->logo_url }}" alt="" class="w-12 h-12 rounded-lg object-cover border border-white/10">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-gray-600">
                                    @svg($projectTypeIcons[$project->type] ?? 'fas-cube', 'w-5 h-5', ['aria-hidden' => 'true'])
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm text-white font-semibold truncate">{{ $project->name }}</p>
                                @if ($project->type)
                                    <p class="text-xs text-gray-500 truncate flex items-center gap-1.5">
                                        @svg($projectTypeIcons[$project->type] ?? 'fas-cube', 'w-3 h-3 shrink-0', ['aria-hidden' => 'true'])
                                        {{ $project->type }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        @can('about.manage')
                            <div class="flex gap-2">
                                <x-modal :title="$project->name" max-width="max-w-lg">
                                    <x-slot:trigger>
                                        <button type="button"
                                                class="flex-1 font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                            {{ __('admin.about.edit') }}
                                        </button>
                                    </x-slot:trigger>
                                    @include('admin.about._project-form', ['project' => $project])

                                    <form method="POST" action="{{ route('admin.about.projects.logo', $project) }}" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-t-white/10 space-y-2">
                                        @csrf
                                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                            {{ __('admin.about.project_logo') }}
                                        </label>
                                        <input type="file" name="image" accept="image/*" required
                                               class="w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-white/5 file:text-white">
                                        <button type="submit"
                                                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                            {{ __('admin.about.upload_logo') }}
                                        </button>
                                    </form>
                                </x-modal>

                                <form method="POST" action="{{ route('admin.about.projects.destroy', $project) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-confirm-modal
                                        :title="__('admin.about.remove_project')"
                                        :body="__('admin.about.remove_project_confirm')"
                                        :trigger-label="__('admin.about.remove_project')"
                                        :submit-label="__('admin.about.remove_project')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            </div>
                        @endcan
                    </div>
                @empty
                    <p class="text-xs text-gray-500 col-span-full">{{ __('admin.about.no_projects') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
