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
            'prefix' => ['bail','nullable','string','min:1',"regex:/(?:[a-z0-9\*#])/i"],
            'suffix' => ['bail','nullable','string','min:1',"regex:/(?:[a-z0-9\*#])/i"],
            'priority' => 'bail|required|integer|min:0',
            'codec' => ['bail','nullable','string','min:1'],
            'enabled' => 'bail|required|in:true,false',
        ];
    }
}
