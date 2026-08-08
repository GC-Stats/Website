{{--
    GC-Stats — Admin: link channels to matches (tournament -> matches -> channels wizard)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.streams.matches.create_title'))

@section('content')
    @include('streams.matches._create', [
        'organization' => $organization,
        'routePrefix' => $routePrefix,
        'backUrl' => route('admin.streams.matches.index'),
    ])
@endsection
