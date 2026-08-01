{{--
    GC-Stats — Admin: create a change request

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.change_requests.create.title'))

@php
    $maxItems = 4;
@endphp

@section('content')
    <a href="{{ route('admin.change-requests.index') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ __('admin.change_requests.back_to_list') }}
    </a>

    <div class="max-w-3xl bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl"
         x-data="{
            fieldsByType: @json($fieldsByType),
            subjectType: '{{ old('subject_type', 'team') }}',
            visibleItems: 1,
            itemField: Array({{ $maxItems }}).fill(''),
            maxItems: {{ $maxItems }},

            get availableFields() { return this.fieldsByType[this.subjectType] ?? []; },

            addItem() {
                if (this.visibleItems < this.maxItems) this.visibleItems++;
            },
         }">
        <form method="POST" action="{{ route('admin.change-requests.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ __('admin.change_requests.create.subject_type_label') }}
                </label>
                <select name="subject_type" x-model="subjectType"
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    @foreach ($subjectTypes as $type)
                        <option value="{{ $type }}">{{ __('admin.change_requests.create.subject_type.'.$type) }}</option>
                    @endforeach
                </select>
            </div>

            @foreach ($subjectTypes as $type)
                <div x-show="subjectType === '{{ $type }}'" x-cloak>
                    <livewire:entity-picker :type="$type" :name="'subject_id_'.$type" :label="__('admin.change_requests.subject')" :key="'subject-picker-'.$type" />
                </div>
            @endforeach

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                    {{ __('admin.change_requests.reason') }}
                </label>
                <textarea name="reason" rows="3"
                          class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('reason') }}</textarea>
            </div>

            <div class="space-y-4">
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    {{ __('admin.change_requests.create.items_label') }}
                </label>

                @for ($i = 0; $i < $maxItems; $i++)
                    <div x-show="visibleItems > {{ $i }}" x-cloak class="border border-white/10 rounded-lg p-4 space-y-3">
                        <select name="items[{{ $i }}][field]" x-model="itemField[{{ $i }}]"
                                class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            <option value="">{{ __('admin.change_requests.create.field_placeholder') }}</option>
                            <template x-for="fieldName in availableFields" :key="fieldName">
                                <option :value="fieldName" x-text="fieldName" :selected="fieldName === itemField[{{ $i }}]"></option>
                            </template>
                        </select>

                        <div x-show="itemField[{{ $i }}] !== 'roster'">
                            <input type="text" name="items[{{ $i }}][new_value]"
                                   placeholder="{{ __('admin.change_requests.new_value') }}"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        </div>

                        <div x-show="itemField[{{ $i }}] === 'roster'" class="space-y-3">
                            <livewire:entity-picker type="team" :name="'items['.$i.'][team_id]'" :label="__('admin.change_requests.create.team_search_placeholder')" :key="'roster-team-picker-'.$i" />

                            <div class="grid grid-cols-2 gap-3">
                                <select name="items[{{ $i }}][role]"
                                        class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                    @foreach ($roles as $roleOption)
                                        <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                                    @endforeach
                                </select>
                                <input type="date" name="items[{{ $i }}][joined_at]"
                                       class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                            </div>
                        </div>
                    </div>
                @endfor

                <button type="button" @click="addItem()" x-show="visibleItems < maxItems"
                        class="text-xs font-bold uppercase tracking-widest text-gc-yellow hover:text-white transition">
                    + {{ __('admin.change_requests.create.add_item') }}
                </button>
            </div>

            <button type="submit"
                    class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                {{ __('admin.change_requests.create.submit') }}
            </button>
        </form>
    </div>
@endsection
