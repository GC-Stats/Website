{{--
    GC-Stats — API keys list, shared by admin.api-keys.index and
    organization.dashboard.api-keys.index

    $organization is null in the admin context (full CRUD, owner picked via
    the entity-picker toggle below) and bound in the dashboard context
    (read-only list + regenerate only — no create/edit/toggle route exists
    under organization-dashboard.* at all, see
    App\Http\Controllers\Admin\ApiKeyController's docblock).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $routeArgs = fn (...$extra) => $organization ? [$organization, ...$extra] : $extra;
    // Create/rename/(de)activate are admin-only regardless of permissions —
    // an organization dashboard never renders these controls since the
    // routes themselves don't exist there.
    $canManage = ! $organization && auth()->user()->can('api-keys.manage');
    $canRegenerate = $organization
        ? auth()->user()->can('organization.api-keys.manage')
        : auth()->user()->can('api-keys.manage');
    $ownerTypes = ['user', 'organization'];
@endphp

@if (session('reveal_url'))
    <div x-data="{ copied: false }" class="mb-6 bg-gc-yellow/10 border border-gc-yellow/40 rounded-lg px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.api_keys.reveal_banner.title') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ __('admin.api_keys.reveal_banner.body') }}</p>
        </div>
        <button type="button"
                @click="navigator.clipboard.writeText('{{ session('reveal_url') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] shrink-0">
            <span x-show="!copied">{{ __('admin.api_keys.reveal_banner.copy') }}</span>
            <span x-show="copied" x-cloak>{{ __('admin.api_keys.reveal_banner.copied') }}</span>
        </button>
    </div>
@endif

<div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
    <form method="GET" class="flex-1 min-w-[200px] max-w-sm">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('admin.api_keys.search_placeholder') }}"
               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
    </form>

    @if ($canManage)
        <x-modal :title="__('admin.api_keys.create.title')">
            <x-slot:trigger>
                <button type="button"
                        class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                    {{ __('admin.api_keys.create.title') }}
                </button>
            </x-slot:trigger>

            <form method="POST" action="{{ route($routePrefix.'store') }}" class="space-y-4" x-data="{ ownerType: 'user' }">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                        {{ __('admin.api_keys.create.client_name_label') }}
                    </label>
                    <input type="text" name="client_name" required minlength="3" maxlength="50"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                    @error('client_name')
                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                        {{ __('admin.api_keys.create.rate_limit_label') }}
                    </label>
                    <input type="number" name="rate_limit" required min="1" value="60"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                    @error('rate_limit')
                        <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                        {{ __('admin.api_keys.create.owner_type_label') }}
                    </label>
                    <select name="owner_type" x-model="ownerType"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                        @foreach ($ownerTypes as $type)
                            <option value="{{ $type }}">{{ __('admin.api_keys.create.owner_type.'.$type) }}</option>
                        @endforeach
                    </select>
                </div>

                @foreach ($ownerTypes as $type)
                    <div x-show="ownerType === '{{ $type }}'" x-cloak>
                        <livewire:entity-picker :type="$type" :name="'owner_id_'.$type"
                            :label="__('admin.api_keys.owner')" :key="'api-key-owner-picker-create-'.$type" />
                    </div>
                @endforeach
                @error('owner_id_user')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
                @error('owner_id_organization')
                    <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror

                <button type="submit"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                    {{ __('admin.api_keys.create.submit') }}
                </button>
            </form>
        </x-modal>
    @endif
</div>

<div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm shadow-xl overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead>
            <tr class="border-b border-white/10 text-[10px] font-black uppercase tracking-widest text-gray-500">
                @foreach ([['client_name', 'admin.api_keys.client_name'], ['rate_limit', 'admin.api_keys.rate_limit'], ['status', 'admin.api_keys.status']] as [$col, $label])
                    <x-admin.sortable-th :col="$col" :sort="$sort" :direction="$direction">{{ __($label) }}</x-admin.sortable-th>
                @endforeach
                @unless ($organization)
                    <th class="px-4 py-3">{{ __('admin.api_keys.owner') }}</th>
                @endunless
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($keys as $key)
                <tr class="border-b border-white/10 last:border-0">
                    <td class="px-4 py-3 text-white font-semibold">{{ $key->client_name }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $key->rate_limit }}</td>
                    <td class="px-4 py-3">
                        @if ($canManage)
                            <form method="POST" action="{{ route($routePrefix.'toggle', $key) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg transition {{ $key->is_active ? 'bg-green-500/10 text-green-400 border border-green-500/30 hover:bg-green-500/20' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30 hover:bg-gray-500/20' }}">
                                    {{ $key->is_active ? __('admin.api_keys.active') : __('admin.api_keys.inactive') }}
                                </button>
                            </form>
                        @else
                            <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-widest rounded-lg {{ $key->is_active ? 'bg-green-500/10 text-green-400 border border-green-500/30' : 'bg-gray-500/10 text-gray-400 border border-gray-500/30' }}">
                                {{ $key->is_active ? __('admin.api_keys.active') : __('admin.api_keys.inactive') }}
                            </span>
                        @endif
                    </td>
                    @unless ($organization)
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $key->user?->username ?? $key->organization?->name ?? '—' }}</td>
                    @endunless
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            @if ($canManage)
                                <x-modal :title="__('admin.api_keys.edit_modal.title')" max-width="max-w-sm">
                                    <x-slot:trigger>
                                        <button type="button"
                                                class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                            {{ __('admin.api_keys.edit') }}
                                        </button>
                                    </x-slot:trigger>

                                    <form method="POST" action="{{ route($routePrefix.'update', $key) }}" class="space-y-4"
                                          x-data="{ ownerType: '{{ $key->organization_id ? 'organization' : 'user' }}' }">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                                {{ __('admin.api_keys.create.client_name_label') }}
                                            </label>
                                            <input type="text" name="client_name" required minlength="3" maxlength="50" value="{{ $key->client_name }}"
                                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                                {{ __('admin.api_keys.create.rate_limit_label') }}
                                            </label>
                                            <input type="number" name="rate_limit" required min="1" value="{{ $key->rate_limit }}"
                                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">
                                                {{ __('admin.api_keys.create.owner_type_label') }}
                                            </label>
                                            <select name="owner_type" x-model="ownerType"
                                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
                                                @foreach ($ownerTypes as $type)
                                                    <option value="{{ $type }}">{{ __('admin.api_keys.create.owner_type.'.$type) }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        @foreach ($ownerTypes as $type)
                                            <div x-show="ownerType === '{{ $type }}'" x-cloak>
                                                <livewire:entity-picker :type="$type" :name="'owner_id_'.$type"
                                                    :label="__('admin.api_keys.owner')"
                                                    :selected="$type === 'user' ? $key->user_id : $key->organization_id"
                                                    :key="'api-key-owner-picker-edit-'.$type.'-'.$key->id" />
                                            </div>
                                        @endforeach

                                        <button type="submit"
                                                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                                            {{ __('admin.api_keys.edit_modal.submit') }}
                                        </button>
                                    </form>
                                </x-modal>
                            @endif

                            @if ($canRegenerate)
                                <form method="POST" action="{{ route($routePrefix.'regenerate', $routeArgs($key)) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-confirm-modal
                                        :title="__('admin.api_keys.regenerate')"
                                        :body="__('admin.api_keys.regenerate_confirm')"
                                        :trigger-label="__('admin.api_keys.regenerate')"
                                        :submit-label="__('admin.api_keys.regenerate')"
                                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                                        submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                                    />
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $organization ? 4 : 5 }}" class="px-4 py-8 text-center text-gray-500 text-xs">{{ __('admin.api_keys.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $keys->links() }}
