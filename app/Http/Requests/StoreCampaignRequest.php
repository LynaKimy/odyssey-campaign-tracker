<?php

namespace App\Http\Requests;

use App\Enums\GameSystem;
use App\Services\Purifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates campaign creation form input
 *
 * @description Ensures name, game system, and visibility are valid
 * before creating a new campaign.
 */
class StoreCampaignRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'system' => ['required', 'string', Rule::enum(GameSystem::class)],
            'is_public' => ['boolean'],
        ];
    }

    public function passedValidation() {
        $purifier = new Purifier();
        $this->merge([
            'description' => $purifier->clean($this->input('description'))
        ]);
    }
}
