<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $campaign = $this->route('session')->campaign;

        $rules = [
            'summary' => ['nullable', 'string'],
        ];

        if ($user->isGM($campaign)) {
            $rules = array_merge($rules, [
                'session_number' => ['required', 'integer', 'min:1'],
                'title'          => ['nullable', 'string', 'max:255'],
                'planned_at'     => ['required', 'date'],
                'played_at'      => ['nullable', 'date'],
                'gm_notes'       => ['nullable', 'string'],
                'in_game_date'   => ['nullable', 'string', 'max:255'],
                'location'       => ['nullable', 'string', 'max:255'],
                'status'         => ['required', 'in:planned,played,skipped'],
            ]);
        }
        return $rules;
    }
}
