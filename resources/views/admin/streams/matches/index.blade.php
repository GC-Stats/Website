{{--
    GC-Stats — Admin: matches with a linked stream (list all)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.streams.matches.title'))

@section('content')
    @include('streams.matches._index', [
        'matches' => $matches,
        'status' => $status,
        'sort' => $sort,
        'direction' => $direction,
        'organization' => $organization,
        'createUrl' => route('admin.streams.matches.create'),
    ])
@endsection
