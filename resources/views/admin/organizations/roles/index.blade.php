{{--
    GC-Stats — Admin: organization roles list

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.organizations.roles.title'))

@section('content')
    @include('organizations.roles._index', [
        'organization' => $organization,
        'roles' => $roles,
        'routePrefix' => 'admin.organizations.roles.',
        'backUrl' => route('admin.organizations.show', $organization),
        'backLabel' => __('admin.organizations.roles.back_to_organization', ['organization' => $organization->name]),
    ])
@endsection
