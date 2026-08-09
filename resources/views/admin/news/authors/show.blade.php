{{--
    GC-Stats — Admin: author profile (100% editable)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $author->name)

@section('content')
    @include('news._author-show', [
        'author' => $author,
        'routePrefix' => $routePrefix,
        'organization' => $organization,
        'backUrl' => auth()->user()->can('news.authors.view') ? route('admin.news.authors.index') : null,
        'backLabel' => __('admin.news.authors.title'),
    ])
@endsection
