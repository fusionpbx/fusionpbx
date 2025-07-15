<?php
namespace App\Http\Controllers;

use App\Http\Requests\BillingRequest;
use App\Models\Billing;
use App\Repositories\BillingRepository;

class BillingController extends Controller
{
	protected $billingRepository;

	public function __construct(BillingRepository $billingRepository)
	{
		$this->billingRepository = $billingRepository;
	}

	public function index()
	{
		return view('pages.billings.index');
	}

	public function create()
	{
		return view("pages.billings.form");
	}

	public function store(BillingRequest $request)
	{
		$data = $request->validated();

    	$data['domain_uuid'] = session('domain_uuid');

		$billing = $this->billingRepository->create($data);

		return redirect()->route("billings.edit", $billing->billing_uuid);
	}

    public function show(Billing $billing)
    {
        //
    }

	public function edit(Billing $billing)
	{
		return view("pages.billings.form", compact("billing"));
	}

	public function update(BillingRequest $request, Billing $billing)
	{
		$this->billingRepository->update($billing, $request->validated());

        return redirect()->route("billings.edit", $billing->billing_uuid);
	}

    public function destroy(Billing $billing)
    {
        $this->billingRepository->delete($billing);

        return redirect()->route('billings.index');
    }
}
