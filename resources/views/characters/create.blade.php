@extends('layouts.app')

@section('content')
    <a href="{{ route('campaigns.show', $campaign) }}" class="inline-block mb-4 text-sm hover:underline" style="color: var(--color-bronze);">&larr; {{ __('ui.back') }}</a>

    <div class="max-w-lg mx-auto py-6">
        <h1 class="text-3xl font-bold text-center mb-8" style="color: var(--color-bronze);">
            {{ __('character.create_character') }}
        </h1>

        <div class="panel corner-decor">
            <form method="POST" action="{{ route('campaigns.characters.store', $campaign) }}" enctype="multipart/form-data">
                @csrf

                {{-- Owner (MJ: dropdown, Joueur: hidden) --}}
                @if($isGM)
                    <div class="mb-5">
                        <label for="user_id" class="stat-label block mb-1">{{ __('character.owner') }}</label>
                        <select id="user_id" name="user_id" class="w-full">
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" {{ old('user_id') == $member->id ? 'selected' : '' }}>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                @endif

                {{-- Name --}}
                <div class="mb-5">
                    <label for="name" class="stat-label block mb-1">{{ __('character.name') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="w-full" required autofocus>
                    @error('name')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Race & Class --}}
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label for="race" class="stat-label block mb-1">{{ __('character.race') }}</label>
                        <input type="text" id="race" name="race" value="{{ old('race') }}" class="w-full">
                        @error('race')
                            <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="class" class="stat-label block mb-1">{{ __('character.class') }}</label>
                        <input type="text" id="class" name="class" value="{{ old('class') }}" class="w-full">
                        @error('class')
                            <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Level, HP, AC --}}
                <div class="grid grid-cols-3 gap-4 mb-5">
                    <div>
                        <label for="level" class="stat-label block mb-1">{{ __('character.level') }}</label>
                        <input type="number" id="level" name="level" value="{{ old('level', 1) }}" min="1" max="20" class="w-full">
                        @error('level')
                            <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="max_hp" class="stat-label block mb-1">{{ __('character.max_hp') }}</label>
                        <input type="number" id="max_hp" name="max_hp" value="{{ old('max_hp') }}" min="1" class="w-full">
                        @error('max_hp')
                            <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="armor_class" class="stat-label block mb-1">{{ __('character.armor_class') }}</label>
                        <input type="number" id="armor_class" name="armor_class" value="{{ old('armor_class') }}" min="1" class="w-full">
                        @error('armor_class')
                            <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Ability Scores --}}
                <div class="meander my-5"></div>
                <h3 class="text-sm font-medium mb-3" style="font-family: var(--font-heading); color: var(--color-bronze); text-transform: uppercase; letter-spacing: 0.06em;">
                    {{ __('character.ability_scores') }}
                </h3>

                <div class="grid grid-cols-3 gap-4 mb-5">
                    @foreach(['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'] as $ability)
                        <div class="stat-block">
                            <label for="{{ $ability }}" class="stat-label block mb-1">{{ __("character.$ability") }}</label>
                            <input type="number" id="{{ $ability }}" name="{{ $ability }}" value="{{ old($ability, 10) }}" min="1" max="30" class="w-full text-center">
                            @error($ability)
                                <p class="mt-1 text-sm" style="color: var(--color-red-accent); font-size: 0.7rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                {{-- Avatar --}}
                <div class="meander my-5"></div>
                <div class="mb-5">
                    <label for="avatar" class="stat-label block mb-1">{{ __('character.avatar') }}</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*"
                           class="w-full text-sm" style="color: var(--color-text-muted);">
                    @error('avatar')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Backstory --}}
                <div class="mb-5">
                    <label for="backstory" class="stat-label block mb-1">{{ __('character.backstory') }}</label>
                    <textarea id="backstory" name="backstory" rows="4" class="w-full">{{ old('backstory') }}</textarea>
                    @error('backstory')
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
