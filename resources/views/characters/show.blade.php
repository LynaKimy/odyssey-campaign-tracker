@extends('layouts.app')

@section('content')
    <a href="{{ route('campaigns.show', $campaign) }}" class="inline-block mb-4 text-sm hover:underline" style="color: var(--color-bronze);">&larr; {{ __('ui.back') }}</a>

    {{-- Header --}}
    <div class="panel corner-decor mb-6">
        <div class="flex items-start gap-6">
            {{-- Avatar --}}
            @if($character->avatar)
                <img src="{{ asset('storage/' . $character->avatar) }}"
                     alt="{{ $character->name }}"
                     class="rounded"
                     style="width: 120px; height: 120px; object-fit: cover; border: 2px solid var(--color-bronze-dim);">
            @else
                <div class="rounded flex items-center justify-center"
                     style="width: 120px; height: 120px; background: var(--color-input); border: 2px solid var(--color-bronze-dim); color: var(--color-text-muted); font-size: 2.5rem; font-family: var(--font-heading);">
                    {{ mb_substr($character->name, 0, 1) }}
                </div>
            @endif

            <div class="flex-1">
                <h1 class="text-3xl font-bold" style="color: var(--color-text);">{{ $character->name }}</h1>
                <p class="mt-1 italic" style="color: var(--color-text-muted);">
                    @if($character->race){{ $character->race }}@endif
                    @if($character->class) &mdash; {{ $character->class }}@endif
                    @if($character->level) &bull; {{ __('character.level') }} {{ $character->level }}@endif
                </p>
                <p class="mt-1 text-sm" style="color: var(--color-text-muted);">
                    {{ __('character.owned_by') }} {{ $character->user->name }}
                    &bull; {{ $campaign->name }}
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Combat Stats --}}
        <div class="panel">
            <h2 class="panel-title" style="color: var(--color-bronze);">{{ __('character.hit_points') }} & {{ __('character.armor_class') }}</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="stat-block">
                    <div class="stat-label">{{ __('character.current_hp') }}</div>
                    <div class="stat-value" style="color: #81c784;">{{ $character->current_hp ?? '—' }}</div>
                </div>
                <div class="stat-block">
                    <div class="stat-label">{{ __('character.max_hp') }}</div>
                    <div class="stat-value">{{ $character->max_hp ?? '—' }}</div>
                </div>
                <div class="stat-block">
                    <div class="stat-label">{{ __('character.armor_class') }}</div>
                    <div class="stat-value" style="color: #7ec8f0;">{{ $character->armor_class ?? '—' }}</div>
                </div>
            </div>
        </div>

        {{-- Ability Scores --}}
        <div class="panel lg:col-span-2">
            <h2 class="panel-title" style="color: var(--color-bronze);">{{ __('character.ability_scores') }}</h2>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                @foreach(['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $ability)
                    <div class="stat-block">
                        <div class="stat-label">{{ __("character.$ability") }}</div>
                        <div class="stat-value">{{ $character->{$ability} ?? '—' }}</div>
                        @if($character->{$ability})
                            @php $mod = $character->abilityModifier($ability); @endphp
                            <div class="text-xs mt-1" style="color: {{ $mod >= 0 ? '#81c784' : 'var(--color-red-accent)' }};">
                                {{ $mod >= 0 ? '+' : '' }}{{ $mod }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Backstory --}}
    <div class="panel mt-6">
        <h2 class="panel-title" style="color: var(--color-bronze);">{{ __('character.backstory') }}</h2>
        @if($character->backstory)
            <p class="leading-relaxed" style="color: var(--color-text-muted);">{{ $character->backstory }}</p>
        @else
            <p class="text-sm italic" style="color: var(--color-text-muted);">{{ __('character.no_backstory') }}</p>
        @endif
    </div>

    {{-- Spells --}}
    @if($character->spells->isNotEmpty())
        <div class="panel mt-6">
            <h2 class="panel-title" style="color: #c89ef0;">{{ __('character.spells') }}</h2>
            <div class="space-y-2">
                @foreach($character->spells as $spell)
                    <div class="flex items-center justify-between">
                        <span style="color: var(--color-text);">{{ $spell->name }}</span>
                        @if($spell->pivot->is_prepared)
                            <span class="badge badge-purple">{{ __('character.prepared_spells') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
