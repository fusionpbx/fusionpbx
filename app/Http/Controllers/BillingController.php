<?php
namespace App\Http\Controllers;

use App\Http\Requests\BillingRequest;
use App\Models\Billing;
use App\Models\Domain;
use App\Repositories\BillingRepository;
use Illuminate\Support\Facades\Session;

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
		$billings = Billing::parentProfiles();
		$domains = Domain::all();

		return view("pages.billings.form", compact("billings", "domains"));
	}

	public function store(BillingRequest $request)
	{
		$data = $request->validated();

    	$data['domain_uuid'] = Session::get('domain_uuid');

		$billing = $this->billingRepository->create($data);

		return redirect()->route("billings.edit", $billing->billing_uuid);
	}

    public function show(Billing $billing)
    {
        //
    }

	public function edit(Billing $billing)
	{
		$billings = Billing::parentProfiles($billing->billing_uuid);
		$domains = Domain::all();

		return view("pages.billings.form", compact("billing", "billings", "domains"));
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

    public function analysis()
    {
        //
    }

    public function pricing()
    {
        return view("pages.billings.pricing");
    }
}
