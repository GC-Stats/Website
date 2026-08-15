{{--
    GC-Stats — Forum thread page

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.app')

@php
    $threadTitle = $thread->title ?? __('forum.category.'.$thread->category);
@endphp

@section('title', $threadTitle)
@section('description', $threadTitle)

@section('content')
    <div class="max-w-3xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-black text-white mb-6">{{ $threadTitle }}</h1>

        <livewire:forum-thread :thread-id="$thread->id" />
    </div>
@endsection
