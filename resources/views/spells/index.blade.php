@extends('layouts.app')

@section('content')
    <h1 class="text-3xl font-bold mb-6" style="color: var(--color-bronze);">{{ __('character.spells') }}</h1>

    {{-- Filters --}}
    <form method="GET" action="{{ route('spells.index') }}" class="panel mb-6 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-48">
            <label class="stat-label block mb-1">{{ __('ui.search') }}</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ui.search') }}...">
        </div>
        <div>
            <label class="stat-label block mb-1">{{ __('character.level') }}</label>
            <select name="level">
                <option value="">—</option>
                @foreach($levels as $level)
                    <option value="{{ $level }}" {{ request('level') === (string)$level ? 'selected' : '' }}>
                        {{ $level === 0 ? 'Cantrip' : "Level {$level}" }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="stat-label block mb-1">School</label>
            <select name="school">
                <option value="">—</option>
                @foreach($schools as $school)
                    <option value="{{ $school }}" {{ request('school') === $school ? 'selected' : '' }}>{{ $school }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('ui.filter') }}</button>
    </form>

    {{-- Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($spells as $spell)
            <a href="{{ route('spells.show', $spell) }}" class="panel transition-all">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-medium" style="font-family: var(--font-heading); font-size: 0.95rem; color: var(--color-text);">{{ $spell->name }}</h3>
                    <span class="badge badge-blue ml-2 shrink-0">
                        {{ $spell->level_int === 0 ? 'Cantrip' : "Lvl {$spell->level_int}" }}
                    </span>
                </div>
                <p class="text-sm italic" style="color: var(--color-text-secondary);">{{ $spell->school }}</p>
                <div class="flex gap-3 mt-2 text-xs" style="color: var(--color-text-muted);">
                    <span>{{ $spell->casting_time }}</span>
                    <span>&bull;</span>
                    <span>{{ $spell->range }}</span>
                    @if($spell->requires_concentration)
                        <span>&bull;</span>
                        <span style="color: #f0d87e;">C</span>
                    @endif
                </div>
            </a>
        @empty
            <p style="color: var(--color-text-muted);" class="col-span-full italic">{{ __('ui.no_results') }}</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $spells->links() }}
    </div>
@endsection
