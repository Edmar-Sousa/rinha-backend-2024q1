<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class TransactionRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'valor' => 'required|numeric',
            'tipo'  => 'required|in:c,d',
            'descricao' => 'required|string|min:1|max:10',
        ];
    }
}
