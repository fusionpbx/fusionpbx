<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Dialplan;
use App\Models\Domain;
use App\Models\Fax;
use App\Http\Requests\DialplanRequest;
use App\Http\Requests\InboundDialplanRequest;
use App\Http\Requests\OutboundDialplanRequest;
use App\Repositories\DialplanDetailRepository;
use App\Repositories\DialplanRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DialplanController extends Controller
{
	protected $dialplanRepository;
	protected $dialplanDetailRepository;

	public function __construct(DialplanRepository $dialplanRepository, DialplanDetailRepository $dialplanDetailRepository)
    {
        $this->dialplanRepository = $dialplanRepository;
        $this->dialplanDetailRepository = $dialplanDetailRepository;
    }

	public function index(Request $request)
	{
		$app_uuid = $request->query("app_uuid");
		$context = $request->query("context");
		$show = $request->query("show");

		return view("pages.dialplans.index", compact("app_uuid", "context", "show"));
	}

	public function create()
	{
        $domains = $this->dialplanRepository->getAllDomains();
        $types = $this->dialplanRepository->getTypesList();
        $dialplan_default_context = $this->dialplanRepository->getDefaultContext(
            request()->input('app_id'),
            Session::get('domain_name')
        );

		return view("pages.dialplans.form", compact("domains", "types", "dialplan_default_context"));
	}

	public function store(DialplanRequest $request)
	{

	}

	public function show(Dialplan $dialplan)
	{
		//
	}

	public function edit(Dialplan $dialplan)
	{
		$domains = Domain::all();
		$dialplan->load("dialplanDetails");
		$types = $this->dialplanRepository->getTypesList();
		$dialplan_default_context = (request()->input('app_id') == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') ? 'public' : Session::get('domain_name');
		return view("pages.dialplans.form", compact("dialplan", "domains", "types", "dialplan_default_context"));
	}

	public function update(DialplanRequest $request, Dialplan $dialplan)
	{

	}

	public function destroy(Dialplan $dialplan)
	{
		$dialplan->delete();
		return redirect()->route('dialplans.index');
	}

	public function createInbound(Request $request)
	{
		$app_uuid = $request->query("app_uuid");
		$destinations = Destination::where("domain_uuid", Session::get("domain_uuid"))->get();
		return view("pages.dialplans.inbound.form", compact("app_uuid", "destinations"));
	}

	public function createOutbound(Request $request)
	{
		$app_uuid = $request->query("app_uuid");

		return view("pages.dialplans.outbound.form", compact("app_uuid"));
	}

	public function storeInbound(InboundDialplanRequest $request)
	{
		$destination = Destination::where("domain_uuid", Session::get("domain_uuid"))->where("destination_uuid", $request->input("destination_uuid"))->first();

		$dialplanData = [
            "domain_uuid" => Session::get("domain_uuid"),
            "app_uuid" => $request->input("app_uuid"),
            "dialplan_name" => $request->input("dialplan_name"),
            "dialplan_number" => isset($destination) ? $destination->destination_number : null,
            "dialplan_order" => $request->input("dialplan_order"),
            "dialplan_continue" => "false",
            "dialplan_destination" => "false",
            "dialplan_context" => "public",
            "dialplan_enabled" => $request->input("dialplan_enabled") ?? "false",
            "dialplan_description" => $request->input("dialplan_description"),
        ];

        $dialplan = $this->dialplanRepository->create($dialplanData);

		$dialplanDetailData = $this->buildInboundRouteDetail($dialplan, $destination, $request);
		$this->dialplanDetailRepository->create($dialplan, $dialplanDetailData);
		$xml = $this->dialplanRepository->buildXML($dialplan);
		$this->dialplanRepository->update($dialplan->dialplan_uuid, ["dialplan_xml" => $xml]);

		return redirect()->to(route("dialplans.index") . "?app_uuid=" . urlencode($request->input("app_uuid")));
	}

	private function buildInboundRouteDetail(Dialplan $dialplan, ?Destination $destination, $request)
	{
		$dialplanDetails = [];

		$y = 0;

		$condition_field_1 = $request->input("condition_field_1");
		$condition_expression_1 = $request->input("condition_expression_1");
		// $condition_field_2 = $request->input("condition_field_2"); //TODO: remove?
		// $condition_expression_2 = $request->input("condition_expression_2"); //TODO: remove?
		$destination_accountcode = '';
		$destination_carrier = '';
        $destination_carrier_uuid = '';
		$limit = $request->input("limit");
		$caller_id_outbound_prefix = $request->input("caller_id_outbound_prefix");
		$fax_uuid = null;
		$domain_name = Session::get("domain_name");
		$condition_expression_2 = null;
		$condition_field_2 = null;

		if($destination)
		{
			$condition_expression_2 = $condition_expression_1;
			$condition_field_2 = $condition_field_1;
			//TODO: review how to build condition_expression_1 properly
			$condition_expression_1 = $destination->destination_number;
			$fax_uuid = $destination->fax_uuid;
			$destination_carrier = $destination->carrier ? $destination->carrier->carrier_name : null;
            $destination_carrier_uuid = $destination->carrier ? $destination->carrier->carrier_uuid : null;
			$destination_accountcode = $destination->destination_accountcode;
		}

		$action_1 = $request->input("action_1");
		// $action_2 = $request->input("action_2"); //TODO: remove?

		list($action_application_1, $action_data_1) = $this->parseAction($action_1);
		// list($action_application_2, $action_data_2) = $this->parseAction($action_2); //TODO: remove?

		if($condition_field_1 && $condition_expression_1)
		{
			$dialplanDetails[] = $this->buildDialplanDetail("condition", $condition_field_1, $condition_expression_1, $y * 10);
            $y++;
		}

		if($condition_field_2)
		{
			$dialplanDetails[] = $this->buildDialplanDetail("condition", $condition_field_2, $condition_expression_2, $y * 10);
			$y++;
		}

		if($destination_accountcode)
		{
			$dialplanDetails[] = $this->buildDialplanDetail("action", "set", "accountcode={$destination_accountcode}", $y * 10);
            $y++;
		}

		if($destination_carrier)
		{
			$dialplanDetails[] = $this->buildDialplanDetail("action", "set", "carrier={$destination_carrier}", $y * 10);
            $y++;
            $dialplanDetails[] = $this->buildDialplanDetail("action", "set", "carrier_uuid={$destination_carrier_uuid}", $y * 10);
            $y++;
		}

		if($limit)
		{
			$dialplanDetails[] = $this->buildDialplanDetail("action", "limit", "hash {$domain_name} inbound {$limit} !USER_BUSY", $y * 10);
            $y++;
		}

		if($caller_id_outbound_prefix)
		{
			$dialplanDetails[] = $this->buildDialplanDetail("action", "set", "effective_caller_id_number={$caller_id_outbound_prefix}\${caller_id_number}", $y * 10);
            $y++;
		}

		if(Str::isUuid($fax_uuid ?? null))
		{
			$fax = Fax::where("domain_uuid", $dialplan->domain_uuid)->where("fax_uuid", $fax_uuid)->first();

			if($fax)
			{
				$dialplanDetails[] = $this->buildDialplanDetail("action", "set", "codec_string=PCMU,PCMA", $y * 10);
                $y++;
				$dialplanDetails[] = $this->buildDialplanDetail("action", "set", "tone_detect_hits=1", $y * 10);
                $y++;
				$dialplanDetails[] = $this->buildDialplanDetail("action", "set", "execute_on_tone_detect=transfer {$fax->fax_extension} XML " . Session::get("domain_name"), $y * 10);
                $y++;
				$dialplanDetails[] = $this->buildDialplanDetail("action", "tone_detect", "fax 1100 r +5000", $y * 10);
                $y++;
				$dialplanDetails[] = $this->buildDialplanDetail("action", "sleep", "3000", $y * 10);
                $y++;
				$dialplanDetails[] = $this->buildDialplanDetail("action", "export", "codec_string=\${ep_codec_string}", $y * 10);
                $y++;
			}
		}

		if(in_array($action_application_1, ["ivr", "conference"]))
		// if(in_array($action_application_1, ["ivr", "conference"]) || in_array($action_application_2, ["ivr", "conference"]))  //TODO: remove?
		{
			$dialplanDetails[] = $this->buildDialplanDetail("action", "answer", "", $y * 10);
            $y++;
		}

		// Add final actions
		if($action_application_1 && $action_data_1)
		{
			$dialplanDetails[] = $this->buildDialplanDetail("action", $action_application_1, $action_data_1, $y * 10);
            $y++;
		}

  		//TODO: remove?
		// if($action_application_2 && $action_data_2)
		// {
		// 	$this->buildDialplanDetail("action", $action_application_2, $action_data_2, $y * 10);
		// }

		return $dialplanDetails;
	}

	private function parseAction(?string $action): array
	{
		if(!$action)
		{
			return [null, null];
		}

		$parts = explode(":", $action);
		$app = array_shift($parts);
		$data = implode(":", $parts);

		return [$app, $data];
	}

	public function storeOutbound(OutboundDialplanRequest $request)
	{
		$dialplan_name = $request->input("dialplan_name") ?? '';
		$dialplan_order = $request->input("dialplan_order") ?? '';
		$dialplan_expressions = explode("\n", $request->input("dialplan_expression") ?? '');
		$prefix_number = $request->input("prefix_number") ?? '';
		$condition_field_1 = $request->input("condition_field_1") ?? '';
		$condition_expression_1 = $request->input("condition_expression_1") ?? '';
		$condition_field_2 = $request->input("condition_field_2") ?? '';
		$condition_expression_2 = $request->input("condition_expression_2") ?? '';
		$gateway = $request->input("gateway") ?? '';
		$limit = $request->input("limit") ?? '';
		$accountcode = $request->input("accountcode") ?? '';
		$toll_allow = $request->input("toll_allow") ?? '';
		$pin_numbers_enable = $request->input("pin_numbers_enabled") ?? null;

		if(empty($pin_numbers_enable))
		{
			$pin_numbers_enable = "false";
		}

		//set the default type
		$gateway_type = 'gateway';
		$gateway_2_type = 'gateway';
		$gateway_3_type = 'gateway';

		//set the gateway type to bridge
		if (strtolower(substr($gateway, 0, 6)) == "bridge")
		{
			$gateway_type = 'bridge';
		}

		//set the type to enum
		if (strtolower(substr($gateway, 0, 4)) == "enum")
		{
			$gateway_type = 'enum';
		}

		//set the type to freetdm
		if (strtolower(substr($gateway, 0, 7)) == "freetdm")
		{
			$gateway_type = 'freetdm';
		}

		//set the type to transfer
		if (strtolower(substr($gateway, 0, 8)) == "transfer")
		{
			$gateway_type = 'transfer';
		}

		//set the type to dingaling
		if (strtolower(substr($gateway, 0, 4)) == "xmpp")
		{
			$gateway_type = 'xmpp';
		}

		//set the gateway_uuid and gateway_name
		if ($gateway_type == "gateway")
		{
			$gateway_array = explode(":", $gateway);
			$gateway_uuid = $gateway_array[0];
			$gateway_name = $gateway_array[1];
		}
		else
		{
			$gateway_name = '';
			$gateway_uuid = '';
		}

		//set the gateway_2 variable
		$gateway_2 = $_POST["gateway_2"];

		//set the type to bridge
		if (strtolower(substr($gateway_2, 0, 6)) == "bridge")
		{
			$gateway_2_type = 'bridge';
		}

		//set type to enum
		if (strtolower(substr($gateway_2, 0, 4)) == "enum")
		{
			$gateway_2_type = 'enum';
		}

		//set the type to freetdm
		if (strtolower(substr($gateway_2, 0, 7)) == "freetdm")
		{
			$gateway_2_type = 'freetdm';
		}
		//set the type to transfer
		if (strtolower(substr($gateway_2, 0, 8)) == "transfer")
		{
			$gateway_type = 'transfer';
		}

		//set the type to dingaling
		if (strtolower(substr($gateway_2, 0, 4)) == "xmpp")
		{
			$gateway_2_type = 'xmpp';
		}

		//set the gateway_2_id and gateway_2_name
		if ($gateway_2_type == "gateway" && !empty($_POST["gateway_2"]))
		{
			$gateway_2_array = explode(":", $gateway_2);
			$gateway_2_id = $gateway_2_array[0];
			$gateway_2_name = $gateway_2_array[1];
		}
		else
		{
			$gateway_2_id = '';
			$gateway_2_name = '';
		}

		//set the gateway_3 variable
		$gateway_3 = $_POST["gateway_3"];

		//set the type to bridge
		if (strtolower(substr($gateway_3, 0, 6)) == "bridge")
		{
			$gateway_3_type = 'bridge';
		}

		//set the type to enum
		if (strtolower(substr($gateway_3, 0, 4)) == "enum")
		{
			$gateway_3_type = 'enum';
		}

		//set the type to freetdm
		if (strtolower(substr($gateway_3, 0, 7)) == "freetdm")
		{
			$gateway_3_type = 'freetdm';
		}

		//set the type to dingaling
		if (strtolower(substr($gateway_3, 0, 4)) == "xmpp")
		{
			$gateway_3_type = 'xmpp';
		}

		//set the type to transfer
		if (strtolower(substr($gateway_3, 0, 8)) == "transfer")
		{
			$gateway_type = 'transfer';
		}

		//set the gateway_3_id and gateway_3_name
		if ($gateway_3_type == "gateway" && !empty($_POST["gateway_3"]))
		{
			$gateway_3_array = explode(":", $gateway_3);
			$gateway_3_id = $gateway_3_array[0];
			$gateway_3_name = $gateway_3_array[1];
		}
		else
		{
			$gateway_3_id = '';
			$gateway_3_name = '';
		}

		//set additional variables
		$dialplan_enabled = $request->input("dialplan_enabled") ?? 'false';
		$dialplan_description = $request->input("dialplan_description");

		//set default to enabled
		if (empty($dialplan_enabled))
		{
			$dialplan_enabled = "true";
		}

		foreach($dialplan_expressions as $dialplan_expression)
		{
			$dialplan_expression = trim($dialplan_expression);

			if(!empty($dialplan_expression))
			{
				switch ($dialplan_expression)
				{
					case "^(\d{7})$":
						$abbrv = "7d";
						break;
					case "^(\d{8})$":
						$abbrv = "8d";
						break;
					case "^(\d{9})$":
						$abbrv = "9d";
						break;
					case "^(\d{10})$":
						$abbrv = "10d";
						break;
					case "^\+?(\d{11})$":
						$abbrv = "11d";
						break;
					case "^(?:\+1|1)?([2-9]\d{2}[2-9]\d{2}\d{4})$":
						$abbrv = "10-11-NANP";
						break;
					case "^(011\d{9,17})$":
						$abbrv = "011.9-17d";
						break;
					case "^\+?1?((?:264|268|242|246|441|284|345|767|809|829|849|473|658|876|664|787|939|869|758|784|721|868|649|340|684|671|670|808)\d{7})$":
						$abbrv = "011.9-17d";
						break;
					case "^(\d{12,20})$":
						$abbrv = __('International');
						break;
					case "^(311)$":
						$abbrv = "311";
						break;
					case "^(411)$":
						$abbrv = "411";
						break;
					case "^(711)$":
						$abbrv = "711";
						break;
					case "(^911$|^933$)":
						$abbrv = "911";
						break;
					case "(^988$)":
						$abbrv = "988";
						break;
					case "^9(\d{3})$":
						$abbrv = "9.3d";
						break;
					case "^9(\d{4})$":
						$abbrv = "9.4d";
						break;
					case "^9(\d{7})$":
						$abbrv = "9.7d";
						break;
					case "^9(\d{10})$":
						$abbrv = "9.10d";
						break;
					case "^9(\d{11})$":
						$abbrv = "9.11d";
						break;
					case "^9(\d{12,20})$":
						$abbrv = "9.12-20";
						break;
					case "^1?(8(00|33|44|55|66|77|88)[2-9]\d{6})$":
						$abbrv = "800";
						break;
					case "^0118835100\d{8}$":
						$abbrv = "inum";
						break;
					default:
						$abbrv = $this->filename_safe($dialplan_expression);
				}

				// Use as outbound prefix all digits beetwen ^ and first (
				$tmp_prefix = preg_replace("/^\^(\d{1,})\(.*/", "$1", $dialplan_expression);
				$tmp_prefix == $dialplan_expression ? $outbound_prefix = "" : $outbound_prefix = $tmp_prefix;

				if ($gateway_type == "gateway")
				{
					$dialplan_name = $gateway_name . "." . $abbrv;

					if ($abbrv == "988")
					{
						$bridge_data = "sofia/gateway/" . $gateway_uuid . "/" . $prefix_number . "18002738255";
					}
					else
					{
						$bridge_data = "sofia/gateway/" . $gateway_uuid . "/" . $prefix_number . "\$1";
					}
				}

				if (!empty($gateway_2_name) && $gateway_2_type == "gateway")
				{
					$extension_2_name = $gateway_2_id . "." . $abbrv;

					if ($abbrv == "988")
					{
						$bridge_2_data = "sofia/gateway/" . $gateway_2_id . "/" . $prefix_number . "18002738255";
					}
					else
					{
						$bridge_2_data = "sofia/gateway/" . $gateway_2_id . "/" . $prefix_number . "\$1";
					}
				}

				if (!empty($gateway_3_name) && $gateway_3_type == "gateway")
				{
					$extension_3_name = $gateway_3_id . "." . $abbrv;

					if ($abbrv == "988")
					{
						$bridge_3_data = "sofia/gateway/" . $gateway_3_id . "/" . $prefix_number . "18002738255";
					}
					else
					{
						$bridge_3_data = "sofia/gateway/" . $gateway_3_id . "/" . $prefix_number . "\$1";
					}
				}

				if ($gateway_type == "freetdm")
				{
					$dialplan_name = "freetdm." . $abbrv;
					$bridge_data = $gateway . "/1/a/" . $prefix_number . "\$1";
				}

				if ($gateway_2_type == "freetdm")
				{
					$extension_2_name = "freetdm." . $abbrv;
					$bridge_2_data .= $gateway_2 . "/1/a/" . $prefix_number . "\$1";
				}

				if ($gateway_3_type == "freetdm")
				{
					$extension_3_name = "freetdm." . $abbrv;
					$bridge_3_data .= $gateway_3 . "/1/a/" . $prefix_number . "\$1";
				}

				if ($gateway_type == "xmpp")
				{
					$dialplan_name = "xmpp." . $abbrv;
					$bridge_data = "dingaling/gtalk/+" . $prefix_number . "\$1@voice.google.com";
				}

				if ($gateway_2_type == "xmpp")
				{
					$extension_2_name = "xmpp." . $abbrv;
					$bridge_2_data .= "dingaling/gtalk/+" . $prefix_number . "\$1@voice.google.com";
				}

				if ($gateway_3_type == "xmpp")
				{
					$extension_3_name = "xmpp." . $abbrv;
					$bridge_3_data .= "dingaling/gtalk/+" . $prefix_number . "\$1@voice.google.com";
				}

				if ($gateway_type == "bridge")
				{
					$dialplan_name = "bridge." . $abbrv;
					$gateway_array = explode(":", $gateway);
					$bridge_data = $gateway_array[1];
				}

				if ($gateway_2_type == "bridge")
				{
					$dialplan_name = "bridge." . $abbrv;
					$gateway_array = explode(":", $gateway_2);
					$bridge_2_data = $gateway_array[1];
				}

				if ($gateway_3_type == "bridge")
				{
					$dialplan_name = "bridge." . $abbrv;
					$gateway_array = explode(":", $gateway_3);
					$bridge_3_data = $gateway_array[1];
				}

				if ($gateway_type == "enum")
				{
					if (empty($bridge_2_data))
					{
						$dialplan_name = "enum." . $abbrv;
					}
					else
					{
						$dialplan_name = $extension_2_name;
					}
					$bridge_data = "\${enum_auto_route}";
				}

				if ($gateway_2_type == "enum")
				{
					$bridge_2_data .= "\${enum_auto_route}";
				}

				if ($gateway_3_type == "enum")
				{
					$bridge_3_data .= "\${enum_auto_route}";
				}

				if ($gateway_type == "transfer")
				{
					$dialplan_name = "transfer." . $abbrv;
					$gateway_array = explode(":", $gateway);
					$bridge_data = $gateway_array[1];
				}

				if ($gateway_2_type == "transfer")
				{
					$gateway_array = explode(":", $gateway_2);
					$bridge_2_data = $gateway_array[1];
				}

				if ($gateway_3_type == "transfer")
				{
					$gateway_array = explode(":", $gateway_3);
					$bridge_3_data = $gateway_array[1];
				}

				if (empty($dialplan_order))
				{
					$dialplan_order = '333';
				}

				$dialplan_context = Session::get("domain_name");
				$dialplan_continue = 'false';
				// $app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';

				//call direction
				$dialplanData = [
					"domain_uuid" => Session::get("domain_uuid"),
					"app_uuid" => $request->input("app_uuid"),
					"dialplan_name" => "call_direction-outbound",
					"dialplan_order" => "22",
					"dialplan_continue" => "true",
					"dialplan_context" => $dialplan_context,
					"dialplan_enabled" => $dialplan_enabled,
					"dialplan_description" => $dialplan_description,
				];

				$dialplan = $this->dialplanRepository->create($dialplanData);

				$y = 0;

				$dialplanDetailData = [];

				$dialplanDetailData[] = [
					"dialplan_detail_tag" => 'condition',
					"dialplan_detail_type" => '${user_exists}',
					"dialplan_detail_data" => 'false',
					"dialplan_detail_order" => $y++ * 10,
					"dialplan_detail_group" => '0',
					"dialplan_detail_enabled" => 'true',
				];

				$dialplanDetailData[] = [
					"dialplan_detail_tag" => 'condition',
					"dialplan_detail_type" => '${call_direction}',
					"dialplan_detail_data" => '^$',
					"dialplan_detail_order" => $y++ * 10,
					"dialplan_detail_group" => '0',
					"dialplan_detail_enabled" => 'true',
				];

				$dialplanDetailData[] = [
					"dialplan_detail_tag" => 'condition',
					"dialplan_detail_type" => 'destination_number',
					"dialplan_detail_data" => $dialplan_expression,
					"dialplan_detail_order" => $y++ * 10,
					"dialplan_detail_group" => '0',
					"dialplan_detail_enabled" => 'true',
				];

				$dialplanDetailData[] = [
					"dialplan_detail_tag" => 'action',
					"dialplan_detail_type" => 'export',
					"dialplan_detail_data" => 'call_direction=outbound',
					"dialplan_detail_order" => $y++ * 10,
					"dialplan_detail_inline" => 'true',
					"dialplan_detail_group" => '0',
					"dialplan_detail_enabled" => 'true',
				];

				$this->dialplanDetailRepository->create($dialplan, $dialplanDetailData);

				$xml = $this->dialplanRepository->buildXML($dialplan);

				$this->dialplanRepository->update($dialplan->dialplan_uuid, ["dialplan_xml" => $xml]);

				//outbound route
				$dialplanData = [
					"domain_uuid" => Session::get("domain_uuid"),
					"app_uuid" => $request->input("app_uuid"),
					"dialplan_name" => $dialplan_name,
					"dialplan_order" => $dialplan_order,
					"dialplan_continue" => $dialplan_continue,
					"dialplan_context" => $dialplan_context,
					"dialplan_enabled" => $dialplan_enabled,
					"dialplan_description" => $dialplan_description,
				];

				$dialplan = $this->dialplanRepository->create($dialplanData);

				$y = 1;

				$dialplanDetailData = [];

				$dialplanDetailData[] = [
					'dialplan_detail_tag' => 'condition',
					'dialplan_detail_type' => '${user_exists}',
					'dialplan_detail_data' => 'false',
					'dialplan_detail_order' => $y * 10,
					'dialplan_detail_group' => '0',
					'dialplan_detail_enabled' => 'true',
				];

				if(!empty($toll_allow))
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'condition',
						'dialplan_detail_type' => '${toll_allow}',
						'dialplan_detail_data' => $toll_allow,
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				$dialplanDetailData[] = [
					'dialplan_detail_tag' => 'condition',
					'dialplan_detail_type' => 'destination_number',
					'dialplan_detail_data' => $dialplan_expression,
					'dialplan_detail_order' => $y++ * 10,
					'dialplan_detail_group' => '0',
					'dialplan_detail_enabled' => 'true',
				];

				if ($gateway_type != "transfer")
				{
					if (!empty($accountcode))
					{
						$dialplanDetailData[] = [
							'dialplan_detail_tag' => 'action',
							'dialplan_detail_type' => 'set',
							'dialplan_detail_data' => 'sip_h_accountcode=' . $accountcode,
							'dialplan_detail_order' => $y++ * 10,
							'dialplan_detail_group' => '0',
							'dialplan_detail_enabled' => 'false',
						];
					}
					else
					{
						$dialplanDetailData[] = [
							'dialplan_detail_tag' => 'action',
							'dialplan_detail_type' => 'set',
							'dialplan_detail_data' => 'sip_h_accountcode=${accountcode}',
							'dialplan_detail_order' => $y++ * 10,
							'dialplan_detail_group' => '0',
							'dialplan_detail_enabled' => 'false',
						];
					}
				}

				$dialplanDetailData[] = [
					'dialplan_detail_tag' => 'action',
					'dialplan_detail_type' => 'export',
					'dialplan_detail_data' => 'call_direction=outbound',
					'dialplan_detail_inline' => 'true',
					'dialplan_detail_order' => $y++ * 10,
					'dialplan_detail_group' => '0',
					'dialplan_detail_enabled' => 'true',
				];

				$dialplanDetailData[] = [
					'dialplan_detail_tag' => 'action',
					'dialplan_detail_type' => 'unset',
					'dialplan_detail_data' => 'call_timeout',
					'dialplan_detail_order' => $y++ * 10,
					'dialplan_detail_group' => '0',
					'dialplan_detail_enabled' => 'true',
				];

				if ($gateway_type != "transfer")
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => 'hangup_after_bridge=true',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];

					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => ($dialplan_expression == '(^911$|^933$)') ? 'effective_caller_id_name=${emergency_caller_id_name}' : 'effective_caller_id_name=${outbound_caller_id_name}',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];

					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => ($dialplan_expression == '(^911$|^933$)') ? 'effective_caller_id_number=${emergency_caller_id_number}' : 'effective_caller_id_number=${outbound_caller_id_number}',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];

					if ($dialplan_expression == '(^911$|^933$)')
					{
						$dialplanDetailData[] = [
							'dialplan_detail_tag' => 'action',
							'dialplan_detail_type' => 'lua',
							'dialplan_detail_data' => "email.lua \${email_to} \${email_from} '' 'Emergency Call' '\${sip_from_user}@\${domain_name} has called 911 emergency'",
							'dialplan_detail_order' => $y++ * 10,
							'dialplan_detail_group' => '0',
							'dialplan_detail_enabled' => 'false',
						];
					}

					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => 'inherit_codec=true',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];

					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => 'ignore_display_updates=true',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];

					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => 'callee_id_number=$1',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];

					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => 'continue_on_fail=1,2,3,6,18,21,27,28,31,34,38,41,42,44,58,88,111,403,501,602,607',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				if ($gateway_type == "enum" || $gateway_2_type == "enum")
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'enum',
						'dialplan_detail_data' => $prefix_number."$1 e164.org",
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				if (!empty($limit))
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'limit',
						'dialplan_detail_data' => "hash \${domain_name} outbound " . $limit . " !USER_BUSY",
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				if (!empty($outbound_prefix))
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => 'outbound_prefix=' . $outbound_prefix,
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				if ($pin_numbers_enable == "true")
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => 'pin_number=database',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];

					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'lua',
						'dialplan_detail_data' => 'pin_number.lua',
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				if (strlen($prefix_number) > 2)
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'set',
						'dialplan_detail_data' => 'provider_prefix=' . $prefix_number,
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				if ($gateway_type == "transfer")
				{
					$dialplan_detail_type = 'transfer';
				}
				else
				{
					$dialplan_detail_type = 'bridge';
				}

				$dialplanDetailData[] = [
					'dialplan_detail_tag' => 'action',
					'dialplan_detail_type' => $dialplan_detail_type,
					'dialplan_detail_data' => $bridge_data,
					'dialplan_detail_order' => $y++ * 10,
					'dialplan_detail_group' => '0',
					'dialplan_detail_enabled' => 'true',
				];

				if (!empty($bridge_2_data))
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'bridge',
						'dialplan_detail_data' => $bridge_2_data,
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				if (!empty($bridge_3_data))
				{
					$dialplanDetailData[] = [
						'dialplan_detail_tag' => 'action',
						'dialplan_detail_type' => 'bridge',
						'dialplan_detail_data' => $bridge_3_data,
						'dialplan_detail_order' => $y++ * 10,
						'dialplan_detail_group' => '0',
						'dialplan_detail_enabled' => 'true',
					];
				}

				$this->dialplanDetailRepository->create($dialplan, $dialplanDetailData);

				$xml = $this->dialplanRepository->buildXML($dialplan);

				$this->dialplanRepository->update($dialplan->dialplan_uuid, ["dialplan_xml" => $xml]);
			}
		}

		return redirect()->to(route("dialplans.index") . "?app_uuid=" . urlencode($request->input("app_uuid")));
	}

	private function buildDialplanDetail(string $tag, string $type, ?string $data, int $order, int $group = 0)
	{
		return [
			"dialplan_detail_tag" => $tag,
			"dialplan_detail_type" => $type,
			"dialplan_detail_data" => $data ?? '',
			"dialplan_detail_order" => $order,
			"dialplan_detail_group" => $group,
		];
	}

	private function filename_safe($filename)
	{
		//lower case
		$filename = strtolower($filename);

		//replace spaces with a '_'
		$filename = str_replace(" ", "_", $filename);

		//loop through string
		$result = '';

		for($i=0; $i<strlen($filename); $i++)
		{
			if(preg_match('([0-9]|[a-z]|_)', $filename[$i]))
			{
				$result .= $filename[$i];
			}
		}

		//return filename
		return $result;
	}
}
