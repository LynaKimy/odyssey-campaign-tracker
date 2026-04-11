@extends('layouts.app')

@section('content')
    <h1 class="text-3xl font-bold mb-6" style="color: var(--color-bronze);">Initiative Tracker</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Add forms --}}
        <div class="space-y-4">

            {{-- Add monster --}}
            <div class="panel">
                <h2 class="panel-title" style="color: #e87070;">{{ __('character.monsters') }}</h2>
                <div class="relative">
                    <input type="text" id="monster-search" placeholder="{{ __('ui.search') }}..." autocomplete="off"
                           class="w-full">
                    <div id="monster-results" class="absolute z-10 w-full mt-1 hidden" style="background: var(--color-input); border: 1px solid var(--color-border); border-radius: 4px; max-height: 240px; overflow-y: auto;"></div>
                </div>
            </div>

            {{-- Add manual --}}
            <div class="panel">
                <h2 class="panel-title" style="color: var(--color-bronze);">Manual</h2>
                <div class="space-y-3">
                    <div>
                        <label class="stat-label block mb-1">Nom</label>
                        <input type="text" id="manual-name" class="w-full" placeholder="Gandalf...">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="stat-label block mb-1">Initiative</label>
                            <input type="text" id="manual-init" class="w-full" placeholder="15">
                        </div>
                        <div>
                            <label class="stat-label block mb-1">HP</label>
                            <input type="text" id="manual-hp" class="w-full" placeholder="—">
                        </div>
                    </div>
                    <button onclick="addManual()" class="btn btn-primary w-full">{{ __('ui.create') }}</button>
                </div>
            </div>

            {{-- Controls --}}
            <div class="panel">
                <h2 class="panel-title" style="color: #7ec8f0;">Combat</h2>
                <div class="space-y-2">
                    <button onclick="nextTurn()" class="btn btn-primary w-full">&#9654; Next Turn</button>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="sortByInitiative()" class="btn w-full">&#9660; Sort</button>
                        <button onclick="clearAll()" class="btn w-full" style="color: var(--color-red-accent);">&#10005; Clear</button>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <span class="stat-label">Round</span>
                    <span id="round-counter" class="stat-value ml-2">1</span>
                </div>
            </div>
        </div>

        {{-- Right: Initiative list --}}
        <div class="lg:col-span-2">
            <div id="initiative-list" class="space-y-2">
                <p id="empty-msg" class="italic text-center py-8" style="color: var(--color-text-muted);">
                    No combatants yet.
                </p>
            </div>
        </div>
    </div>

    {{-- Monster sheet sidebar --}}
    <div id="monster-sheet" class="fixed top-0 right-0 h-full w-full max-w-lg overflow-y-auto transition-transform duration-300 translate-x-full z-50"
         style="background: var(--color-panel); border-left: 2px solid var(--color-bronze-dim);">
        <div class="p-6">
            <button onclick="closeSheet()" class="btn mb-4">&#10005; Close</button>
            <div id="sheet-content"></div>
        </div>
    </div>
    <div id="sheet-overlay" class="fixed inset-0 bg-black/50 z-40 hidden" onclick="closeSheet()"></div>

@endsection

@push('scripts')
    @vite('resources/js/initiative.js')
@endpush
