{{--
    GC-Stats — Head-to-head broadcast widget

    Standalone OBS-browser-source-friendly render of the Face to Face
    component, for productions to drop directly into their overlay scene.

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('public.layouts.widget')

@section('content')
    <x-public.head-to-head :data="$headToHead" :bare="true" />

    @vite('resources/js/public/head-to-head/index.js')
@endsection
