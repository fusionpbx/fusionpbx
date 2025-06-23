<?php

namespace App\View\Components;

use App\Models\Extension;
use App\Models\IVRMenu;
use App\Models\RingGroup;
use App\Models\Voicemail;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;

class SwitchCallBlockAction extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null)
    {
        $this->name = $name;
        $this->selected = $selected;

        $values = [];

        $values = [
            ["id" => "reject", "name" => __("Reject")],
            ["id" => "busy", "name" => __("Busy")],
            ["id" => "hold", "name" => __("Hold")],
        ];

        $this->setOptions("", $values);

        if(auth()->user()->hasPermission('call_block_extension'))
        {
            $values = [];

            $extensions = Extension::where("domain_uuid", Session::get("domain_uuid"))->where("enabled", "true")->orderBy("extension")->get();

            foreach($extensions as $extension)
            {
                $values[] = [
                    "id" => "extension:{$extension->extension}",
                    "name" => $extension->extension . " " . $extension->description
                ];
            }

            $this->setOptions("Extensions", $values);
        }

        if(auth()->user()->hasPermission('call_block_ivr'))
        {
            $values = [];

            $ivrs = IVRMenu::where("domain_uuid", Session::get("domain_uuid"))->orderBy("ivr_menu_extension")->get();

            foreach($ivrs as $ivr)
            {
                $values[] = [
                    "id" => "ivr:{$ivr->ivr_menu_extension}",
                    "name" => $ivr->ivr_menu_name . " " . $ivr->ivr_menu_extension
                ];
            }

            $this->setOptions("IVR Menus", $values);
        }

        if(auth()->user()->hasPermission('call_block_ring_group'))
        {
            $values = [];

            $ring_groups = RingGroup::where("domain_uuid", Session::get("domain_uuid"))->orderBy("ring_group_extension")->get();

            foreach($ring_groups as $ring_group)
            {
                $values[] = [
                    "id" => "ring_group:{$ring_group->ring_group_extension}",
                    "name" => $ring_group->ring_group_name . " " . $ring_group->ring_group_extension
                ];
            }

            $this->setOptions("Ring Groups", $values);
        }

        if(auth()->user()->hasPermission('call_block_voicemail'))
        {
            $values = [];

            $voicemails = Voicemail::where("domain_uuid", Session::get("domain_uuid"))->where("voicemail_enabled", "true")->orderBy("voicemail_id")->get();

            foreach($voicemails as $voicemail)
            {
                $values[] = [
                    "id" => "voicemail:{$voicemail->voicemail_id}",
                    "name" => $voicemail->voicemail_id . " " . $voicemail->voicemail_description
                ];
            }

            $this->setOptions("Voicemail", $values);
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
        return view('components.switch-call-block-action');
    }
}
