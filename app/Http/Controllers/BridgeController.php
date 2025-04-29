<?php
namespace App\Http\Controllers;

use App\Http\Requests\BridgeRequest;
use App\Models\Bridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BridgeController extends Controller
{
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

		$bridge = Bridge::create($data);

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
		$bridge->update($request->validated());

		return redirect()->route("bridges.edit", $bridge->bridge_uuid);
	}

    public function destroy(Bridge $bridge)
    {
        $bridge->delete();

        return redirect()->route('bridges.index');
    }
}
