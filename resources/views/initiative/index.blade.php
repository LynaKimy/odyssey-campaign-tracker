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

    <script>
        let combatants = JSON.parse(localStorage.getItem('odyssey_initiative') || '[]');
        let currentTurn = parseInt(localStorage.getItem('odyssey_turn') || '0');
        let round = parseInt(localStorage.getItem('odyssey_round') || '1');
        let debounceTimer = null;

        // ── Render ──────────────────────────────────────────────
        function render() {
            const list = document.getElementById('initiative-list');
            const empty = document.getElementById('empty-msg');
            document.getElementById('round-counter').textContent = round;

            if (combatants.length === 0) {
                list.innerHTML = '';
                list.appendChild(empty.cloneNode(true));
                save();
                return;
            }

            list.innerHTML = combatants.map((c, i) => `
                <div class="panel flex items-center gap-4 transition-all ${i === currentTurn ? 'corner-decor' : ''}"
                     style="${i === currentTurn ? 'border-color: var(--color-bronze); background: rgba(212, 176, 92, 0.06);' : ''}">

                    {{-- Initiative --}}
                    <div class="shrink-0 text-center" style="width: 50px;">
                        <input type="number" value="${c.initiative ?? ''}"
                               onchange="updateInit(${i}, this.value)"
                               class="w-full text-center"
                               style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 600; background: transparent; border: none; color: ${i === currentTurn ? 'var(--color-bronze)' : 'var(--color-text)'}; padding: 0;">
                        <div class="stat-label">Init</div>
                    </div>

                    {{-- Name + info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            ${c.monsterId
                                ? `<a href="#" onclick="openSheet(${c.monsterId}); return false;"
                                      class="font-semibold hover:underline truncate"
                                      style="font-family: var(--font-heading); color: var(--color-text);">
                                      ${esc(c.name)}</a>
                                   <a href="/monsters/${c.monsterId}" target="_blank" class="shrink-0" style="color: var(--color-text-muted); font-size: 0.75rem;" title="Open full page">&#8599;</a>`
                                : `<span class="font-semibold truncate" style="font-family: var(--font-heading); color: var(--color-text);">${esc(c.name)}</span>`
                            }
                            ${c.cr ? `<span class="badge badge-red shrink-0">CR ${esc(c.cr)}</span>` : ''}
                            ${!c.monsterId ? '<span class="badge shrink-0">Manual</span>' : ''}
                        </div>
                        ${c.type ? `<div class="text-xs italic" style="color: var(--color-text-muted);">${esc(c.size || '')} ${esc(c.type)}</div>` : ''}
                    </div>

                    {{-- HP --}}
                    ${c.maxHp ? `
                    <div class="shrink-0 text-center" style="width: 90px;">
                        <div class="flex items-center gap-1">
                            <button onclick="adjustHp(${i}, -1)" class="px-1" style="color: var(--color-red-accent);">&#9660;</button>
                            <input type="number" value="${c.currentHp}"
                                   onchange="setHp(${i}, this.value)"
                                   class="w-12 text-center"
                                   style="font-family: var(--font-heading); font-size: 1rem; font-weight: 600; background: transparent; border: none; padding: 0;
                                          color: ${c.currentHp <= 0 ? 'var(--color-red-accent)' : c.currentHp <= c.maxHp * 0.25 ? '#e87070' : 'var(--color-text)'};">
                            <button onclick="adjustHp(${i}, 1)" class="px-1" style="color: #81c784;">&#9650;</button>
                        </div>
                        <div class="stat-label">/ ${c.maxHp} HP</div>
                    </div>` : ''}

                    {{-- Remove --}}
                    <button onclick="remove(${i})" class="shrink-0 btn px-2 py-1" style="color: var(--color-red-accent); border-color: rgba(212, 80, 80, 0.2);">&#10005;</button>
                </div>
            `).join('');

            save();
        }

        // ── Actions ─────────────────────────────────────────────
        function addMonster(monster) {
            combatants.push({
                name: monster.name,
                monsterId: monster.id,
                initiative: null,
                currentHp: monster.hit_points,
                maxHp: monster.hit_points,
                ac: monster.armor_class,
                cr: monster.challenge_rating,
                type: monster.type,
                size: monster.size,
            });
            render();
            document.getElementById('monster-search').value = '';
            document.getElementById('monster-results').classList.add('hidden');
        }

        function addManual() {
            const name = document.getElementById('manual-name').value.trim();
            if (!name) return;
            const init = parseInt(document.getElementById('manual-init').value) || null;
            const hp = parseInt(document.getElementById('manual-hp').value) || null;
            combatants.push({
                name: name,
                monsterId: null,
                initiative: init,
                currentHp: hp,
                maxHp: hp,
                cr: null,
                type: null,
                size: null,
            });
            document.getElementById('manual-name').value = '';
            document.getElementById('manual-init').value = '';
            document.getElementById('manual-hp').value = '';
            render();
        }

        function updateInit(i, val) {
            combatants[i].initiative = parseInt(val) || null;
            save();
        }

        function setHp(i, val) {
            combatants[i].currentHp = parseInt(val) || 0;
            render();
        }

        function adjustHp(i, delta) {
            combatants[i].currentHp = Math.max(0, (combatants[i].currentHp || 0) + delta);
            render();
        }

        function remove(i) {
            combatants.splice(i, 1);
            if (currentTurn >= combatants.length) currentTurn = 0;
            render();
        }

        function sortByInitiative() {
            combatants.sort((a, b) => (b.initiative || 0) - (a.initiative || 0));
            currentTurn = 0;
            render();
        }

        function nextTurn() {
            if (combatants.length === 0) return;
            currentTurn++;
            if (currentTurn >= combatants.length) {
                currentTurn = 0;
                round++;
            }
            render();
        }

        function clearAll() {
            if (!confirm('Clear all combatants?')) return;
            combatants = [];
            currentTurn = 0;
            round = 1;
            render();
        }

        function save() {
            localStorage.setItem('odyssey_initiative', JSON.stringify(combatants));
            localStorage.setItem('odyssey_turn', currentTurn.toString());
            localStorage.setItem('odyssey_round', round.toString());
        }

        function esc(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // ── Monster search ──────────────────────────────────────
        document.getElementById('monster-search').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            const results = document.getElementById('monster-results');

            if (q.length < 2) {
                results.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`/api/monsters/search?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(monsters => {
                        if (monsters.length === 0) {
                            results.classList.add('hidden');
                            return;
                        }
                        results.innerHTML = monsters.map(m => `
                            <div onclick='addMonster(${JSON.stringify(m).replace(/'/g, "&#39;")})'
                                 class="px-3 py-2 cursor-pointer transition-colors"
                                 style="border-bottom: 1px solid var(--color-border);"
                                 onmouseenter="this.style.background='rgba(212, 176, 92, 0.08)'"
                                 onmouseleave="this.style.background='transparent'">
                                <div class="flex items-center justify-between">
                                    <span style="font-family: var(--font-heading); font-size: 0.9rem; color: var(--color-text);">${esc(m.name)}</span>
                                    <span class="badge badge-red">CR ${esc(m.challenge_rating)}</span>
                                </div>
                                <div class="text-xs" style="color: var(--color-text-muted);">${esc(m.size)} ${esc(m.type)} &bull; AC ${m.armor_class} &bull; HP ${m.hit_points}</div>
                            </div>
                        `).join('');
                        results.classList.remove('hidden');
                    });
            }, 250);
        });

        // Close dropdown on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#monster-search') && !e.target.closest('#monster-results')) {
                document.getElementById('monster-results').classList.add('hidden');
            }
        });

        // ── Monster sheet sidebar ───────────────────────────────
        function openSheet(monsterId) {
            fetch(`/monsters/${monsterId}`)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const content = doc.querySelector('.corner-decor');
                    if (content) {
                        document.getElementById('sheet-content').innerHTML = content.outerHTML;
                    }
                    document.getElementById('monster-sheet').classList.remove('translate-x-full');
                    document.getElementById('sheet-overlay').classList.remove('hidden');
                });
        }

        function closeSheet() {
            document.getElementById('monster-sheet').classList.add('translate-x-full');
            document.getElementById('sheet-overlay').classList.add('hidden');
        }

        // Close sheet with Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSheet();
        });

        // Enter key on manual form
        document.getElementById('manual-hp').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') addManual();
        });
        document.getElementById('manual-name').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') addManual();
        });

        // ── Init ────────────────────────────────────────────────
        render();
    </script>
@endsection
