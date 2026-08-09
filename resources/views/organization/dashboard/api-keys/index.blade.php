{{--
    GC-Stats — Organization dashboard: API keys list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', __('admin.api_keys.title'))

@section('content')
    @include('api-keys._index', [
        'keys' => $keys,
        'search' => $search,
        'sort' => $sort,
        'direction' => $direction,
        'organization' => $organization,
        'routePrefix' => $routePrefix,
    ])
@endsection
