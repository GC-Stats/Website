{{--
    GC-Stats — Admin: news media library

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.news.media.title'))

@section('content')
    @include('news._media', [
        'images' => $images,
        'unattachedOnly' => $unattachedOnly,
        'canUpload' => $canUpload,
        'editableOrganizationIds' => $editableOrganizationIds,
        'deletableOrganizationIds' => $deletableOrganizationIds,
        'linkableArticles' => $linkableArticles,
        'routePrefix' => $routePrefix,
        'organization' => $organization,
    ])
@endsection
