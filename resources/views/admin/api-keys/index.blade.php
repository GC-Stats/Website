{{--
    GC-Stats — Admin: API keys

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.api_keys.title'))

@section('content')
    @include('api-keys._index', ['organization' => null])
@endsection
