<?php

namespace App\View\Components;

use App\Models\Bridge;
use App\Models\CallCenterQueue;
use App\Models\ConferenceCenter;
use Closure;
use App\Models\Extension;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;

class SwitchDestinations extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null, $bridgeType = null, $callCenterType = null, $conferenceCenterType = null, $extensionType = null)
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
                        $id = "menu-exec-app:transfer {$extension->extension} XML \${$extension->user_context}";
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
