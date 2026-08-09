{{--
    GC-Stats — News media library (shared content)

    Shared between admin/news/media/index.blade.php (site-admin view, flat
    across every organization, extends admin.layout) and
    organization/dashboard/news/media/index.blade.php (scoped to one
    organization, extends organization.layout) — same
    Admin\NewsMediaController::index() action renders either wrapper
    depending on which route matched, both @include this partial so the
    actual markup never has to be duplicated.

    Expects $images, $unattachedOnly, $canUpload, $editableOrganizationIds,
    $deletableOrganizationIds, $linkableArticles, $routePrefix, $organization
    (nullable — bound only in dashboard mode), and optionally
    $backUrl/$backLabel. Store/link/cover.update/destroy always post to the
    flat admin.news.media.* routes regardless of context — see
    Admin\NewsMediaController's docblock.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@if (isset($backUrl))
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-4">
        &larr; {{ $backLabel }}
    </a>
@endif

<div class="flex items-center justify-between gap-4 mb-6">
    <form method="GET" action="{{ route($routePrefix.'index', $organization ?? []) }}" class="flex items-center gap-2">
        <label class="flex items-center gap-2 text-xs text-gray-400">
            <input type="checkbox" name="unattached" value="1" @checked($unattachedOnly) onchange="this.form.submit()"
                   class="rounded-lg border-white/10 bg-white/5 text-gc-yellow focus:ring-gc-yellow">
            {{ __('admin.news.media.unattached_only') }}
        </label>
    </form>

    @if ($canUpload)
        <x-modal :title="__('admin.news.media.upload')">
            <x-slot:trigger>
                <button type="button"
                        class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)] shrink-0">
                    {{ __('admin.news.media.upload') }}
                </button>
            </x-slot:trigger>

            <form method="POST" action="{{ route('admin.news.media.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="file" name="image" accept="image/*" required
                       class="w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-white/5 file:text-white hover:file:bg-white/10">
                <button type="submit"
                        class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                    {{ __('admin.news.media.upload') }}
                </button>
            </form>
        </x-modal>
    @endif
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
    @forelse ($images as $image)
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm overflow-hidden shadow-xl">
            <img src="{{ $image->url }}" alt="" class="w-full aspect-video object-cover">
            <div class="p-3 space-y-2">
                <p class="text-[10px] text-gray-500 truncate">
                    {{ $image->news?->title ?? __('admin.news.media.unattached') }}
                </p>
                <div class="flex items-center gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.news.media.link', $image) }}" class="flex-1 min-w-[120px]">
                        @csrf
                        @method('PUT')
                        <x-styled-select name="news_id" :selected="$image->news_id" autosubmit
                            :options="collect(['' => __('admin.news.media.unattached')])->union($linkableArticles->mapWithKeys(fn ($linkable) => [$linkable->id => $linkable->title]))" />
                    </form>
                    <button
                        type="button"
                        title="{{ __('admin.news.media.copy_url') }}"
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard.writeText(@js($image->url));
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        x-text="copied ? '{{ __('admin.news.media.copied') }}' : '{{ __('admin.news.media.copy') }}'"
                        class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-2 py-1.5 rounded-lg transition active:scale-95 border"
                        :class="copied ? 'bg-green-500/10 border-green-500/40 text-green-400' : 'bg-white/5 border-white/10 text-white hover:bg-white/10'"
                    >{{ __('admin.news.media.copy') }}</button>
                    @if ($image->news && (auth()->user()->can('news.edit') || $editableOrganizationIds->contains($image->news->organization_id) || (! $image->news->organization_id && $image->news->author_id === auth()->user()->newsAuthor?->id && auth()->user()->can('news.author'))) && $image->url !== $image->news->image_cover)
                        <form method="POST" action="{{ route('admin.news.media.cover.update', [$image->news, $image]) }}">
                            @csrf
                            @method('PUT')
                            <button type="submit" title="{{ __('admin.news.media.set_as_cover') }}"
                                    class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-2 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                                {{ __('admin.news.media.set_as_cover') }}
                            </button>
                        </form>
                    @endif
                    @if (auth()->user()->can('news.media.delete')
                            || ($image->news && $deletableOrganizationIds->contains($image->news->organization_id))
                            || ($image->news && ! $image->news->organization_id && $image->news->author_id === auth()->user()->newsAuthor?->id && auth()->user()->can('news.author')))
                        <form method="POST" action="{{ route('admin.news.media.destroy', $image) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="font-bold uppercase text-[10px] tracking-widest px-2 py-1.5 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10">
                                {{ __('admin.news.media.delete') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <p class="col-span-full text-center text-gray-500 text-xs py-8">{{ __('admin.news.media.empty') }}</p>
    @endforelse
</div>

<div class="mt-6">{{ $images->links() }}</div>
