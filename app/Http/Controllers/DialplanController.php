<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Dialplan;
use App\Models\Domain;
use App\Models\Fax;
use App\Http\Requests\DialplanRequest;
use App\Http\Requests\InboundDialplanRequest;
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
		$this->dialplanDetailRepository->create($dialplan->dialplan_uuid, $dialplanDetailData);
		$xml = $this->dialplanRepository->buildXML($dialplan);
		$this->dialplanRepository->update($dialplan->dialplan_uuid, ["dialplan_xml" => $xml]);

		return redirect()->to(route("dialplans.index") . "?app_uuid=" . urlencode($request->input("app_uuid")));
	}

	private function buildInboundRouteDetail(Dialplan $dialplan, ?Destination $destination, $request)
	{
		$dialplanDetails = [];

		$y = 0;

		// Helper to add a dialplan detail
		$addDetail = function(string $tag, string $type, ?string $data, int $order, int $group = 0) use (&$dialplanDetails, $dialplan)
		{
			return [
				"dialplan_detail_uuid" => Str::uuid(),
				"domain_uuid" => $dialplan->domain_uuid,
				"dialplan_uuid" => $dialplan->dialplan_uuid,
				"dialplan_detail_tag" => $tag,
				"dialplan_detail_type" => $type,
				"dialplan_detail_data" => $data ?? '',
				"dialplan_detail_order" => $order,
				"dialplan_detail_group" => $group,
			];
		};

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
			$dialplanDetails[] = $addDetail("condition", $condition_field_1, $condition_expression_1, $y * 10);
            $y++;
		}

		if($condition_field_2)
		{
			$dialplanDetails[] = $addDetail("condition", $condition_field_2, $condition_expression_2, $y * 10);
			$y++;
		}

		if($destination_accountcode)
		{
			$dialplanDetails[] = $addDetail("action", "set", "accountcode={$destination_accountcode}", $y * 10);
            $y++;
		}

		if($destination_carrier)
		{
			$dialplanDetails[] = $addDetail("action", "set", "carrier={$destination_carrier}", $y * 10);
            $y++;
            $dialplanDetails[] = $addDetail("action", "set", "carrier_uuid={$destination_carrier_uuid}", $y * 10);
            $y++;
		}

		if($limit)
		{
			$dialplanDetails[] = $addDetail("action", "limit", "hash {$domain_name} inbound {$limit} !USER_BUSY", $y * 10);
            $y++;
		}

		if($caller_id_outbound_prefix)
		{
			$dialplanDetails[] = $addDetail("action", "set", "effective_caller_id_number={$caller_id_outbound_prefix}\${caller_id_number}", $y * 10);
            $y++;
		}

		if(Str::isUuid($fax_uuid ?? null))
		{
			$fax = Fax::where("domain_uuid", $dialplan->domain_uuid)->where("fax_uuid", $fax_uuid)->first();

			if($fax)
			{
				$dialplanDetails[] = $addDetail("action", "set", "codec_string=PCMU,PCMA", $y * 10);
                $y++;
				$dialplanDetails[] = $addDetail("action", "set", "tone_detect_hits=1", $y * 10);
                $y++;
				$dialplanDetails[] = $addDetail("action", "set", "execute_on_tone_detect=transfer {$fax->fax_extension} XML " . Session::get("domain_name"), $y * 10);
                $y++;
				$dialplanDetails[] = $addDetail("action", "tone_detect", "fax 1100 r +5000", $y * 10);
                $y++;
				$dialplanDetails[] = $addDetail("action", "sleep", "3000", $y * 10);
                $y++;
				$dialplanDetails[] = $addDetail("action", "export", "codec_string=\${ep_codec_string}", $y * 10);
                $y++;
			}
		}

		if(in_array($action_application_1, ["ivr", "conference"]))
		// if(in_array($action_application_1, ["ivr", "conference"]) || in_array($action_application_2, ["ivr", "conference"]))  //TODO: remove?
		{
			$dialplanDetails[] = $addDetail("action", "answer", "", $y * 10);
            $y++;
		}

		// Add final actions
		if($action_application_1 && $action_data_1)
		{
			$dialplanDetails[] = $addDetail("action", $action_application_1, $action_data_1, $y * 10);
            $y++;
		}

  		//TODO: remove?
		// if($action_application_2 && $action_data_2)
		// {
		// 	$addDetail("action", $action_application_2, $action_data_2, $y * 10);
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
}
