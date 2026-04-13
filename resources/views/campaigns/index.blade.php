@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold" style="color: var(--color-bronze);">{{ __('campaign.campaigns') }}</h1>
        @auth
            <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
                {{ __('campaign.create_campaign') }}
            </a>
        @endauth
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($campaigns as $campaign)
            <a href="{{ route('campaigns.show', $campaign) }}" class="panel transition-all">
                <h3 class="text-lg font-medium mb-1" style="font-family: var(--font-heading); color: var(--color-text);">{{ $campaign->name }}</h3>
                @if($campaign->system)
                    <span class="badge">{{ $campaign->system->label() }}</span>
                @endif
                @if($campaign->description)
                    <p class="text-sm italic mt-3 line-clamp-2" style="color: var(--color-text-muted);">{!! $campaign->description !!}</p>
                @endif
                <div class="flex items-center justify-between mt-4 text-xs" style="color: var(--color-text-muted);">
                    <span>{{ __('campaign.created_by') }} {{ $campaign->creator->name }}</span>
                    <span>{{ $campaign->members->count() }} {{ __('campaign.members') }}</span>
                </div>
            </a>
        @empty
            <p style="color: var(--color-text-muted);" class="col-span-full italic">{{ __('ui.no_results') }}</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $campaigns->links() }}
    </div>
@endsection
