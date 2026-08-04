<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'title' => 'No se pudo procesar la solicitud',
            'detail' => 'Revisa los datos indicados y vuelve a intentarlo.',
            'status' => 400,
            'errors' => $validator->errors()->toArray(),
        ], 400));
    }
}
