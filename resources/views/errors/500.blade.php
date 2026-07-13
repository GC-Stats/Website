{{--
    GC-Stats — 500 Server Error page

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('errors::layout')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Server Error'))
