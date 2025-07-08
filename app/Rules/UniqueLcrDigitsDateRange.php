<?php
namespace App\Rules;

use App\Models\Lcr;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueLcrDigitsDateRange implements ValidationRule
{
    protected ?string $currentUuid;

    public function __construct(?string $currentUuid = null)
    {
        $this->currentUuid = $currentUuid;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = $value;
        $dateStart = request('date_start');
        $dateEnd = request('date_end');

        $conflict = Lcr::where('digits', $digits)
            ->when($this->currentUuid, function ($query) {
                $query->where('lcr_uuid', '!=', $this->currentUuid);
            })
            ->where(function ($query) use ($dateStart, $dateEnd) {
                $query->where(function ($q) use ($dateStart, $dateEnd) {
                    $q->where('date_start', '<=', $dateEnd)
                      ->where('date_end', '>=', $dateStart);
                });
            })
            ->exists();

        if ($conflict) {
            $fail("The digits value '{$digits}' is already used for an overlapping date range.");
        }
    }
}
