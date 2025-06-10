<?php

namespace App\Http\Requests;

use App\Models\CarrierGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class CarrierGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public static function rules(): array
    {
        return [
            'gateway_uuid' => 'bail|required|uuid|exists:App\Models\Gateway,gateway_uuid',
            'prefix' => 'bail|required|integer|min:1|max:100',
            'suffix' => 'bail|required|integer|min:1|max:100',
            'priority' => 'bail|required|integer|min:1|max:100',
            'codec' => 'bail|required|integer|min:1|max:100',
            'enabled' => 'bail|required|in:true,false',
        ];
    }
}
