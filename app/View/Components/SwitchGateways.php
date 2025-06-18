<?php

namespace App\View\Components;

use Closure;
use App\Models\Bridge;
use App\Models\Domain;
use App\Models\Gateway;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;

class SwitchGateways extends Component
{
    public $name;
    public $selected;
    public $options;

    public function __construct($name = "", $selected = null)
    {
        $this->name = $name;
        $this->selected = $selected;

		$values = [];

		$bridges =  Bridge::where("domain_uuid", Session::get("domain_uuid"))->where("bridge_enabled", "true")->get();
		$domains =  Domain::where("domain_enabled", "true")->get();
        $gateways = Gateway::where("enabled", "true")
                    ->when(!auth()->user()->hasPermission('outbound_route_any_gateway'), function($query){
                        return $query->where("domain_uuid", Session::get("domain_uuid"));
                    })
                    ->get();
		$previous_domain_uuid = '';
		$domain_name = '';

		$this->setOptions("Gateways", $values);
        $domain_groups = [];

		foreach($gateways as $gateway)
		{
			if(auth()->user()->hasPermission('outbound_route_any_gateway'))
			{
				if($previous_domain_uuid != $gateway->domain_uuid)
				{
					$domain_name = '';

					foreach($domains as $domain)
					{
						if($gateway->domain_uuid == $domain->domain_uuid)
						{
							$domain_name = $domain->domain_name;

							break;
						}
					}

					if(empty($domain_name))
					{
						$domain_name = __('Global');
					}

					$domain_groups[$domain_name] = [];
				}

				$domain_groups[$domain_name][] = [
					"id" => "{$gateway->gateway_uuid}:{$gateway->gateway}",
					"name" => $gateway->gateway
				];
			}
			else
			{
				$domain_groups[$domain_name][] = [
					"id" => "{$gateway->gateway_uuid}:{$gateway->gateway}",
					"name" => $gateway->gateway
				];
			}

			$previous_domain_uuid = $gateway->domain_uuid;
		}

		foreach($domain_groups as $group => $values)
		{
			$this->setOptions("&nbsp;&nbsp;" . $group, $values);
		}

		if(auth()->user()->hasPermission('bridge_view'))
		{
			$values = [];

			foreach($bridges as $bridge)
			{
				$values[] = [
					"id" => "bridge:{$bridge->bridge_destination}",
					"name" => $bridge->bridge_name
				];
			}

			$this->setOptions("Bridges", $values);
		}

		$values = [
			["id" => "enum", "name" => "enum"],
			["id" => "freetdm", "name" => "freetdm"],
			["id" => "transfer:$1 XML {$domain_name}", "name" => "transfer"],
			["id" => "xmpp", "name" => "xmpp"],
		];

		$this->setOptions("Additional Options", $values);

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
        return view('components.switch-gateways');
    }
}
