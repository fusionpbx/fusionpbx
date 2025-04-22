<?php

namespace App\Http\Requests;

use App\Http\Controllers\DefaultSettingController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$isCreating = $this->isMethod("post");
        $defaultSettings = new DefaultSettingController;
        $reqLength = $defaultSettings->get('users', 'password_length', 'numeric') ?? 0;
        $reqNumber = $defaultSettings->get('users', 'password_number', 'boolean') ?? false;
        $reqLowcase = $defaultSettings->get('users', 'password_lowercase', 'boolean') ?? false;
        $reqUpcase = $defaultSettings->get('users', 'password_uppercase', 'boolean') ?? false;
        $reqSpecial = $defaultSettings->get('users', 'password_special', 'boolean') ?? false;
        $userUnique = $defaultSettings->get('users', 'unique', 'text');

		$rule =  [
			"username" => [
                "bail",
                "required",
                "string",
                "max:255"
            ],
			"user_email" => "bail|required|email|max:254",
			"password" => [
                "bail",
                ($isCreating ? "required" : "nullable"),
                "string",
                "confirmed"
            ],
			"domain_uuid" => "sometimes|uuid|exists:App\Models\Domain,domain_uuid",
			"language" => ['bail', 'nullable','min:2','regex:/[a-z]{2,3}\-\w+/i'],   // TODO: Find a better rule
			"timezone" => ["nullable", 'regex:/^\w+\/\w[\w\-]+\w$/i'],
            "contact_uuid" => "nullable|uuid",
			"user_enabled" => "bail|nullable",
			"api_key" => "nullable|min:30",
		];

        if ($reqLength > 0)
            $rule["password"][] = "min:".$reqLength;

        if ($reqNumber)
            $rule["password"][] = 'regex:/(?=.*[\d])/';

        if ($reqLowcase)
            $rule["password"][] = 'regex:/(?=.*[a-z])/';

        if ($reqUpcase)
            $rule["password"][] = 'regex:/(?=.*[A-Z])/';

        if ($reqSpecial)
            $rule["password"][] = 'regex:/(?=.*[\W])/';

        if ($isCreating){
            if (isset($userUnique) && ($userUnique == 'global'))
                $rule["username"][] = Rule::unique('App\Models\User','username');
            else
                $rule["username"][] = Rule::unique('App\Models\User','username')->where(fn (Builder $query) => $query->where('domain_uuid', Session::get('domain_uuid')));
        }
        return $rule;
	}
}
