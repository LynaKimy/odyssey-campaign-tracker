@extends('layouts.app')

@section('content')
    <a href="{{ route('monsters.index') }}" class="inline-block mb-4 text-sm hover:underline" style="color: var(--color-bronze);">&larr; {{ __('ui.back') }}</a>

    <div class="panel corner-decor">
        {{-- Header --}}
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold" style="color: var(--color-text);">{{ $monster->name }}</h1>
                <p class="mt-1 italic" style="color: var(--color-text-muted);">
                    {{ $monster->size }} {{ $monster->type }}{{ $monster->subtype ? " ({$monster->subtype})" : '' }}, {{ $monster->alignment }}
                </p>
            </div>
            <span class="badge badge-red text-base px-3 py-1">CR {{ $monster->challenge_rating }}</span>
        </div>

        <div class="meander mb-6"></div>

        {{-- Core stats --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="stat-block">
                <div class="stat-label">AC</div>
                <div class="stat-value">{{ $monster->armor_class }}</div>
                @if($monster->armor_detail)
                    <div class="text-xs mt-1 italic" style="color: var(--color-text-muted);">{{ $monster->armor_detail }}</div>
                @endif
            </div>
            <div class="stat-block">
                <div class="stat-label">HP</div>
                <div class="stat-value">{{ $monster->hit_points }}</div>
                <div class="text-xs mt-1 italic" style="color: var(--color-text-muted);">{{ $monster->hit_dice }}</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Speed</div>
                <div class="text-sm font-medium mt-1" style="color: var(--color-text);">
                    @foreach($monster->speed as $type => $value)
                        @if($type !== 'unit' && $type !== 'hover' && $value > 0)
                            {{ $type }} {{ number_format($value) }}ft{{ !$loop->last ? ',' : '' }}
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ability scores --}}
        @if($monster->strength)
            @php $abbrs = ['strength' => 'STR', 'dexterity' => 'DEX', 'constitution' => 'CON', 'intelligence' => 'INT', 'wisdom' => 'WIS', 'charisma' => 'CHA']; @endphp
            <div class="grid grid-cols-6 gap-2 mb-6">
                @foreach(\App\Models\Monster::ABILITIES as $ability)
                    @php $score = $monster->{$ability} ?? 10; $mod = $monster->abilityModifier($ability); @endphp
                    <div class="stat-block">
                        <div class="stat-label">{{ $abbrs[$ability] }}</div>
                        <div class="stat-value text-lg">{{ $score }}</div>
                        <div class="text-xs mt-0.5" style="color: {{ $mod >= 0 ? '#81c784' : '#e87070' }};">{{ $mod >= 0 ? "+{$mod}" : $mod }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Traits --}}
        @if(!empty($monster->traits))
            <div class="mb-6">
                <h2 class="section-title" style="color: var(--color-bronze);">Traits</h2>
                @foreach($monster->traits as $trait)
                    <div class="mb-3">
                        <span class="font-semibold" style="font-family: var(--font-heading); color: var(--color-text);">{{ $trait['name'] }}.</span>
                        <span style="color: var(--color-text-secondary);">{{ $trait['desc'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Actions --}}
        @if(!empty($monster->actions))
            <div class="mb-6">
                <h2 class="section-title" style="color: #e87070;">Actions</h2>
                @foreach($monster->actions as $action)
                    <div class="mb-3">
                        <span class="font-semibold" style="font-family: var(--font-heading); color: var(--color-text);">{{ $action['name'] }}.</span>
                        <span style="color: var(--color-text-secondary);">{{ $action['desc'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Reactions --}}
        @if(!empty($monster->reactions))
            <div class="mb-6">
                <h2 class="section-title" style="color: #f0d87e;">Reactions</h2>
                @foreach($monster->reactions as $reaction)
                    <div class="mb-3">
                        <span class="font-semibold" style="font-family: var(--font-heading); color: var(--color-text);">{{ $reaction['name'] }}.</span>
                        <span style="color: var(--color-text-secondary);">{{ $reaction['desc'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Legendary Actions --}}
        @if(!empty($monster->legendary_actions))
            <div class="mb-6">
                <h2 class="section-title" style="color: #c89ef0;">Legendary Actions</h2>
                @foreach($monster->legendary_actions as $action)
                    <div class="mb-3">
                        <span class="font-semibold" style="font-family: var(--font-heading); color: var(--color-text);">{{ $action['name'] }}.</span>
                        <span style="color: var(--color-text-secondary);">{{ $action['desc'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
