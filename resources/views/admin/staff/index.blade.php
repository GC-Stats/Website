{{--
    GC-Stats — Admin: staff list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.staff.title'))

@section('content')
    @if (session('created_staff'))
        @php $createdStaff = \App\Models\Staff::find(session('created_staff')) @endphp
        @if ($createdStaff)
            <div class="mb-6 bg-gc-yellow/10 border border-gc-yellow/40 rounded-lg px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
                <p class="text-xs text-white">{{ __('admin.staff.create.success', ['name' => $createdStaff->handle]) }}</p>
                <a href="{{ route('admin.staff.show', $createdStaff) }}"
                   class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                    {{ __('admin.staff.manage') }}
                </a>
            </div>
        @endif
    @endif

    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <form method="GET" action="{{ route('admin.staff.index') }}" class="flex flex-wrap gap-2 flex-1 min-w-[200px]">
            <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.staff.search_placeholder') }}"
                   class="flex-1 max-w-sm bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">

            <x-styled-select name="sort" :selected="$sort" autosubmit class="w-44"
                :options="[
                    'name' => __('admin.staff.sort.name'),
                    'country' => __('admin.staff.sort.country'),
                ]" />

            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                {{ __('admin.staff.search_submit') }}
            </button>
        </form>

        @can('staff.create')
            <x-modal :title="__('admin.staff.create.title')" max-width="max-w-sm">
                <x-slot:trigger>
                    <button type="button"
                            class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.staff.create.title') }}
                    </button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('admin.staff.create.name_label') }}
                        </label>
                        <input type="text" name="handle" required maxlength="255"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @error('handle')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-admin.country-select
                        id="create_staff_country_code_query"
                        name="country_code"
                        :label="__('admin.staff.create.country_label')"
                        :countries="$countries"
                        :search-placeholder="__('player.edit.fields.country_code_search')"
                        :none-label="__('player.edit.fields.country_code_none')"
                    />

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('admin.staff.create.organization_label') }}
                        </label>
                        <x-styled-select name="organization_id" :options="collect(['' => '—'])->union($organizationOptions->pluck('name', 'id'))" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                            {{ __('admin.staff.fields.vlr_id') }}
                        </label>
                        <input type="number" name="vlr_id"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        @error('vlr_id')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.staff.create.submit') }}
                    </button>
                </form>
            </x-modal>
        @endcan
    </div>

    <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                    <th class="px-4 py-3">{{ __('admin.staff.title') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $staffMember)
                    <tr class="border-b border-white/10 last:border-0">
                        <td class="px-4 py-3 text-white font-semibold">{{ $staffMember->handle }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.staff.show', $staffMember) }}"
                               class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.staff.manage') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 text-xs">—</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $staff->links() }}
@endsection
