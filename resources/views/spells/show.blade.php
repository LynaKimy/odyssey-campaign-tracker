@extends('layouts.app')

@section('content')
    <a href="{{ route('spells.index') }}" class="inline-block mb-4 text-sm hover:underline" style="color: var(--color-bronze);">&larr; {{ __('ui.back') }}</a>

    <div class="panel corner-decor">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-3xl font-bold" style="color: var(--color-text);">{{ $spell->name }}</h1>
            <p class="mt-1 italic" style="color: var(--color-text-muted);">
                {{ $spell->level_int === 0 ? "{$spell->school} cantrip" : "Level {$spell->level_int} {$spell->school}" }}
            </p>
        </div>

        <div class="meander mb-6"></div>

        {{-- Properties --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-block">
                <div class="stat-label">Casting Time</div>
                <div class="text-sm font-medium mt-1" style="color: var(--color-text);">{{ $spell->casting_time }}</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Range</div>
                <div class="text-sm font-medium mt-1" style="color: var(--color-text);">{{ $spell->range }}</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Components</div>
                <div class="text-sm font-medium mt-1" style="color: var(--color-text);">{{ $spell->components }}</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Duration</div>
                <div class="text-sm font-medium mt-1" style="color: var(--color-text);">
                    {{ $spell->duration }}
                    @if($spell->requires_concentration)
                        <span style="color: #f0d87e;">(C)</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tags --}}
        <div class="flex flex-wrap gap-2 mb-6">
            @if($spell->can_be_cast_as_ritual)
                <span class="badge badge-purple">Ritual</span>
            @endif
            @if($spell->dnd_class)
                @foreach(explode(', ', $spell->dnd_class) as $class)
                    <span class="badge">{{ $class }}</span>
                @endforeach
            @endif
        </div>

        {{-- Description --}}
        <div class="mb-6">
            <h2 class="section-title" style="color: #7ec8f0;">Description</h2>
            <div class="leading-relaxed whitespace-pre-line" style="color: var(--color-text-secondary);">{{ $spell->desc }}</div>
        </div>

        {{-- Higher levels --}}
        @if($spell->higher_level)
            <div>
                <h2 class="section-title" style="color: var(--color-bronze);">At Higher Levels</h2>
                <div class="leading-relaxed whitespace-pre-line" style="color: var(--color-text-secondary);">{{ $spell->higher_level }}</div>
            </div>
        @endif
    </div>
@endsection
