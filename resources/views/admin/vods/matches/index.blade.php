{{--
    GC-Stats — Admin: matches with a linked VOD (list all)

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
@extends('admin.layout')

@section('title', __('admin.vods.matches.title'))

@section('content')
    @include('vods.matches._index', [
        'matches' => $matches,
        'sort' => $sort,
        'direction' => $direction,
        'countries' => $countries,
        'vodsRestricted' => $vodsRestricted,
        'vodOrganizations' => $vodOrganizations,
        'organization' => $organization,
        'createUrl' => route('admin.vods.create'),
    ])
@endsection
