<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class TransformCipherRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'algorithm' => strtolower(trim((string) $this->input('algorithm'))),
            'operation' => strtolower(trim((string) $this->input('operation'))),
            'includeSteps' => $this->has('includeSteps') ? $this->boolean('includeSteps') : true,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'algorithm' => [
                'required',
                'string',
                Rule::in([
                    'atbash',
                    'caesar',
                    'rot13',
                    'substitution',
                    'affine',
                    'vigenere',
                    'beaufort',
                    'autokey',
                    'rail-fence',
                    'columnar',
                ]),
            ],
            'operation' => ['required', Rule::in(['encrypt', 'cifrar', 'decrypt', 'descifrar'])],
            'text' => ['required', 'string', 'min:1', 'max:8000'],
            'parameters' => ['sometimes', 'array'],
            'includeSteps' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'algorithm.required' => 'El algoritmo es obligatorio.',
            'algorithm.in' => 'El algoritmo indicado no existe.',
            'operation.required' => 'La operación es obligatoria.',
            'operation.in' => 'La operación debe ser encrypt, cifrar, decrypt o descifrar.',
            'text.required' => 'El texto es obligatorio.',
            'text.min' => 'El texto no puede estar vacío.',
            'text.max' => 'El texto no puede superar los 8000 caracteres.',
            'parameters.array' => 'Los parámetros deben ser un objeto.',
        ];
    }
}
