<?php

namespace App\View\Components;

use App\Facades\Setting;
use App\Models\Bridge;
use App\Models\CallCenterQueue;
use App\Models\ConferenceCenter;
use App\Models\Dialplan;
use App\Models\Extension;
use App\Models\IVRMenu;
use App\Models\RingGroup;
use App\Models\Variable;
use App\Models\Voicemail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;
use Closure;

class SwitchDestinations extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null, $bridgeType = null, $callCenterType = null, $conferenceCenterType = null, $extensionType = null, $ivrMenuType = null, $switchType = null, $timeConditionType = null, $toneType = null, $ringGroupType = null, $voiceMailType = null)
    {
        $this->name = $name;
        $this->selected = $selected;

        if(!empty($bridgeType))
        {
            $bridges = Bridge::where("domain_uuid", Session::get("domain_uuid"))
                ->where("bridge_enabled", "true")
                ->orderBy("bridge_name")
                ->get();

            $values = [];

            foreach($bridges as $bridge)
            {
                $id = "";

                switch($bridgeType)
                {
                    case "user_contact":
                        $id = $bridge->bridge_destination;
                        break;
                    case "dialplan":
                        $id = "bridge:{$bridge->bridge_destination}";
                        break;
                    case "ivr":
                        $id = "menu-exec-app:bridge {$bridge->bridge_destination}";
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$bridge->bridge_name} {$bridge->description}"
                ];
            }

            $this->setOptions("Bridges", $values);
        }

        if(!empty($callCenterType))
        {
            $callCenterQueues = CallCenterQueue::where("domain_uuid", Session::get("domain_uuid"))
                ->orderBy("queue_name")
                ->get();

            $values = [];

            foreach($callCenterQueues as $callCenterQueue)
            {
                $id = "";

                switch($callCenterType)
                {
                    case "dialplan":
                        $id = "transfer:{$callCenterQueue->queue_extension} XML " . Session::get("domain_name");
                        break;
                    case "ivr":
                        $id = "menu-exec-app:transfer {$callCenterQueue->queue_extension} XML " . Session::get("domain_name");
                        break;
                    case "simple":
                        $id = $callCenterQueue->queue_extension;
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$callCenterQueue->queue_extension} {$callCenterQueue->queue_description}"
                ];
            }

            $this->setOptions("Call Center", $values);
        }

        if(!empty($conferenceCenterType))
        {
            $conferenceCenters = ConferenceCenter::where("domain_uuid", Session::get("domain_uuid"))
                ->where("conference_center_enabled", "true")
                ->orderBy("conference_center_name")
                ->get();

            $values = [];

            foreach($conferenceCenters as $conferenceCenter)
            {
                $id = "";

                switch($conferenceCenterType)
                {
                    case "dialplan":
                        $id = "transfer:{$conferenceCenter->conference_center_extension} XML " . Session::get("domain_name");
                        break;
                    case "ivr":
                        $id = "menu-exec-app:transfer {$conferenceCenter->conference_center_extension} XML " . Session::get("domain_name");
                        break;
                    case "simple":
                        $id = $conferenceCenter->conference_center_extension;
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$conferenceCenter->conference_center_extension} {$conferenceCenter->conference_center_name} {$conferenceCenter->conference_center_description}"
                ];
            }

            $this->setOptions("Conference Centers", $values);
        }

        if(!empty($extensionType))
        {
            $extensions = Extension::where("domain_uuid", Session::get("domain_uuid"))
                ->where("enabled", "true")
                ->orderBy("number_alias")
                ->orderBy("extension")
                ->get();

            $values = [];

            foreach($extensions as $extension)
            {
                $id = "";

                switch($extensionType)
                {
                    case "user_contact":
                        $id = "user/{$extension->extension}@" . Session::get("domain_name");
                        break;
                    case "dialplan":
                        $id = "transfer:{$extension->extension} XML {$extension->user_context}";
                        break;
                    case "ivr":
                        $id = "menu-exec-app:transfer {$extension->extension} XML {$extension->user_context}";
                        break;
                    case "simple":
                        $id = "{$extension->extension}";
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$extension->extension} {$extension->description}"
                ];
            }

            $this->setOptions("Extensions", $values);
        }

        if(!empty($ivrMenuType))
        {
            $ivrs = IVRMenu::where("domain_uuid", Session::get("domain_uuid"))
                ->where("ivr_menu_enabled", "true")
                ->orderBy("ivr_menu_extension")
                ->get();

            $values = [];

            foreach($ivrs as $ivr)
            {
                $id = "";

                switch($ivrMenuType)
                {
                    case "dialplan":
                        $id = "transfer:{$ivr->ivr_menu_extension} XML {$ivr->ivr_menu_context}";
                        break;
                    case "ivr":
                        $id = "menu-exec-app:transfer {$ivr->ivr_menu_extension} XML {$ivr->ivr_menu_context}";
                        break;
                    case "simple":
                        $id = "{$ivr->ivr_menu_extension}";
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$ivr->ivr_menu_extension} {$ivr->ivr_menu_name}"
                ];
            }

            $this->setOptions("IVR Menus", $values);
        }

        if(!empty($switchType))
        {
            $values = [];

            $switchSoundDir = Setting::getSetting("switch", "sounds", "dir");

            $languages = array_filter(glob($switchSoundDir . "/*/*/*"), function ($dir) use ($switchSoundDir)
            {
                $relative = str_replace($switchSoundDir . "/", "", $dir);

                $parts = explode("/", $relative);

                return count($parts) === 3 && preg_match('/^[a-z]{2}$/i', $parts[0]) && preg_match('/^[a-z]{2}$/i', $parts[1]) && is_dir($dir);
            });

            foreach($languages as $key => $path)
            {
                $path = str_replace($switchSoundDir . "/", "", $path);

                list($language, $dialect, $voice) = explode("/", $path);

                switch($switchType)
                {
                    case "dialplan":
                        $id = 'multiset:^^,sound_prefix=$${sounds_dir}' . "/{$language}/{$dialect}/{$voice},default_language={$language},default_dialect={$dialect},default_voice={$voice}";
                        break;
                    case "ivr":
                        $id = 'menu-exec-app:multiset ^^,sound_prefix=$${sounds_dir}' . "/{$language}/{$dialect}/{$voice},default_language={$language},default_dialect={$dialect},default_voice={$voice}";
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => str_replace("/", "_", $path)
                ];
            }

            $this->setOptions("Languages", $values);
        }

        if(!empty($timeConditionType))
        {
            $dialplans = Dialplan::where(function ($query) {
                    $query->where('domain_uuid', Session::get('domain_uuid'))->orWhereNull('domain_uuid');
                })
                ->where("app_uuid", "4b821450-926b-175a-af93-a03c441818b1")
                ->orderBy("dialplan_number")
                ->get();

            $values = [];

            foreach($dialplans as $dialplan)
            {
                $id = "";

                switch($timeConditionType)
                {
                    case "dialplan":
                        $id = "transfer:{$dialplan->dialplan_number} XML {$dialplan->dialplan_context}";
                        break;
                    case "ivr":
                        $id = "menu-exec-app:transfer {$dialplan->dialplan_number} XML {$dialplan->dialplan_context}";
                        break;
                    case "simple":
                        $id = "{$dialplan->dialplan_number}";
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$dialplan->dialplan_number} {$dialplan->dialplan_name} {$dialplan->dialplan_description}"
                ];
            }

            $this->setOptions("Time Conditions", $values);
        }

        if(!empty($toneType))
        {
            $vars = Variable::where("var_category", "Tones")
                ->orderBy("var_name")
                ->get();

            $values = [];

            foreach($vars as $var)
            {
                $id = "";

                switch($toneType)
                {
                    case "dialplan":
                        $id = "playback:tone_stream://{$var->var_filename}";
                        break;
                    case "ivr":
                        $id = "menu-exec-app:playback tone_stream://{$var->var_filename}";
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$var->var_name}"
                ];
            }

            $this->setOptions("Tones", $values);
        }

        if(!empty($ringGroupType))
        {
            $ringGroups = RingGroup::where("domain_uuid", Session::get("domain_uuid"))
                ->where("ring_group_enabled", "true")
                ->orderBy("ring_group_extension")
                ->get();

            $values = [];

            foreach($ringGroups as $ringGroup)
            {
                $id = "";

                switch($ringGroupType)
                {
                    case "dialplan":
                        $id = "transfer:{$ringGroup->ring_group_extension} XML {$ringGroup->ring_group_context}";
                        break;
                    case "ivr":
                        $id = "menu-exec-app:transfer: {$ringGroup->ring_group_extension} XML {$ringGroup->ring_group_context}";
                        break;
                    case "simple":
                        $id = "{$ringGroup->ring_group_extension}";
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$ringGroup->ring_group_extension} {$ringGroup->ring_group_name} {$ringGroup->ring_group_description}"
                ];
            }

            $this->setOptions("Ring Groups", $values);
        }

        if(!empty($voiceMailType))
        {
            $voiceMails = Voicemail::where("domain_uuid", Session::get("domain_uuid"))
                ->where("voicemail_enabled", "true")
                ->orderBy("voicemail_id")
                ->get();

            $values = [];

            foreach($voiceMails as $voiceMail)
            {
                $id = "";

                switch($voiceMailType)
                {
                    case "dialplan":
                        $id = "transfer:*99{$voiceMail->voicemail_id} XML " . Session::get("domain_name");
                        break;
                    case "ivr":
                        $id = "menu-exec-app:transfer *99{$voiceMail->voicemail_id} XML " . Session::get("domain_name");
                        break;
                    case "simple":
                        $id = "{$voiceMail->voicemail_id}";
                        break;
                }

                $values[] = [
                    "id" => $id,
                    "name" => "{$voiceMail->voicemail_id} {$voiceMail->voicemail_description}"
                ];
            }

            $this->setOptions("Voicemails", $values);
        }

        //Other
        $values = [];

        $values[] = [
            "id" => "transfer:*98 XML " . Session::get("domain_name"),
            "name" => __("Check Voicemail")
        ];

        $values[] = [
            "id" => "transfer:*411 XML " . Session::get("domain_name"),
            "name" => __("Company Directory")
        ];

        $values[] = [
            "id" => "transfer:hangup XML " . Session::get("domain_name"),
            "name" => __("Hangup")
        ];

        $values[] = [
            "id" => "transfer:*732 XML " . Session::get("domain_name"),
            "name" => __("Record")
        ];

        $this->setOptions("Other", $values);

        $this->options = json_decode(json_encode($this->options));
    }

    private function setOptions($group, $values)
    {
        $this->options[] = [
            "label" => __($group),
            "values" => $values
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.switch-destinations');
    }
}
