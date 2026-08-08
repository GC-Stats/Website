{{--
    GC-Stats — Admin: new news article

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.news.create'))

@section('content')
    @include('news._create', [
        'routePrefix' => $routePrefix,
        'organization' => $organization,
        'organizations' => $organizations,
        'canAttributePersonally' => $canAttributePersonally,
        'selectedPlayers' => $selectedPlayers,
        'selectedTeams' => $selectedTeams,
        'selectedTournaments' => $selectedTournaments,
        'backUrl' => route('admin.news.index'),
        'backLabel' => __('admin.news.title'),
    ])
@endsection
