{{--
    GC-Stats — Organization dashboard: role detail

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', $role->name)

@section('content')
    @include('organizations.roles._show', [
        'organization' => $organization,
        'role' => $role,
        'permissionGroups' => $permissionGroups,
        'members' => $members,
        'search' => $search,
        'searchResults' => $searchResults,
        'routePrefix' => 'organization-dashboard.roles.',
        'backUrl' => route('organization-dashboard.roles.index', $organization),
    ])
@endsection
