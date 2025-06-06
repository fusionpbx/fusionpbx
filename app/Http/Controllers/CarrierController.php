<?php
namespace App\Http\Controllers;

use App\Http\Requests\CarrierRequest;
use App\Models\Carrier;
use App\Models\Gateway;
use App\Repositories\CarrierRepository;

class CarrierController extends Controller
{
	protected $carrierRepository;

	public function __construct(CarrierRepository $carrierRepository)
	{
		$this->carrierRepository = $carrierRepository;
	}
	public function index()
	{
		return view('pages.carriers.index');
	}

	public function create()
	{
		$gateways = Gateway::all();

		return view("pages.carriers.form", compact("gateways"));
	}

	public function store(CarrierRequest $request)
	{
		$carrier = $this->carrierRepository->create($request->validated());

		return redirect()->route("carriers.edit", $carrier->carrier_uuid);
	}

    public function show(Carrier $carrier)
    {
        //
    }

	public function edit(Carrier $carrier)
	{
		$gateways = Gateway::all();

		return view("pages.carriers.form", compact("carrier", "gateways"));
	}

	public function update(CarrierRequest $request, Carrier $carrier)
	{
		$this->carrierRepository->update($carrier, $request->validated());

        return redirect()->route("carriers.edit", $carrier->carrier_uuid);
	}

    public function destroy(Carrier $carrier)
    {
        $this->carrierRepository->delete($carrier);

        return redirect()->route('carriers.index');
    }
}
