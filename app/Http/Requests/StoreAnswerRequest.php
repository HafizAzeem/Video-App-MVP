<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'exists:questions,id'],
            'text' => ['nullable', 'string', 'max:1000'],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,webm,ogg', 'max:25600'],
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'Please select a question.',
            'question_id.exists' => 'Invalid question selected.',
            'text.max' => 'Answer must not exceed 1000 characters.',
            'audio.mimes' => 'Audio must be mp3, wav, webm, or ogg format.',
            'audio.max' => 'Audio file must not exceed 25MB.',
        ];
    }
}
