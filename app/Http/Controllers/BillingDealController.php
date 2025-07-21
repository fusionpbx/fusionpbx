<?php
namespace App\Http\Controllers;

use App\Http\Requests\BillingDealRequest;
use App\Models\BillingDeal;
use App\Repositories\BillingDealRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class BillingDealController extends Controller
{
	protected $billingDealRepository;

	public function __construct(BillingDealRepository $billingDealRepository)
	{
		$this->billingDealRepository = $billingDealRepository;
	}

	public function index()
	{
		return view("pages.billings.deals.index");
	}

	public function create()
	{
		return view("pages.billings.deals.form");
	}

	public function store(BillingDealRequest $request)
	{
		$data = $request->validated();

    	$data['domain_uuid'] = Session::get('domain_uuid');

		$billingDeal = $this->billingDealRepository->create($data);

		return redirect()->route("billings.deals.edit", $billingDeal->billing_deal_uuid);
	}

    public function show(BillingDeal $billingDeal)
    {
        //
    }

	public function edit(BillingDeal $billingDeal)
	{
		return view("pages.billings.deals.form", compact("billingDeal"));
	}

	public function update(BillingDealRequest $request, BillingDeal $billingDeal)
	{
		$this->billingDealRepository->update($billingDeal, $request->validated());

        return redirect()->route("billings.deals.edit", $billingDeal->billing_deal_uuid);
	}

    public function destroy(BillingDeal $billingDeal)
    {
        $this->billingDealRepository->delete($billingDeal);

        return redirect()->route('billings.deals.index');
    }
}
