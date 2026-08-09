{{--
    GC-Stats — Admin: edit stream channel

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', $channel->name)

@section('content')
    @include('streams._edit', [
        'channel' => $channel,
        'routePrefix' => $routePrefix,
        'organization' => $organization,
        'restricted' => $restricted,
        'platforms' => $platforms,
        'countries' => $countries,
        'organizations' => $organizations,
        'backUrl' => route('admin.streams.index'),
        'backLabel' => __('admin.streams.title'),
    ])
@endsection
