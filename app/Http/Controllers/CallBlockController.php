<?php
namespace App\Http\Controllers;

use App\Facades\Setting;
use App\Http\Requests\CallBlockRequest;
use App\Models\CallBlock;
use App\Models\Dialplan;
use App\Models\Extension;
use App\Models\XmlCDR;
use App\Repositories\CallBlockRepository;
use App\Repositories\DialplanRepository;
use App\Repositories\ExtensionRepository;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CallBlockController extends Controller
{
	protected $callBlockRepository;
	protected $dialplanRepository;
    protected $extensionRepository;

	public function __construct(CallBlockRepository $callBlockRepository, DialplanRepository $dialplanRepository, ExtensionRepository $extensionRepository)
	{
		$this->callBlockRepository = $callBlockRepository;
		$this->dialplanRepository = $dialplanRepository;
        $this->extensionRepository = $extensionRepository;
	}

	public function index()
	{
		return view('pages.callblocks.index');
	}

	public function create()
	{
        $xmlCDRQuery = XmlCDR::where("domain_uuid", Session::get("domain_uuid"))
                        ->where("direction", "<>", "local");
        if(!auth()->user()->hasPermission("call_block_all"))
        {
            $extensions = $this->extensionRepository->mine();
        }
        else
        {
            $extensions = Extension::where('domain_uuid','=',Session::get('domain_uuid'))->where("enabled", "true")->orderBy("extension")->get();
        }

        if ($extensions->count() == 0)
        {
            $xmlCDR = collect([]);
        }
        else
        {
            $xmlCDR = $xmlCDRQuery->when(!auth()->user()->hasPermission("call_block_all"),
                   function ($query){
			return $query->whereIn('extension_uuid', $this->extensionRepository->mine()->pluck('extension_uuid'));
		})
                ->orderBy("start_stamp", "desc")
                ->limit(Setting::getSetting("call_block", "recent_call_limit", "text") ?? 50)
                ->get();
        }

		return view("pages.callblocks.form", compact("extensions", "xmlCDR"));
	}

	public function store(CallBlockRequest $request)
	{
		$callblock = $this->callBlockRepository->create($request->validated());

		return redirect()->route("callblocks.edit", $callblock->call_block_uuid);
	}

    public function show(CallBlock $callblock)
    {
        //
    }

	public function edit(CallBlock $callblock)
	{
        if(!auth()->user()->hasPermission("call_block_all"))
        {
            $extensions = $this->extensionRepository->mine();
        }
        else
        {
            $extensions = Extension::where('domain_uuid','=',Session::get('domain_uuid'))->where("enabled", "true")->orderBy("extension")->get();
        }

		return view("pages.callblocks.form", compact("callblock", "extensions"));
	}

	public function update(CallBlockRequest $request, CallBlock $callblock)
	{
		$this->callBlockRepository->update($callblock, $request->validated());

        return redirect()->route("callblocks.edit", $callblock->call_block_uuid);
	}

    public function destroy(CallBlock $callblock)
    {
        $this->callBlockRepository->delete($callblock);

        return redirect()->route('callblocks.index');
    }

	public function block(CallBlockRequest $request)
	{
		$uuids = $request->input("selected_xml_cdrs", []);

		if(!empty($uuids))
		{
			$xmlCDR = XmlCDR::whereIn("xml_cdr_uuid", $uuids)->get();
			$domainCountryCode = Setting::getSetting('domain', 'country_code', 'numeric');

			foreach($xmlCDR as $x)
			{
				$callblockData = [];
                $callblockData['domain_uuid'] = Session::get('domain_uuid');
                $callblockData['call_block_direction'] = $request->input("call_block_direction");
                $insert = false;

				if(auth()->user()->hasPermission("call_block_all"))
				{
					if(Str::isUuid($xmlCDR->extension_uuid))
					{
						$callblockData['extension_uuid'] = $xmlCDR->extension_uuid;
					}

					if($request->input("call_block_direction") == 'inbound')
					{
						//remove e.164 and country code
						if(substr($x->caller_id_number, 0, 1) == "+")
						{
							//format e.164
							$call_block_number = str_replace("+".trim($domainCountryCode), "", trim($x->caller_id_number));
						}
						else
						{
							//remove the country code if its the first in the string
							$call_block_number = ltrim(trim($x->caller_id_number), $domainCountryCode ?? '');
						}

						// TODO: work on the name later
						$callblockData['call_block_name'] = Str::ucfirst($request->input("call_block_direction")).' '.$x->caller_id_number;
						$callblockData['call_block_description'] = trim($x->caller_id_name);
						$callblockData['call_block_country_code'] = trim($domainCountryCode ?? '');
						$callblockData['call_block_number'] = $call_block_number;
					}
					else {
                        // Outbound call
                        $callblockData['call_block_number'] = trim($x->caller_destination);
                        $callblockData['call_block_name'] = Str::ucfirst($request->input("call_block_direction")).' '.$x->caller_id_number;
						$callblockData['call_block_description'] = trim($x->caller_id_name);
					}

					$callblockData['call_block_count'] = 0;
					$callblockData['call_block_enabled'] = 'true';
					$callblockData['date_added'] = time();
                    $insert = true;
				}
				else
				{
                    // TODO: This assumes there will be always a value in extension_uuid
                    $userExtensions = $this->extensionRepository->mine();
                    $currentExtensionUuid = $x->extension_uuid;
                    if ($this->extensionRepository->belongsToUser($currentExtensionUuid, auth()->user()->user_uuid()))
                    {
                        // The extension bellongs to the current user

                        $callblockData['extension_uuid'] = $currentExtensionUuid;
                        if ($callblock->call_block_direction == 'inbound')
                        {
                            //remove e.164 and country code
                            $call_block_number = str_replace("+".trim($domainCountryCode), "", trim($x->caller_id_number));

                            //build the array
                            $callblockData['call_block_name'] = '';
                            $callblockData['call_block_description'] = trim($x->caller_id_name);
                            $callblockData['call_block_number'] = $call_block_number;
                        }
                        else
                        {
                            $callblockData['call_block_number'] = trim($x->caller_destination);
                        }

                        $callblockData['call_block_count'] = 0;
                        $callblockData['call_block_app'] = $callblock->call_block_app;
                        $callblockData['call_block_data'] = $callblock->call_block_data;
                        $callblockData['call_block_enabled'] = 'true';
                        $callblockData['date_added'] = time();
                        $insert = true;
					}
				}

				if ($insert)
                    $this->callBlockRepository->create($callblockData);

            }
            $dialplans = Dialplan::where("domain_uuid", Session::get("domain_uuid"))
            ->where("app_uuid", "9ed63276-e085-4897-839c-4f2e36d92d6c")
            ->where("dialplan_enabled", "<>", "true")
            ->get();

			foreach ($dialplans as $dialplan)
			{
				$this->dialplanRepository->update($dialplan, ['dialplan_enabled' => 'true']);
			}
        }

        return redirect()->route('callblocks.index');
	}
}
