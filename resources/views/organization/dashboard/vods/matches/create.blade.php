{{--
    GC-Stats — Organization dashboard: add a VOD

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', __('admin.vods.matches.create_title'))

@section('content')
    @include('vods.matches._create', [
        'countries' => $countries,
        'backUrl' => route('organization-dashboard.vods.index', $organization),
    ])
@endsection
