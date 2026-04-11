@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto py-12">
        <h1 class="text-3xl font-bold text-center mb-8" style="color: var(--color-bronze);">
            {{ __('auth.register') }}
        </h1>

        <div class="panel corner-decor">
            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Name --}}
                <div class="mb-5">
                    <label for="name" class="stat-label block mb-1">{{ __('auth.name') }}</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        class="w-full"
                        required
                        autofocus
                        autocomplete="name"
                    >
                    @error('name')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-5">
                    <label for="email" class="stat-label block mb-1">{{ __('auth.email') }}</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full"
                        required
                        autocomplete="username"
                    >
                    @error('email')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-5">
                    <label for="password" class="stat-label block mb-1">{{ __('auth.password') }}</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="w-full"
                        required
                        autocomplete="new-password"
                    >
                    @error('password')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="mb-5">
                    <label for="password_confirmation" class="stat-label block mb-1">{{ __('auth.confirm_password') }}</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="w-full"
                        required
                        autocomplete="new-password"
                    >
                </div>

                {{-- Submit --}}
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        {{ __('auth.register') }}
                    </button>
                </div>
            </form>

            <div class="meander my-5"></div>

            <p class="text-center text-sm" style="color: var(--color-text-muted);">
                {{ __('auth.already_registered') }}
                <a href="{{ route('login') }}" class="hover:underline" style="color: var(--color-bronze);">
                    {{ __('auth.login') }}
                </a>
            </p>
        </div>
    </div>
@endsection
