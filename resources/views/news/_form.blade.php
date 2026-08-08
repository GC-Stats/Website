{{--
    GC-Stats — News article form fields (shared content)

    Shared by resources/views/news/_create.blade.php and _edit.blade.php,
    used from both admin/news/{create,edit} (site-admin, extends
    admin.layout) and organization/dashboard/news/{create,edit} (extends
    organization.layout). Expects $article (null when creating),
    $organization (nullable — bound only in dashboard mode, where
    attribution is fixed rather than picked), $organizations,
    $canAttributePersonally, $selectedPlayers/$selectedTeams/$selectedTournaments.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<div>
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.title_label') }}</label>
    <input type="text" name="title" value="{{ old('title', $article?->title) }}" required
           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.slug_label') }}</label>
    <input type="text" name="slug" value="{{ old('slug', $article?->slug) }}" placeholder="{{ __('admin.news.form.slug_hint') }}"
           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.organization_label') }}</label>
        @if ($organization)
            <input type="hidden" name="organization_id" value="{{ $organization->id }}">
            <div class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-gray-300">
                {{ $organization->name }}
            </div>
        @else
            <x-styled-select name="organization_id" :selected="old('organization_id', $article?->organization_id)"
                :options="collect(['' => $canAttributePersonally ? __('admin.news.form.attribution_personal') : '—'])->union($organizations->mapWithKeys(fn ($organization) => [$organization->id => $organization->name]))" />
            <p class="text-[10px] text-gray-500 mt-1">{{ __('admin.news.form.organization_hint') }}</p>
        @endif
    </div>
    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.lang_label') }}</label>
        <x-styled-select name="lang" :selected="old('lang', $article?->lang ?? app()->getLocale())"
            :options="config('locales.supported')" />
    </div>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.scheduled_at_label') }}</label>
    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($article?->scheduled_at)->format('Y-m-d\TH:i')) }}"
           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
    <p class="text-[10px] text-gray-500 mt-1">{{ __('admin.news.form.scheduled_at_hint') }}</p>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.excerpt_label') }}</label>
    <textarea name="excerpt" rows="2"
              class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('excerpt', $article?->excerpt) }}</textarea>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.content_label') }}</label>
    <textarea id="news-content-editor" name="content" rows="14"
              class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('content', $article?->content) }}</textarea>
</div>

@push('scripts')
    @vite('resources/js/admin/news/editor.js')
@endpush

<div class="flex items-center gap-6">
    <label class="flex items-center gap-2 text-sm text-gray-300">
        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $article?->is_featured))
               class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
        {{ __('admin.news.form.is_featured_label') }}
    </label>
    <label class="flex items-center gap-2 text-sm text-gray-300">
        <input type="checkbox" name="show_on_home" value="1" @checked(old('show_on_home', $article?->show_on_home))
               class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
        {{ __('admin.news.form.show_on_home_label') }}
    </label>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <x-admin.relation-picker
        name="players"
        type="players"
        :label="__('admin.news.form.players_label')"
        :search-url="route('admin.news.relations.search')"
        :selected="$selectedPlayers"
    />
    <x-admin.relation-picker
        name="teams"
        type="teams"
        :label="__('admin.news.form.teams_label')"
        :search-url="route('admin.news.relations.search')"
        :selected="$selectedTeams"
    />
    <x-admin.relation-picker
        name="tournaments"
        type="tournaments"
        :label="__('admin.news.form.tournaments_label')"
        :search-url="route('admin.news.relations.search')"
        :selected="$selectedTournaments"
    />
    <x-admin.relation-picker
        name="staff"
        type="staff"
        :label="__('admin.news.form.staff_label')"
        :search-url="route('admin.news.relations.search')"
        :selected="$selectedStaff"
    />
    <x-admin.relation-picker
        name="organizations"
        type="organizations"
        :label="__('admin.news.form.organizations_label')"
        :search-url="route('admin.news.relations.search')"
        :selected="$selectedOrganizations"
    />
</div>
