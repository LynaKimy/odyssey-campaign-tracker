@extends('layouts.app')

@section('content')
    <h1 class="text-3xl font-bold mb-6" style="color: var(--color-bronze);">{{ __('character.monsters') }}</h1>

    {{-- Filters --}}
    <form method="GET" action="{{ route('monsters.index') }}" class="panel mb-6 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-48">
            <label class="stat-label block mb-1">{{ __('ui.search') }}</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ui.search') }}...">
        </div>
        <div>
            <label class="stat-label block mb-1">Type</label>
            <select name="type">
                <option value="">—</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="stat-label block mb-1">CR</label>
            <select name="cr">
                <option value="">—</option>
                @foreach($challengeRatings as $cr)
                    <option value="{{ $cr }}" {{ request('cr') === $cr ? 'selected' : '' }}>{{ $cr }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('ui.filter') }}</button>
    </form>

    {{-- Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($monsters as $monster)
            <a href="{{ route('monsters.show', $monster) }}" class="panel transition-all">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-medium" style="font-family: var(--font-heading); font-size: 0.95rem; color: var(--color-text);">{{ $monster->name }}</h3>
                    <span class="badge badge-red ml-2 shrink-0">CR {{ $monster->challenge_rating }}</span>
                </div>
                <p class="text-sm italic" style="color: var(--color-text-secondary);">{{ $monster->size }} {{ $monster->type }}</p>
                <p class="text-xs mt-2" style="color: var(--color-text-muted);">AC {{ $monster->armor_class }} &bull; HP {{ $monster->hit_points }}</p>
            </a>
        @empty
            <p style="color: var(--color-text-muted);" class="col-span-full italic">{{ __('ui.no_results') }}</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $monsters->links() }}
    </div>
@endsection
