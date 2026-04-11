@extends('layouts.app')

@section('content')
    <a href="{{ route('campaigns.show', $campaign) }}" class="inline-block mb-4 text-sm hover:underline" style="color: var(--color-bronze);">&larr; {{ __('ui.back') }}</a>

    <div class="max-w-lg mx-auto py-6">
        <h1 class="text-3xl font-bold text-center mb-8" style="color: var(--color-bronze);">
            {{ __('character.create_npc') }}
        </h1>

        <div class="panel corner-decor">
            <form method="POST" action="{{ route('campaigns.npcs.store', $campaign) }}">
                @csrf

                {{-- Name --}}
                <div class="mb-5">
                    <label for="name" class="stat-label block mb-1">{{ __('character.npc_name') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="w-full" required autofocus>
                    @error('name')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="mb-5">
                    <label for="description" class="stat-label block mb-1">{{ __('character.description') }}</label>
                    <textarea id="description" name="description" rows="3" class="w-full">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notes --}}
                <div class="mb-5">
                    <label for="notes" class="stat-label block mb-1">{{ __('character.notes') }}</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Location --}}
                <div class="mb-5">
                    <label for="location" class="stat-label block mb-1">{{ __('character.location') }}</label>
                    <input type="text" id="location" name="location" value="{{ old('location') }}" class="w-full">
                    @error('location')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between">
                    <a href="{{ route('campaigns.show', $campaign) }}" class="btn">{{ __('ui.cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('ui.create') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
