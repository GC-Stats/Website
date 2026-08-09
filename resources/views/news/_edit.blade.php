{{--
    GC-Stats — Edit news article (shared content)

    Shared between admin/news/edit.blade.php (site-admin, extends
    admin.layout) and organization/dashboard/news/edit.blade.php (extends
    organization.layout). Expects $article, $routePrefix, $organization
    (nullable), $canPublish, $canPublishUnvalidated, $canArchive,
    $canValidate, $canComment, $comments, plus everything
    Admin\NewsController::formData() returns, and optionally
    $backUrl/$backLabel.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@php
    $routeArgs = fn (...$extra) => $organization ? [$organization, ...$extra] : $extra;
@endphp

@if (isset($backUrl))
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-white transition mb-6">
        &larr; {{ $backLabel }}
    </a>
@endif

<div class="flex items-center justify-between mb-6 flex-wrap gap-2">
    <div>
        <h1 class="text-2xl font-black uppercase tracking-tighter text-white">{{ $article->title }}</h1>
        <p class="text-xs text-gray-500 mt-1">
            <span class="font-bold uppercase tracking-widest text-[10px] {{ $article->status === 'published' ? 'text-green-400' : 'text-gray-500' }}">
                {{ __('admin.news.status.'.$article->status) }}
            </span>
            &middot; {{ __('admin.news.form.author_label') }}: {{ $article->author?->name }}
            @if ($article->organization_id)
                &middot;
                @if ($article->validated_at)
                    <span class="text-green-400">{{ __('admin.news.review.validated_badge') }}</span>
                @else
                    <span class="text-yellow-400">{{ __('admin.news.review.unvalidated_badge') }}</span>
                @endif
            @endif
        </p>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('news.show', $article->slug) }}" target="_blank" rel="noopener"
           class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
            {{ __('admin.news.public_page') }}
        </a>
        @if ($canValidate && ! $article->validated_at)
            <form method="POST" action="{{ route($routePrefix.'validate', $routeArgs($article)) }}">
                @csrf
                <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-green-500/10 border border-green-500/40 text-green-400 hover:bg-green-500/20">
                    {{ __('admin.news.review.validate') }}
                </button>
            </form>
        @endif
        @if ($canPublish && $article->status !== 'published' && ($article->validated_at || ! $article->organization_id || $canPublishUnvalidated))
            <form method="POST" action="{{ route($routePrefix.'publish', $routeArgs($article)) }}">
                @csrf
                @if ($article->organization_id && ! $article->validated_at)
                    <x-confirm-modal
                        :title="__('admin.news.publish')"
                        :body="__('admin.news.review.publish_unvalidated_warning')"
                        :trigger-label="__('admin.news.publish')"
                        :submit-label="__('admin.news.publish')"
                        trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-yellow-500/10 border border-yellow-500/40 text-yellow-400 hover:bg-yellow-500/20"
                        submit-class="bg-yellow-500/10 border border-yellow-500/40 text-yellow-400 hover:bg-yellow-500/20"
                    />
                @else
                    <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                        {{ __('admin.news.publish') }}
                    </button>
                @endif
            </form>
        @endif
        @if ($canArchive && $article->status !== 'archived')
            <form method="POST" action="{{ route($routePrefix.'archive', $routeArgs($article)) }}">
                @csrf
                <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                    {{ __('admin.news.archive') }}
                </button>
            </form>
        @endif
        @can('news.edit')
            <form method="POST" action="{{ route('admin.news.feature', $article) }}">
                @csrf
                <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 {{ $article->is_featured ? 'text-gc-yellow' : 'text-white' }} hover:bg-white/10">
                    {{ __('admin.news.feature') }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.news.show-on-home', $article) }}">
                @csrf
                <button type="submit" class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 {{ $article->show_on_home ? 'text-gc-yellow' : 'text-white' }} hover:bg-white/10">
                    {{ __('admin.news.show_on_home') }}
                </button>
            </form>
        @endcan
        @if ($canArchive)
            <form method="POST" action="{{ route($routePrefix.'destroy', $routeArgs($article)) }}">
                @csrf
                @method('DELETE')
                <x-confirm-modal
                    :title="__('admin.news.delete')"
                    :body="__('admin.news.delete_confirm')"
                    :trigger-label="__('admin.news.delete')"
                    :submit-label="__('admin.news.delete')"
                    trigger-class="font-bold uppercase text-[10px] tracking-widest px-4 py-2 rounded-lg transition active:scale-95 bg-transparent border border-red-500/40 text-red-400 hover:bg-red-500/10"
                    submit-class="bg-red-500/10 border border-red-500/40 text-red-400 hover:bg-red-500/20"
                />
            </form>
        @endif
    </div>
</div>

@if ($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <form method="POST" action="{{ route($routePrefix.'update', $routeArgs($article)) }}" class="lg:col-span-2 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6">
        @csrf
        @method('PUT')
        @include('news._form')

        <button type="submit"
                class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
            {{ __('admin.news.form.save') }}
        </button>
    </form>

    <div class="space-y-6">
        <div class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
            <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.news.media.title') }}</h2>

            @if ($article->image_cover)
                <img src="{{ $article->image_cover }}" alt="" class="w-full aspect-video object-cover rounded-lg border border-white/10">
            @endif

            <div class="grid grid-cols-3 gap-2">
                @forelse ($images as $image)
                    <div class="relative group">
                        <img src="{{ $image->url }}" alt="" class="w-full aspect-square object-cover rounded-lg border border-white/10">
                        <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition bg-black/60 p-1">
                            @if ($image->url !== $article->image_cover)
                                <form method="POST" action="{{ route('admin.news.media.cover.update', [$article, $image]) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="text-[9px] font-bold uppercase tracking-widest text-white">{{ __('admin.news.media.set_as_cover') }}</button>
                                </form>
                            @endif
                            <button
                                type="button"
                                x-data="{ copied: false }"
                                x-on:click="
                                    navigator.clipboard.writeText(@js($image->url));
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                x-text="copied ? '{{ __('admin.news.media.copied') }}' : '{{ __('admin.news.media.copy') }}'"
                                class="text-[9px] font-bold uppercase tracking-widest text-white"
                            >{{ __('admin.news.media.copy') }}</button>
                        </div>
                    </div>
                @empty
                    <p class="col-span-3 text-xs text-gray-500">{{ __('admin.news.media.empty_for_article') }}</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('admin.news.media.store') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="news_id" value="{{ $article->id }}">
                <input type="file" name="image" accept="image/*" required
                       class="flex-1 min-w-0 text-xs text-gray-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-white/5 file:text-white hover:file:bg-white/10">
                <button type="submit"
                        class="shrink-0 font-bold uppercase text-[10px] tracking-widest px-3 py-1.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                    {{ __('admin.news.media.upload') }}
                </button>
            </form>

            @can('news.nav.media')
                <a href="{{ $organization ? route('organization-dashboard.news.media.index', $organization) : route('admin.news.media.index') }}"
                   class="block text-center w-full font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                    {{ __('admin.news.media.title') }} &rarr;
                </a>
            @endcan
        </div>
    </div>
</div>

@if ($article->organization_id)
    <div class="mt-6 bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-4">
        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">{{ __('admin.news.review.title') }}</h2>

        <div class="space-y-3">
            @forelse ($comments as $comment)
                <div class="bg-white/5 border border-white/10 rounded-lg px-4 py-3 {{ $comment->resolved_at ? 'opacity-50' : '' }}">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-xs text-white font-semibold">
                            {{ $comment->type === 'system' ? __('admin.news.review.system_author') : ($comment->user?->name ?? '—') }}
                            @if ($comment->field)
                                <span class="ml-1 text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm bg-gc-yellow/10 text-gc-yellow">
                                    {{ __('admin.news.review.fields.'.$comment->field) }}
                                </span>
                            @endif
                        </p>
                        <span class="text-[10px] text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-300">{{ $comment->body }}</p>

                    @if ($canComment && $comment->type !== 'system')
                        <div class="flex gap-2 mt-2">
                            @if (! $comment->resolved_at)
                                <form method="POST" action="{{ route($routePrefix.'comments.resolve', $routeArgs($article, $comment)) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-green-400 hover:underline">
                                        {{ __('admin.news.review.resolve') }}
                                    </button>
                                </form>
                            @else
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                    {{ __('admin.news.review.resolved_by', ['name' => $comment->resolver?->name ?? '—']) }}
                                </span>
                            @endif
                            <form method="POST" action="{{ route($routePrefix.'comments.destroy', $routeArgs($article, $comment)) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-red-400 hover:underline">
                                    {{ __('admin.news.review.remove') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-xs text-gray-500">{{ __('admin.news.review.empty') }}</p>
            @endforelse
        </div>

        @if ($canComment)
            <form method="POST" action="{{ route($routePrefix.'comments.store', $routeArgs($article)) }}" class="space-y-3 pt-4 border-t border-white/10">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-1">
                        <x-styled-select name="field"
                            :options="collect(['' => __('admin.news.review.general_comment')])->union(collect(\App\Http\Controllers\Admin\NewsController::REVIEWABLE_FIELDS)->mapWithKeys(fn ($field) => [$field => __('admin.news.review.fields.'.$field)]))" />
                    </div>
                    <div class="sm:col-span-3">
                        <textarea name="body" rows="2" required placeholder="{{ __('admin.news.review.comment_placeholder') }}"
                                  class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-gc-yellow transition"></textarea>
                    </div>
                </div>
                <button type="submit"
                        class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-white/5 border border-white/10 text-white hover:bg-white/10">
                    {{ __('admin.news.review.add_comment') }}
                </button>
            </form>
        @endif
    </div>
@endif
