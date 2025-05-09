<?php

namespace App\View\Components;

use Closure;
use App\Models\MusicOnHold;
use App\Models\Recording;
use App\Models\Stream;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;

class SwitchMusicOnHold extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null, $withMusicOnHold = false, $withRecordings = false, $withStreams = false)
    {
        $this->name = $name;
        $this->selected = $selected;

        if($withMusicOnHold)
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

        if($withRecordings)
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

        if($withStreams)
        {
            $streams = Stream::where(function ($query) {
                $query->where("domain_uuid", Session::get("domain_uuid"))->orWhereNull("domain_uuid");
            })
            ->where("stream_enabled", "true")
            ->orderBy("stream_name", "asc")
            ->get();

            $values = [];

            foreach($streams as $stream)
            {
                $values[] = [
                    "id" => $stream->stream_location,
                    "name" => $stream->stream_name
                ];
            }

            $this->options[] = [
                "label" => __("Streams"),
                "values" => $values
            ];
        }

        $this->options[] = [
            "label" => __("Others"),
            "values" => [
                [
                    "id" => __("silence"),
                    "name" => __("none")
                ]
            ]
        ];

        $this->options = json_decode(json_encode($this->options));
    }

    public function render(): View|Closure|string
    {
        return view('components.switch-music-on-hold');
    }
}
