@extends('layouts.app')

@section('content')
    <a href="{{ route('campaigns.index') }}" class="inline-block mb-4 text-sm hover:underline" style="color: var(--color-bronze);">&larr; {{ __('ui.back') }}</a>

    @if (session('status'))
        <div class="mb-4 text-sm" style="color: #81c784;">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 text-sm" style="color: var(--color-red-accent);">{{ session('error') }}</div>
    @endif

    <div class="panel corner-decor mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold" style="color: var(--color-text);">{{ $campaign->name }}</h1>
                <p class="mt-1 italic" style="color: var(--color-text-muted);">
                    {{ __('campaign.created_by') }} {{ $campaign->creator->name }}
                    @if($campaign->system)
                        &bull; {{ $campaign->system->label() }}
                    @endif
                </p>
            </div>
            @if($campaign->is_public)
                <span class="badge badge-green">{{ __('campaign.public') }}</span>
            @endif
        </div>

        <div class="meander mb-4"></div>

        @if($campaign->description)
            <p class="leading-relaxed" style="color: var(--color-text-muted);">{{ $campaign->description }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Members --}}
        <div class="panel">
            <h2 class="panel-title" style="color: var(--color-bronze);">{{ __('campaign.members') }}</h2>
            <ul class="space-y-2">
                @foreach($campaign->members as $member)
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span style="color: var(--color-text);">{{ $member->name }}</span>
                            <span class="badge {{ $member->pivot->role === 'mj' ? '' : 'badge-blue' }}">
                                {{ \App\Enums\CampaignRole::from($member->pivot->role)->label() }}
                            </span>
                        </div>
                        @auth
                            @if(auth()->user()->isGM($campaign) && $member->id !== $campaign->created_by && $member->id !== auth()->id())
                                <form method="POST" action="{{ route('campaigns.members.destroy', [$campaign, $member]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn text-xs" style="color: var(--color-red-accent); padding: 0.2rem 0.5rem;"
                                            onclick="return confirm('{{ __('ui.confirm') }}')">
                                        {{ __('campaign.remove') }}
                                    </button>
                                </form>
                            @endif
                        @endauth
                    </li>
                @endforeach
            </ul>

            @auth
                {{-- Invite form (MJ only) --}}
                @if(auth()->user()->isGM($campaign))
                    <div class="meander my-4"></div>
                    <h3 class="text-sm font-medium mb-3" style="font-family: var(--font-heading); color: var(--color-bronze); text-transform: uppercase; letter-spacing: 0.06em;">
                        {{ __('campaign.invite_member') }}
                    </h3>
                    <form method="POST" action="{{ route('campaigns.members.store', $campaign) }}">
                        @csrf
                        <div class="mb-3">
                            <input type="email" name="email" placeholder="{{ __('auth.email') }}"
                                   value="{{ old('email') }}" class="w-full" required>
                            @error('email')
                                <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <select name="role" class="w-full">
                                @foreach(\App\Enums\CampaignRole::cases() as $role)
                                    <option value="{{ $role->value }}" {{ old('role', 'joueur') === $role->value ? 'selected' : '' }}>
                                        {{ $role->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm" style="color: var(--color-red-accent);">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary text-sm">{{ __('campaign.invite_member') }}</button>
                    </form>
                @endif

                {{-- Leave campaign (non-creator members) --}}
                @if(auth()->user()->isMemberOf($campaign) && auth()->id() !== $campaign->created_by)
                    <div class="meander my-4"></div>
                    <form method="POST" action="{{ route('campaigns.leave', $campaign) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn text-sm" style="color: var(--color-red-accent);"
                                onclick="return confirm('{{ __('ui.confirm') }}')">
                            {{ __('campaign.leave_campaign') }}
                        </button>
                    </form>
                @endif
            @endauth
        </div>

        {{-- Characters --}}
        <div class="panel">
            <h2 class="panel-title" style="color: #7ec8f0;">{{ __('character.characters') }}</h2>
            @forelse($campaign->characters as $character)
                <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--color-border);">
                    @auth
                        @if(auth()->user()->isGM($campaign) || $character->user_id === auth()->id())
                            <a href="{{ route('campaigns.characters.show', [$campaign, $character]) }}" class="font-medium hover:underline" style="font-family: var(--font-heading); color: var(--color-text);">{{ $character->name }}</a>
                        @else
                            <div class="font-medium" style="font-family: var(--font-heading); color: var(--color-text);">{{ $character->name }}</div>
                        @endif
                    @else
                        <div class="font-medium" style="font-family: var(--font-heading); color: var(--color-text);">{{ $character->name }}</div>
                    @endauth
                    <div class="text-sm italic" style="color: var(--color-text-muted);">
                        {{ $character->race }} {{ $character->class }} {{ __('character.level') }} {{ $character->level }}
                    </div>
                </div>
            @empty
                <p class="text-sm italic" style="color: var(--color-text-muted);">{{ __('ui.no_results') }}</p>
            @endforelse
            @auth
                @if(auth()->user()->isMemberOf($campaign))
                    <div class="mt-4">
                        <a href="{{ route('campaigns.characters.create', $campaign) }}" class="btn btn-primary text-sm w-full text-center block">
                            {{ __('character.create_character') }}
                        </a>
                    </div>
                @endif
            @endauth
        </div>

        {{-- NPCs --}}
        <div class="panel">
            <h2 class="panel-title" style="color: #81c784;">{{ __('character.npcs') }}</h2>
            @forelse($campaign->npcs as $npc)
                <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--color-border);">
                    <div class="font-medium" style="font-family: var(--font-heading); color: var(--color-text);">{{ $npc->name }}</div>
                    @if($npc->location)
                        <div class="text-sm italic" style="color: var(--color-text-muted);">{{ $npc->location }}</div>
                    @endif
                </div>
            @empty
                <p class="text-sm italic" style="color: var(--color-text-muted);">{{ __('ui.no_results') }}</p>
            @endforelse
            @auth
                @if(auth()->user()->isGM($campaign))
                    <div class="mt-4">
                        <a href="{{ route('campaigns.npcs.create', $campaign) }}" class="btn btn-primary text-sm w-full text-center block">
                            {{ __('character.create_npc') }}
                        </a>
                    </div>
                @endif
            @endauth
        </div>
    </div>
@endsection
