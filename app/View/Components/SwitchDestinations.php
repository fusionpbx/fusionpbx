<?php

namespace App\View\Components;

use App\Models\Bridge;
use App\Models\CallCenterQueue;
use App\Models\ConferenceCenter;
use App\Models\Dialplan;
use Closure;
use App\Models\Extension;
use App\Models\IVRMenu;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;

class SwitchDestinations extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null, $bridgeType = null, $callCenterType = null, $conferenceCenterType = null, $extensionType = null, $ivrMenusType = null, $timeConditionsType = null)
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
                        $name = "bridge:{$bridge->bridge_destination}";
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
                        $name = "transfer:{$callCenterQueue->queue_extension} XML " . Session::get("domain_name");
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
                        $name = "transfer:{$conferenceCenter->conference_center_extension} XML " . Session::get("domain_name");
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
                        $name = "transfer:{$extension->extension} XML {$extension->user_context}";
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

        if(!empty($ivrMenusType))
        {
            $ivrs = IVRMenu::where("domain_uuid", Session::get("domain_uuid"))
                ->where("ivr_menu_enabled", "true")
                ->orderBy("ivr_menu_extension")
                ->get();

            $values = [];

            foreach($ivrs as $ivr)
            {
                $id = "";

                switch($ivrMenusType)
                {
                    case "dialplan":
                        $name = "transfer:{$ivr->ivr_menu_extension} XML {$ivr->ivr_menu_context}";
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

        if(!empty($timeConditionsType))
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

                switch($timeConditionsType)
                {
                    case "dialplan":
                        $name = "transfer:{$dialplan->dialplan_number} XML {$dialplan->dialplan_context}";
                        break;
                    case "ivr":
                        $id = "menu-exec-app:transfer {$dialplan->dialplan_number} XML {$ivr->dialplan_context}";
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
