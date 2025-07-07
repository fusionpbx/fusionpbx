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
        if(auth()->user()->hasPermission("call_block_all"))
        {
            $extensions = Extension::where('domain_uuid','=',Session::get('domain_uuid'));
        }
        else
        {
            $extensions = $this->extensionRepository->mine();
        }

		$xmlCDRQuery = XmlCDR::where("domain_uuid", Session::get("domain_uuid"))
            ->where("direction", "<>", "local")
            ->when(!auth()->user()->hasPermission("call_block_all"),
                   function ($query){
                       return $query->whereIn('extension_uuid', auth()->user()->extensions()->pluck('extension_uuid'));
                });

		if(auth()->user()->hasPermission("call_block_all") && !empty($extensions))
		{
			$extensionUUIDs = [];

			foreach($extensions as $assigned_extension)
			{
				$extensionUUIDs[] = $assigned_extension["extension_uuid"];
			}

			if(!empty($extensionUUIDs))
			{
				$xmlCDRQuery->where(function ($query) use ($extensionUUIDs)
				{
					foreach($extensionUUIDs as $extension_uuid)
					{
						$query->orWhere("extension_uuid", $extension_uuid);
					}
				});
			}
		}

		$xmlCDR = $xmlCDRQuery
			->orderBy("start_stamp", "desc")
			->limit(Setting::getSetting("call_block", "recent_call_limit", "text") ?? 50)
			->get();

		return view("pages.callblocks.form", compact("extensions", "xmlCDR"));
	}

	public function store(CallBlockRequest $request)
	{
		$callblock = $this->callBlockRepository->create($request->validated());

		return redirect()->route("callblocks.edit", $callblock->callblock_uuid);
	}

    public function show(CallBlock $callblock)
    {
        //
    }

	public function edit(CallBlock $callblock)
	{
        if(auth()->user()->hasPermission("call_block_all"))
        {
            $extensions = Extension::where('domain_uuid','=',Session::get('domain_uuid'));
        }
        else
        {
            $extensions = $this->extensionRepository->mine();
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
			$callblock = $this->callBlockRepository->create($request->validated());

			$xmlCDR = XmlCDR::whereIn("xml_cdr_uuid", $uuids)->get();

			$domainCountryCode = Setting::getSetting('domain', 'country_code', 'numeric');

			$userExtension = Setting::getSetting('user', 'extension');

			foreach($xmlCDR as $x)
			{
				$callblockData = [];

				if(auth()->user()->hasPermission("call_block_all"))
				{
					$callblockData['call_block_uuid'] = Str::uuid();
					$callblockData['domain_uuid'] = Session::get('domain_uuid');
					$callblockData['call_block_direction'] = $callblock->call_block_direction;

					if(Str::isUuid($callblock->extension_uuid))
					{
						$callblockData['extension_uuid'] = $callblock->extension_uuid;
					}

					if($callblock->call_block_direction == 'inbound')
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

						$callblockData['call_block_name'] = '';
						$callblockData['call_block_description'] = trim($x->caller_id_name);
						$callblockData['call_block_country_code'] = trim($domainCountryCode ?? '');
						$callblockData['call_block_number'] = $call_block_number;
					}

					if($callblock->call_block_direction == 'outbound')
					{
						$callblockData['call_block_number'] = trim($x->caller_destination);
					}

					$callblockData['call_block_count'] = 0;
					$callblockData['call_block_app'] = $callblock->call_block_app;
					$callblockData['call_block_data'] = $callblock->call_block_data;
					$callblockData['call_block_enabled'] = 'true';
					$callblockData['date_added'] = time();
				}
				else
				{
					if(is_array($userExtension))
					{
						foreach($userExtension as $field)
						{
							if(Str::isUuid($field['extension_uuid']))
							{
								$callblockData['call_block_uuid'] = Str::uuid();
								$callblockData['domain_uuid'] = Session::get('domain_uuid');
								$callblockData['call_block_direction'] = $callblock->call_block_direction;
								$callblockData['extension_uuid'] = $field['extension_uuid'];

								if ($callblock->call_block_direction == 'inbound')
								{
									//remove e.164 and country code
									$call_block_number = str_replace("+".trim($domainCountryCode), "", trim($x->caller_id_number));

									//build the array
									$callblockData['call_block_name'] = '';
									$callblockData['call_block_description'] = trim($x->caller_id_name);
									$callblockData['call_block_number'] = $call_block_number;
								}

								if($callblock->call_block_direction == 'outbound')
								{
									$callblockData['call_block_number'] = trim($x->caller_destination);
								}

								$callblockData['call_block_count'] = 0;
								$callblockData['call_block_app'] = $callblock->call_block_app;
								$callblockData['call_block_data'] = $callblock->call_block_data;
								$callblockData['call_block_enabled'] = 'true';
								$callblockData['date_added'] = time();
							}
						}
					}
				}

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
