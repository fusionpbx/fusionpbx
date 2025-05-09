<?php

namespace App\View\Components;

use Closure;
use App\Models\MusicOnHold;
use App\Models\Recording;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;

class SwitchMusicOnHold extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null, $musiconhold = false, $recordings = false, $streams = false)
    {
        $this->name = $name;
        $this->selected = $selected;

        if($musiconhold)
        {
            $mohs = MusicOnHold::where("domain_uuid", Session::get("domain_uuid"))->orWhereNull("domain_uuid")->get();
            $values = [];
            $previous_name = "";

            foreach($mohs as $moh)
            {
                if($previous_name != $moh->music_on_hold_name)
                {
                    $name = "";

                    if(!empty($moh->domain_uuid))
                    {
                        $name = $moh->domain->domain_name . '/';
                    }

                    $name .= $moh->music_on_hold_name;

                    $values[] = [
                        "id" => "local_stream://" . $name,
                        "name" => $moh->music_on_hold_name
                    ];
                }

                $previous_name = $moh->music_on_hold_name;
            }

            $this->options[] = [
                "label" => __("Music on Hold"),
                "values" => $values
            ];
        }

        if($recordings)
        {
            $recordings = Recording::where("domain_uuid", Session::get("domain_uuid"))->get();
            $values = [];

            foreach($recordings as $recording)
            {
                $values[] = [
                    "id" => Session::get("switch")["recordings"]["dir"] ?? "" . '/' . Session::get("domain_name") . "/" . $recording->recording_filename,
                    "name" => $recording->recording_filename
                ];
            }

            $this->options[] = [
                "label" => __("Recordings"),
                "values" => $values
            ];
        }

        // if($streams)
        // {
        // }

        $this->options = json_decode(json_encode($this->options));
    }

    public function render(): View|Closure|string
    {
        return view('components.switch-music-on-hold');
    }
}
