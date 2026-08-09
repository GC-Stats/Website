{{--
    GC-Stats — Personal dashboard: news articles list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', __('admin.news.title'))

@section('content')
    @include('news._index', [
        'news' => $news,
        'search' => $search,
        'status' => $status,
        'sort' => $sort,
        'direction' => $direction,
        'organization' => $organization,
        'routePrefix' => $routePrefix,
        'canCreate' => $canCreate,
        'canEditArticle' => $canEditArticle,
        'backUrl' => route('personal-dashboard.index'),
        'backLabel' => __('personal_dashboard.title'),
    ])
@endsection
