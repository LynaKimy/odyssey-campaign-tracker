@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto py-12">
        <h1 class="text-3xl font-bold text-center mb-8" style="color: var(--color-bronze);">
            {{ __('auth.login') }}
        </h1>

        <div class="panel corner-decor">
            @if (session('status'))
                <div class="mb-4 text-sm" style="color: #81c784;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

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
                        autofocus
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
                        autocomplete="current-password"
                    >
                    @error('password')
                        <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="mb-5 flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="rounded"
                        style="accent-color: var(--color-bronze);"
                    >
                    <label for="remember" class="text-sm" style="color: var(--color-text-muted);">
                        {{ __('auth.remember_me') }}
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between">
                    <a href="{{ route('password.request') }}" class="text-sm hover:underline" style="color: var(--color-bronze);">
                        {{ __('auth.forgot_password') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('auth.login') }}
                    </button>
                </div>
            </form>

            <div class="meander my-5"></div>

            <p class="text-center text-sm" style="color: var(--color-text-muted);">
                {{ __('auth.no_account') }}
                <a href="{{ route('register') }}" class="hover:underline" style="color: var(--color-bronze);">
                    {{ __('auth.register') }}
                </a>
            </p>
        </div>
    </div>
@endsection
