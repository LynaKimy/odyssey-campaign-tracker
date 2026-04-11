<?php

namespace App\Http\Requests;

use App\Enums\CampaignRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the invite member form input
 *
 * @description Ensures the email belongs to a registered user who is
 * not already a member of the campaign, and the role is valid.
 */
class InviteCampaignMemberRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'role' => ['required', 'string', Rule::enum(CampaignRole::class)],
        ];
    }

    /**
     * Configure additional validation after the base rules pass
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = User::where('email', $this->validated('email'))->first();

                if ($user && $this->route('campaign')->members()->where('user_id', $user->id)->exists()) {
                    $validator->errors()->add('email', __('campaign.already_member'));
                }
            },
        ];
    }
}
