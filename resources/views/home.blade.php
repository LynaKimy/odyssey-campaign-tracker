@extends('layouts.app')

@section('content')
    <div class="text-center py-12">
        <h1 class="text-5xl font-bold mb-3" style="font-family: var(--font-heading); color: var(--color-bronze); letter-spacing: 0.15em; text-transform: uppercase;">
            Odyssey
        </h1>
        <p class="text-lg italic mb-12" style="font-family: var(--font-sans); color: var(--color-text-muted); letter-spacing: 0.04em;">
            {{ __('campaign.campaigns') }} &bull; {{ __('character.monsters') }} &bull; {{ __('character.spells') }}
        </p>

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto mb-16">
            <a href="{{ route('campaigns.index') }}" class="panel corner-decor text-center hover:border-transparent transition-all" style="border-color: var(--color-bronze-dim);">
                <div class="stat-value text-3xl" style="color: var(--color-bronze);">{{ $campaignCount }}</div>
                <div class="stat-label mt-2">{{ __('campaign.campaigns') }}</div>
            </a>
            <a href="{{ route('monsters.index') }}" class="panel corner-decor text-center hover:border-transparent transition-all" style="border-color: rgba(212, 80, 80, 0.2);">
                <div class="stat-value text-3xl" style="color: #e87070;">{{ $monsterCount }}</div>
                <div class="stat-label mt-2">{{ __('character.monsters') }}</div>
            </a>
            <a href="{{ route('spells.index') }}" class="panel corner-decor text-center hover:border-transparent transition-all" style="border-color: rgba(45, 140, 240, 0.2);">
                <div class="stat-value text-3xl" style="color: #7ec8f0;">{{ $spellCount }}</div>
                <div class="stat-label mt-2">{{ __('character.spells') }}</div>
            </a>
        </div>

        {{-- Public campaigns --}}
        @if($publicCampaigns->isNotEmpty())
            <div class="max-w-3xl mx-auto text-left">
                <h2 class="section-title text-lg" style="color: var(--color-bronze);">{{ __('campaign.campaigns') }}</h2>
                <div class="space-y-3">
                    @foreach($publicCampaigns as $campaign)
                        <a href="{{ route('campaigns.show', $campaign) }}" class="panel block transition-all">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium" style="color: var(--color-text);">{{ $campaign->name }}</span>
                                    @if($campaign->system)
                                        <span class="badge ml-2">{{ $campaign->system->label() }}</span>
                                    @endif
                                </div>
                                <span class="text-sm" style="color: var(--color-text-muted);">{{ $campaign->members()->count() }} {{ __('campaign.members') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
