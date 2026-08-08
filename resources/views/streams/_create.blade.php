{{--
    GC-Stats — New stream channel (shared content)

    Shared between admin/streams/create.blade.php (extends admin.layout) and
    organization/dashboard/streams/create.blade.php (extends
    organization.layout). Expects $routePrefix, $organization (nullable),
    plus everything Admin\StreamChannelController::formData() returns, and
    optionally $backUrl/$backLabel.

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

<h1 class="text-2xl font-black uppercase tracking-tighter text-white mb-6">{{ __('admin.streams.create.title') }}</h1>

<form method="POST" action="{{ route($routePrefix.'store', $routeArgs()) }}">
    @csrf

    @include('streams._form', ['channel' => null])

    <button type="submit"
            class="mt-6 w-full md:w-auto font-bold uppercase text-xs tracking-widest px-8 py-3 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
        {{ __('admin.streams.create.submit') }}
    </button>
</form>
