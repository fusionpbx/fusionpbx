<?php
namespace App\Http\Controllers;

use App\Http\Requests\LcrRequest;
use App\Models\Lcr;
use App\Models\Gateway;
use App\Repositories\LcrRepository;
use Illuminate\Http\Request;

class LcrController extends Controller
{
	protected $lcrRepository;

	public function __construct(LcrRepository $lcrRepository)
	{
		$this->lcrRepository = $lcrRepository;
	}

	public function index()
	{
		return view('pages.lcr.index');
	}

	public function create(Request $request)
	{
		$carrier_uuid = $request->query("carrier_uuid");

		return view("pages.lcr.form", compact("carrier_uuid"));
	}

	public function store(LcrRequest $request)
	{
		$lcr = $this->lcrRepository->create($request->validated());

		return redirect()->route("lcr.edit", $lcr->lcr_uuid);
	}

    public function show(Lcr $lcr)
    {
        //
    }

	public function edit(Request $request, Lcr $lcr)
	{
		$carrier_uuid = $lcr->carrier_uuid;

		return view("pages.lcr.form", compact("lcr", "carrier_uuid"));
	}

	public function update(LcrRequest $request, Lcr $lcr)
	{
		$this->lcrRepository->update($lcr, $request->validated());

        return redirect()->route("lcr.edit", $lcr->lcr_uuid);
	}

    public function destroy(Lcr $lcr)
    {
        $this->lcrRepository->delete($lcr);

        return redirect()->route('lcr.index');
    }
}
