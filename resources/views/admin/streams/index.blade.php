{{--
    GC-Stats — Admin: stream channels list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.streams.title'))

@section('content')
    @include('streams._index', [
        'channels' => $channels,
        'search' => $search,
        'platform' => $platform,
        'platforms' => $platforms,
        'sort' => $sort,
        'direction' => $direction,
        'organization' => $organization,
        'routePrefix' => $routePrefix,
        'canCreate' => $canCreate,
        'editableOrganizationIds' => $editableOrganizationIds,
        'deletableOrganizationIds' => $deletableOrganizationIds,
    ])
@endsection
