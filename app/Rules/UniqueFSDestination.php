<?php

namespace App\Rules;

use App\Models\CallCenterQueue;
use App\Models\Conference;
use App\Models\ConferenceCenter;
use App\Models\Extension;
use App\Models\Fax;
use App\Models\IVRMenu;
use App\Models\RingGroup;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Session;

class UniqueFSDestination implements ValidationRule
{
    private bool $checkCallCenters = true;
    private bool $checkConferences = true;      // Legacy Conference
    private bool $checkConferenceCenters = true;
    private bool $checkExtensions = true;
    private bool $checkFaxes = true;
    private bool $checkIVRs = true;
    private bool $checkRingGroups = true;
    public function __construct(int $flag = 255)
    {
        $this->checkCallCenters = $flag & config('freeswitch.CHECK_CALLCENTERS');
        $this->checkConferences = $flag & config('freeswitch.CHECK_CONFERENCES');
        $this->checkConferenceCenters = $flag & config('freeswitch.CHECK_CONFERENCECENTERS');
        $this->checkExtensions = $flag & config('freeswitch.CHECK_EXTENSIONS');
        $this->checkFaxes = $flag & config('freeswitch.CHECK_FAXES');
        $this->checkIVRs = $flag & config('freeswitch.CHECK_IVRS');
        $this->checkRingGroups = $flag & config('freeswitch.CHECK_RINGGROUPS');

    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->checkCallCenters)
        {
            $o = CallCenterQueue::where('queue_extension', $value)->where('domain_uuid', Session::get('domain_uuid'));
            if ($o->count() > 0)
            {
                $fail('The :attribute is already used in a Call Center Queue.');
            }
        }

        if ($this->checkConferences)
        {
            $o = Conference::where('conference_extension', $value)->where('domain_uuid', Session::get('domain_uuid'));
            if ($o->count() > 0)
            {
                $fail('The :attribute is already used in a legacy Conference.');
            }
        }

        if ($this->checkConferenceCenters)
        {
            $o = ConferenceCenter::where('conference_center_extension', $value)->where('domain_uuid', Session::get('domain_uuid'));
            if ($o->count() > 0)
            {
                $fail('The :attribute is already used in a Conference Center.');
            }
        }

        if ($this->checkExtensions)
        {
            $o = Extension::where('extension', $value)->where('domain_uuid', Session::get('domain_uuid'));
            if ($o->count() > 0)
            {
                $fail('The :attribute is already used in a Extension.');
            }

            $o = Extension::where('number_alias', $value)->where('domain_uuid', Session::get('domain_uuid'));
            if ($o->count() > 0)
            {
                $fail('The :attribute is already used in a Extension (Alias).');
            }
        }

        if ($this->checkFaxes)
        {
            $o = Fax::where('fax_extension', $value)->where('domain_uuid', Session::get('domain_uuid'));
            if ($o->count() > 0)
            {
                $fail('The :attribute is already used in a Fax.');
            }
        }

        if ($this->checkIVRs)
        {
            $o = IVRMenu::where('ivr_menu_extension', $value)->where('domain_uuid', Session::get('domain_uuid'));
            if ($o->count() > 0)
            {
                $fail('The :attribute is already used in a IVR Menu (auto-attendant).');
            }
        }

        if ($this->checkRingGroups)
        {
            $o = RingGroup::where('ring_group_extension', $value)->where('domain_uuid', Session::get('domain_uuid'));
            if ($o->count() > 0)
            {
                $fail('The :attribute is already used in a IVR Menu (auto-attendant).');
            }
        }
    }
}
