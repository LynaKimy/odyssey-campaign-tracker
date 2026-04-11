<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Odyssey' }} — Odyssey</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">

    {{-- Navigation --}}
    <nav class="border-b" style="background-color: var(--color-panel); border-color: var(--color-border);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="font-heading text-2xl font-bold tracking-wider" style="font-family: var(--font-heading); color: var(--color-bronze); text-shadow: 0 0 20px var(--color-bronze-glow);">
                        ODYSSEY
                    </a>
                    <div class="hidden md:flex items-center gap-6">
                        <a href="{{ route('campaigns.index') }}"
                           class="nav-link {{ request()->routeIs('campaigns.*') ? 'nav-link-active' : '' }}">
                            {{ __('campaign.campaigns') }}
                        </a>
                        <a href="{{ route('monsters.index') }}"
                           class="nav-link {{ request()->routeIs('monsters.*') ? 'nav-link-active' : '' }}">
                            {{ __('character.monsters') }}
                        </a>
                        <a href="{{ route('spells.index') }}"
                           class="nav-link {{ request()->routeIs('spells.*') ? 'nav-link-active' : '' }}">
                            {{ __('character.spells') }}
                        </a>
                        <a href="{{ route('initiative') }}"
                           class="nav-link {{ request()->routeIs('initiative') ? 'nav-link-active' : '' }}">
                            Initiative
                        </a>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    {{-- Auth links --}}
                    @auth
                        <span class="nav-link" style="color: var(--color-text-secondary);">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="nav-link transition-colors" style="background: none; border: none; cursor: pointer;">
                                {{ __('auth.logout') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="nav-link {{ request()->routeIs('login') ? 'nav-link-active' : '' }}">
                            {{ __('auth.login') }}
                        </a>
                        <a href="{{ route('register') }}"
                           class="nav-link {{ request()->routeIs('register') ? 'nav-link-active' : '' }}">
                            {{ __('auth.register') }}
                        </a>
                    @endauth

                    <span style="color: var(--color-border);">&#9671;</span>

                    {{-- Locale switcher --}}
                    <div class="flex items-center gap-3" style="font-family: var(--font-heading); font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase;">
                        <a href="{{ route('locale.switch', 'fr') }}"
                           class="{{ app()->getLocale() === 'fr' ? '' : 'opacity-40 hover:opacity-80' }} transition-opacity"
                           style="color: var(--color-bronze);">FR</a>
                        <span style="color: var(--color-border);">&#9671;</span>
                        <a href="{{ route('locale.switch', 'en') }}"
                           class="{{ app()->getLocale() === 'en' ? '' : 'opacity-40 hover:opacity-80' }} transition-opacity"
                           style="color: var(--color-bronze);">EN</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- Meander divider --}}
    <div class="meander"></div>

    {{-- Content --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    {{-- Footer --}}
    <div class="meander"></div>
    <footer class="py-6 text-center" style="background-color: var(--color-panel); border-top: 1px solid var(--color-border);">
        <p style="font-family: var(--font-heading); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-text-muted);">
            ✦ Odyssey &mdash; Campaign Tracker ✦
        </p>
        <p class="mt-1" style="font-family: var(--font-sans); font-size: 0.8rem; font-style: italic; color: var(--color-text-muted); opacity: 0.5;">
            Data from <a href="https://open5e.com" class="hover:underline" style="color: var(--color-bronze);" target="_blank">Open5e</a>
        </p>
    </footer>

</body>
</html>
