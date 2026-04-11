<?php

namespace App\Http\Requests;

use App\Models\Character;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates character creation form input
 *
 * @description Ensures all character fields are valid, the assigned user
 * is a campaign member, and the name is unique per user+campaign.
 */
class StoreCharacterRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'race' => ['nullable', 'string', 'max:255'],
            'class' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'max_hp' => ['nullable', 'integer', 'min:1'],
            'armor_class' => ['nullable', 'integer', 'min:1'],
            'strength' => ['nullable', 'integer', 'min:1', 'max:30'],
            'dexterity' => ['nullable', 'integer', 'min:1', 'max:30'],
            'constitution' => ['nullable', 'integer', 'min:1', 'max:30'],
            'intelligence' => ['nullable', 'integer', 'min:1', 'max:30'],
            'wisdom' => ['nullable', 'integer', 'min:1', 'max:30'],
            'charisma' => ['nullable', 'integer', 'min:1', 'max:30'],
            'backstory' => ['nullable', 'string', 'max:10000'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Validate that user is a campaign member and name is unique per user+campaign
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $campaign = $this->route('campaign');
                $userId = $this->validated('user_id');

                if (! $campaign->members()->where('user_id', $userId)->exists()) {
                    $validator->errors()->add('user_id', __('validation.exists', ['attribute' => 'user_id']));

                    return;
                }

                if (Character::where('user_id', $userId)
                    ->where('campaign_id', $campaign->id)
                    ->where('name', $this->validated('name'))
                    ->exists()
                ) {
                    $validator->errors()->add('name', __('validation.unique', ['attribute' => 'name']));
                }
            },
        ];
    }
}
