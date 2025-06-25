<?php
namespace App\Http\Controllers;

use App\Facades\Setting;
use App\Http\Requests\CallBlockRequest;
use App\Models\CallBlock;
use App\Models\Extension;
use App\Models\XmlCDR;
use App\Repositories\CallBlockRepository;
use Illuminate\Support\Facades\Session;

class CallBlockController extends Controller
{
	protected $callBlockRepository;

	public function __construct(CallBlockRepository $callBlockRepository)
	{
		$this->callBlockRepository = $callBlockRepository;
	}
	public function index()
	{
		return view('pages.callblocks.index');
	}

	public function create()
	{
		$extensions = Extension::all();

		$userExtension = Setting::getSetting("user", "extension");

		$xmlCDRQuery = XmlCDR::where("domain_uuid", Session::get("domain_uuid"))
    	->where("direction", "<>", "local");

		if(auth()->user()->hasPermission("call_block_all") && !empty($userExtension))
		{
			$extensionUUIDs = [];

			foreach($userExtension as $assigned_extension)
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
			->limit(Setting::getSetting("call_block", "recent_call_limit", "text"))
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
		$extensions = Extension::all();

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
}
