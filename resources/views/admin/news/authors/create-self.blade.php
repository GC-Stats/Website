{{--
    GC-Stats — Admin: create your author profile (self-service)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.news.authors.create'))

@section('content')
    @include('news._author-create-self', [
        'routePrefix' => $routePrefix ?? 'admin.news.authors.',
        'organization' => $organization ?? null,
    ])
@endsection
