{{--
    GC-Stats — Personal dashboard: edit news article

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('organization.layout')

@section('title', $article->title)

@section('content')
    @include('news._edit', [
        'article' => $article,
        'routePrefix' => $routePrefix,
        'organization' => $organization,
        'organizations' => $organizations,
        'canAttributePersonally' => $canAttributePersonally,
        'selectedPlayers' => $selectedPlayers,
        'selectedTeams' => $selectedTeams,
        'selectedTournaments' => $selectedTournaments,
        'images' => $images,
        'canPublish' => $canPublish,
        'canPublishUnvalidated' => $canPublishUnvalidated,
        'canArchive' => $canArchive,
        'canValidate' => $canValidate,
        'canComment' => $canComment,
        'comments' => $comments,
        'backUrl' => route('personal-dashboard.news.index'),
        'backLabel' => __('admin.news.title'),
    ])
@endsection
