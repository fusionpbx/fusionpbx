<?php

namespace App\View\Components;

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

    public function __construct($name = "", $selected = null, $extensionsSelectValues = null)
    {
        $this->name = $name;
        $this->selected = $selected;

        if(!empty($extensionsSelectValues))
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

                switch($extensionsSelectValues)
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

            $this->options[] = [
                "label" => __("Extensions"),
                "values" => $values
            ];
        }

        $this->options = json_decode(json_encode($this->options));
    }

    public function render(): View|Closure|string
    {
        return view('components.switch-destinations');
    }
}
