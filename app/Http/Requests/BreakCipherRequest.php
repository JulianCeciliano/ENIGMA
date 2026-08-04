<?php

namespace App\Http\Requests;

class BreakCipherRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->exists('text') && $this->exists('cipherText')) {
            $this->merge(['text' => $this->input('cipherText')]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1', 'max:2000'],
            'cipherText' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.required' => 'El texto cifrado es obligatorio.',
            'text.min' => 'El texto cifrado no puede estar vacío.',
            'text.max' => 'El texto cifrado no puede superar los 2000 caracteres.',
        ];
    }
}
