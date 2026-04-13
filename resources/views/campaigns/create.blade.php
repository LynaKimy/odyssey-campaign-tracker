@extends('layouts.app')

@section('content')
    <a href="{{ route('campaigns.index') }}" class="inline-block mb-4 text-sm hover:underline" style="color: var(--color-bronze);">&larr; {{ __('ui.back') }}</a>

    <div class="max-w-lg mx-auto py-6">
        <h1 class="text-3xl font-bold text-center mb-8" style="color: var(--color-bronze);">
            {{ __('campaign.create_campaign') }}
        </h1>

        <div class="panel corner-decor">
            <form method="POST" action="{{ route('campaigns.store') }}">
                @csrf

                {{-- Name --}}
                <div class="mb-5">
                    <label for="name" class="stat-label block mb-1">{{ __('campaign.name') }}</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        class="w-full"
                        required
                        autofocus
                    >
                    @error('name')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="mb-5">
                    <label for="description" class="stat-label block mb-1">{{ __('campaign.description') }}</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="w-full tinymce"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Game System --}}
                <div class="mb-5">
                    <label for="system" class="stat-label block mb-1">{{ __('campaign.system') }}</label>
                    <select id="system" name="system" class="w-full" required>
                        <option value="">{{ __('campaign.select_system') }}</option>
                        @foreach($gameSystems as $system)
                            <option value="{{ $system->value }}" {{ old('system') === $system->value ? 'selected' : '' }}>
                                {{ $system->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('system')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Visibility --}}
                <div class="mb-6 flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="is_public"
                        name="is_public"
                        value="1"
                        class="rounded"
                        style="accent-color: var(--color-bronze);"
                        {{ old('is_public') ? 'checked' : '' }}
                    >
                    <label for="is_public" class="text-sm" style="color: var(--color-text-muted);">
                        {{ __('campaign.public') }}
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between">
                    <a href="{{ route('campaigns.index') }}" class="btn">{{ __('ui.cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('ui.create') }}</button>
                </div>
            </form>
        </div>
    </div>

@endsection
