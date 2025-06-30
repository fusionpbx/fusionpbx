<?php

namespace App\Rules;

use App\Models\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class E164 implements ValidationRule
{
    private bool $checkCountryCode= false;
    private bool $checkNationalDestinationCode = false;
    private bool $checkSubscriberNumber = false;
    private string $country = '*';

    public function __construct(int $flag = 255, string $country = '*')
    {
        $this->checkCountryCode = $flag & config('freeswitch.CHECK_COUNTRY_CODE');
        $this->checkNationalDestinationCode = $flag & config('freeswitch.CHECK_NATIONAL_DESTINATION_CODE');
        $this->checkSubscriberNumber = $flag & config('freeswitch.CHECK_SUBSCRIBER_NUMBER');
        $this->country = $country;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // For now, this RULE only does Geographic number validation
        if ($this->checkCountryCode)
        {
            if (strlen($value) > 3)
            {
                $fail('A country code can not exceed of 3 digits.');
            }

            if ((intval($value) < 1>) || (strlen($value) == 0))
            {
                $fail('A country code can not be zero or null.');
            }

            $countryQuery = Country::where('country_code', $value);
            $countryCount = $countryQuery->count();
            if ($countryCount == 0)
            {
                $fail('A country code is not listed in our DB. If you believe this is an error, contact your admin.');
            }
        }

        if ($this->checkCountryCode && $this->checkNationalDestinationCode)
        {
            // CC + NDC len must be 7 always
            if (strlen($value) < 7)
            {
                $fail('The number on :attribute is too short.');
            }
        }

        if ($this->checkCountryCode && $this->checkNationalDestinationCode && $this->checkSubscriberNumber)
        {
            // CC + NDC len must be 7 always
            if (strlen($value) > 15)
            {
                $fail('The number on :attribute is too long.');
            }

            // Check Geographical number regex

            If ($this->country == 'AI')
            {
                $r = '^\+?1?(264[2-9]\d{6})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'CA')
            {
                $r = '^\+?1?((204|226|236|249|250|257|263|289|306|343|354|365|367|368|382|387|403|416|418|428|431|437|438|450|468|474|506|514|519|548|579|581|584|587|604|613|639|647|672|683|705|709|742|753|778|780|782|807|819|825|867|873|879|902|905|942)[2-9]\d{6})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'CH')
            {
                $r = '^\+?41(\d{4,12})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'CX')
            {
                $r = '^\+?61(\d{5,15})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'DE')
            {
                $r = '^\+?49(\d{6,13})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'DK')
            {
                $r = '^\+?45(\d{8})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'ES')
            {
                $r = '^\+?34(\d{9})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'FR')
            {
                $r = '^\+?33(\d{9})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'GB')
            {
                $r = '^\+?44(\d{7,10})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'IT')
            {
                $r = '^\+?39(\d{3,11})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'IN')
            {
                $r = '^\+?91(\d{7,10})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'KE')
            {
                $r = '^\+?254(\d{6,10})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'NL')
            {
                $r = '^\+?31(\d{9})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'SE')
            {
                $r = '^\+?46(\d{7,13})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }

            If ($this->country == 'US')
            {
                $r = '^\+?1?((201|202|203|205|206|207|208|209|210|212|213|214|215|216|217|218|219|220|223|224|225|227|228|229|231|234|235|239|240|248|251|252|253|254|256|260|262|267|269|270|272|274|276|279|281|283|301|302|303|304|305|307|308|309|310|312|313|314|315|316|317|318|319|320|321|323|324|325|326|327|329|330|331|332|334|336|337|339|340|341|346|347|350|351|352|353|357|360|361|363|364|369|380|385|386|401|402|404|405|406|407|408|409|410|412|413|414|415|417|419|423|424|425|430|432|434|435|436|440|442|443|445|447|448|457|458|463|464|469|470|472|475|478|479|480|484|501|502|503|504|505|507|508|509|510|512|513|515|516|517|518|520|526|527|528|529|530|531|534|539|540|541|551|557|559|561|562|563|564|567|570|571|572|573|574|575|580|582|585|586|601|602|603|605|606|607|608|609|610|612|614|615|616|617|618|619|620|621|623|624|626|628|629|630|631|636|640|641|645|646|650|651|656|657|659|660|661|662|667|669|670|671|678|679|680|681|682|684|686|689|701|702|703|704|706|707|708|710|712|713|714|715|716|717|718|719|720|724|725|726|727|728|729|730|731|732|734|737|738|740|743|747|748|754|757|760|762|763|765|769|770|771|772|773|774|775|779|781|785|786|787|801|802|803|804|805|806|808|810|812|813|814|815|816|817|818|820|821|826|828|830|831|832|835|837|838|839|840|843|845|847|848|850|854|856|857|858|859|860|861|862|863|864|865|870|872|878|901|903|904|906|907|908|909|910|912|913|914|915|916|917|918|919|920|924|925|928|929|930|931|934|936|937|938|939|940|941|943|945|947|948|949|951|952|954|956|959|970|971|972|973|975|978|979|980|983|984|985|986|989)[2-9]\d{6})$';
                if (!preg_match($r, $value))
                {
                    $fail('The number on :attribute seems to be invalid.');
                }
            }
        }
    }
}
