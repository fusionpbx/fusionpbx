<?php

namespace App\View\Components;

use App\Models\Contact;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;

class SwitchContacts extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null)
    {
        $this->name = $name;
        $this->selected = $selected;

		$contacts = Contact::orderBy("contact_organization")->get();

        $values = [];

		foreach($contacts as $contact)
		{
			$contact_name = '';

			if(strlen($contact->contact_organization) > 0)
			{
				$contact_name = $contact->contact_organization;
			}

			if(strlen($contact->contact_name_family) > 0)
			{
				if(strlen($contact_name) > 0)
				{
					$contact_name .= ", ";
				}

				$contact_name .= $contact->contact_name_family;
			}

			if(strlen($contact->contact_name_given) > 0)
			{
				if(strlen($contact_name) > 0)
				{
					$contact_name .= ", ";
				}

				$contact_name .= $contact->contact_name_given;
			}

			$values[] = ["id" => $contact->contact_uuid, "name" => $contact_name];
		}


        $this->setOptions("", $values);

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
        return view('components.switch-contacts');
    }
}
