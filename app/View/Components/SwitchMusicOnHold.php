<?php

namespace App\View\Components;

use Closure;
use App\Models\MusicOnHold;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SwitchMusicOnHold extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null, $musiconhold = true, $streams = true, $recordings = true)
    {
        $this->name = $name;
        $this->selected = $selected;

        if($musiconhold)
        {
            $mohs = MusicOnHold::all();
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
                "label" => "Music on Hold",
                "values" => $values
            ];

        }

        // if($streams)
        // {
        // }

        // if($recordings)
        // {
        // }

        $this->options = json_decode(json_encode($this->options));
    }

    public function render(): View|Closure|string
    {
        return view('components.switch-music-on-hold');
    }
}
