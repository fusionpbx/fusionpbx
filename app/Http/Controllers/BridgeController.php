<?php
namespace App\Http\Controllers;

use App\Http\Requests\BridgeRequest;
use App\Models\Bridge;
use App\Repositories\BridgeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BridgeController extends Controller
{
	protected $bridgeRepository;

	public function __construct(BridgeRepository $bridgeRepository)
	{
		$this->bridgeRepository = $bridgeRepository;
	}
	public function index()
	{
		return view('pages.bridges.index');
	}

	public function create()
	{
		return view("pages.bridges.form");
	}

	public function store(BridgeRequest $request)
	{
		$data = $request->validated();

    	$data['domain_uuid'] = session('domain_uuid');

		$bridge = $this->bridgeRepository->create($data);

		return redirect()->route("bridges.edit", $bridge->bridge_uuid);
	}

    public function show(Bridge $bridge)
    {
        //
    }

	public function edit(Bridge $bridge)
	{
		return view("pages.bridges.form", compact("bridge"));
	}

	public function update(BridgeRequest $request, Bridge $bridge)
	{
		$this->bridgeRepository->update($bridge, $request->validated());

        return redirect()->route("bridges.edit", $bridge->bridge_uuid);
	}

    public function destroy(Bridge $bridge)
    {
        $this->bridgeRepository->delete($bridge);

        return redirect()->route('bridges.index');
    }
}
