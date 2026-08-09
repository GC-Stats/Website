{{--
    GC-Stats — New news article (shared content)

    Shared between admin/news/create.blade.php (site-admin, extends
    admin.layout) and organization/dashboard/news/create.blade.php (extends
    organization.layout). Expects $routePrefix, $organization (nullable),
    plus everything Admin\NewsController::formData() returns, and optionally
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

<h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">{{ __('admin.news.create') }}</h1>

@if ($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route($routePrefix.'store', $routeArgs()) }}" class="bg-bg-card border border-white/10 rounded-xl backdrop-blur-sm p-6 shadow-xl space-y-6">
    @csrf
    @php $article = null; @endphp
    @include('news._form')

    <button type="submit"
            class="w-full font-bold uppercase text-xs tracking-widest py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
        {{ __('admin.news.form.save') }}
    </button>
</form>
