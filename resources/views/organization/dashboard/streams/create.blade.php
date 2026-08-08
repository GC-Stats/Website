{{--
    GC-Stats — Organization dashboard: create stream channel

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', __('admin.streams.create.title'))

@section('content')
    @include('streams._create', [
        'routePrefix' => $routePrefix,
        'organization' => $organization,
        'restricted' => $restricted,
        'platforms' => $platforms,
        'countries' => $countries,
        'organizations' => $organizations,
        'backUrl' => route('organization-dashboard.streams.index', $organization),
        'backLabel' => __('admin.streams.title'),
    ])
@endsection
