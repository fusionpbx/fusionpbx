<?php

namespace App\Rules;

use App\Models\Dialplan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class ValidContext implements ValidationRule
{
    private bool $allowPublicContext = false;
    private bool $allowGlobal = false;
    public function __construct(int $flag = 0)
    {
        $this->allowPublicContext= $flag & config('freeswitch.ALLOW_PUBLIC_CONTEXT');
        $this->allowGlobal= $flag & config('freeswitch.ALLOW_GLOBAL_CONTEXT');
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $contexts_query = Dialplan::select('dialplan_context')
                    ->distinct()
                    ->whereNotNull('dialplan_context')
                    ->when(!$this->allowPublicContext, function ($query){
                        return $query->whereNot('dialplan_context','=','public');
                    })
                    ->when(!$this->allowGlobal, function ($q){
                        return $q->whereNotIn('dialplan_context',['global','${domain_name}']);
                    });
        $contexts = $contexts_query->get();
        if(App::hasDebugModeEnabled()){
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $contexts_query: '.$contexts_query->toRawSql());
        }
        $found = array_search($value, $contexts);
        if ($found === false)
        {
            $fail('The :attribute does not have a valid ISO639 value.');
        }
    }
}
