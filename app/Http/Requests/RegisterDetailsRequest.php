<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterDetailsRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'name' => 'required|string',
            'country' => 'required|string',
            'mobile' => 'required_without:email|regex:/(09)[0-9]{9}/unique:users,mobile',
            'email' => 'required_without:mobile|email|unique:users,email',
        ];
    }
}
