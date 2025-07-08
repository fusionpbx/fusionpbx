<?php

namespace App\Rules;

use App\Models\Dialplan;
use App\Models\Domain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ValidContext implements ValidationRule
{
    private bool $allowPublicContext = false;
    private bool $allowGlobal = false;
    private bool $allowContextSessionMismatch = false;

    public function __construct(int $flag = 0)
    {
        $this->allowContextSessionMismatch= $flag & config('freeswitch.ALLOW_CONTEXT_SESSION_MISMATCH');
        $this->allowPublicContext= $flag & config('freeswitch.ALLOW_PUBLIC_CONTEXT');
        $this->allowGlobal= $flag & config('freeswitch.ALLOW_GLOBAL_CONTEXT');
        //$this->extension_user_context = auth()->user()->hasPermission('extension_user_context');
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((!$this->allowContextSessionMismatch) && (Session::get('domain_name') != $value))
        {
            $fail('The :attribute must match your current tenant domain name.');
        }

        $contexts_query = Dialplan::join(Domain::getTableName(), Dialplan::getTableName().'.domain_uuid', '=', Domain::getTableName().'.domain_uuid')
                    ->select('dialplan_context')
                    ->distinct()
                    ->where(Domain::getTableName().'.domain_enabled', 'true')
                    ->whereNotNull('dialplan_context')
                    ->when(!$this->allowPublicContext, function ($query){
                        return $query->whereNot('dialplan_context','=','public');
                    })
                    ->when(!$this->allowGlobal, function ($q){
                        return $q->whereNotIn('dialplan_context',['global','${domain_name}']);
                    })
                    ->where('dialplan_context','=', $value);
        $contexts_count = $contexts_query->count();
        if(App::hasDebugModeEnabled()){
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $contexts_query: '.$contexts_query->toRawSql());
        }

        if ($contexts_count == 0)
        {
            $fail('The :attribute does not have a valid ISO639 value.');
        }
    }
}
