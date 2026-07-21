{{--
    GC-Stats — Admin: news article form fields

    Shared by admin/news/create and admin/news/edit.

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
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.publisher_label') }}</label>
        <select name="publisher_id"
                class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            <option value="">—</option>
            @foreach ($publishers as $publisher)
                <option value="{{ $publisher->id }}" @selected(old('publisher_id', $article?->publisher_id) == $publisher->id)>{{ $publisher->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.lang_label') }}</label>
        <select name="lang" required
                class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition [color-scheme:dark]">
            @foreach (config('locales.supported') as $code => $label)
                <option value="{{ $code }}" @selected(old('lang', $article?->lang ?? app()->getLocale()) === $code)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.excerpt_label') }}</label>
    <textarea name="excerpt" rows="2"
              class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('excerpt', $article?->excerpt) }}</textarea>
</div>

<div>
    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">{{ __('admin.news.form.content_label') }}</label>
    <textarea id="news-content-editor" name="content" rows="14" required
              class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition">{{ old('content', $article?->content) }}</textarea>
</div>

@push('scripts')
    @vite('resources/js/admin-news-editor.js')
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
    <x-relation-picker
        name="players"
        type="players"
        :label="__('admin.news.form.players_label')"
        :search-url="route('admin.news.relations.search')"
        :selected="$selectedPlayers"
    />
    <x-relation-picker
        name="teams"
        type="teams"
        :label="__('admin.news.form.teams_label')"
        :search-url="route('admin.news.relations.search')"
        :selected="$selectedTeams"
    />
    <x-relation-picker
        name="tournaments"
        type="tournaments"
        :label="__('admin.news.form.tournaments_label')"
        :search-url="route('admin.news.relations.search')"
        :selected="$selectedTournaments"
    />
</div>
