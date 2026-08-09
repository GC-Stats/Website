{{--
    GC-Stats — Personal dashboard: your author profile

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', $author->name)

@section('content')
    @include('news._author-show', [
        'author' => $author,
        'routePrefix' => $routePrefix,
        'organization' => $organization,
        'backUrl' => route('personal-dashboard.news.index'),
        'backLabel' => __('admin.news.title'),
    ])
@endsection
