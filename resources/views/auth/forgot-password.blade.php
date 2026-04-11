@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto py-12">
        <h1 class="text-3xl font-bold text-center mb-8" style="color: var(--color-bronze);">
            {{ __('auth.reset_password') }}
        </h1>

        <div class="panel corner-decor">
            <p class="text-sm mb-6" style="color: var(--color-text-muted);">
                {{ __('auth.forgot_password_text') }}
            </p>

            @if (session('status'))
                <div class="mb-4 text-sm" style="color: #81c784;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
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

                {{-- Submit --}}
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        {{ __('auth.send_reset_link') }}
                    </button>
                </div>
            </form>

            <div class="meander my-5"></div>

            <p class="text-center text-sm" style="color: var(--color-text-muted);">
                <a href="{{ route('login') }}" class="hover:underline" style="color: var(--color-bronze);">
                    {{ __('auth.login') }}
                </a>
            </p>
        </div>
    </div>
@endsection
