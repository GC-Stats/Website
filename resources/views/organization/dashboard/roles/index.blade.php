{{--
    GC-Stats — Organization dashboard: roles list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', __('admin.organizations.roles.title'))

@section('content')
    @include('organizations.roles._index', [
        'organization' => $organization,
        'roles' => $roles,
        'routePrefix' => 'organization-dashboard.roles.',
        'backUrl' => route('organization-dashboard.index', $organization),
        'backLabel' => $organization->name,
    ])
@endsection
