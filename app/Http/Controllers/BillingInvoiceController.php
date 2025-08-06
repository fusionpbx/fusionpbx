<?php
namespace App\Http\Controllers;

use App\Facades\Setting;
use App\Http\Requests\BillingInvoiceRequest;
use App\Models\BillingInvoice;
use App\Repositories\BillingRepository;
use App\Repositories\BillingInvoiceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class BillingInvoiceController extends Controller
{
	protected $billingRepository;
	protected $billingInvoiceRepository;

	public function __construct(BillingRepository $billingRepository, BillingInvoiceRepository $billingInvoiceRepository)
	{
		$this->billingRepository = $billingRepository;
		$this->billingInvoiceRepository = $billingInvoiceRepository;
	}

	public function process(BillingInvoiceRequest $request, BillingInvoice $billingInvoice)
	{
		$billingInvoiceData = $request->validated();

		$billing = $billingInvoice->billing;

		if(($billingInvoice->settled == 1) || ($billingInvoice->settled == -1))
		{
			$percentageComission = Setting::getSetting('billing', $billingInvoice->plugin_used . '_percentage_comission', 'numeric');
			$fixedComission = Setting::getSetting('billing', $billingInvoice->plugin_used . '_fixed_comission', 'numeric');
			$currency = (strlen(Setting::getSetting('billing', $billingInvoice->plugin_used . '_fixed_comission_currency', 'text')) ? Setting::getSetting('billing', $billingInvoice->plugin_used . '_fixed_comission_currency', 'text') : (strlen(Setting::getSetting('billing', 'currency', 'text')) ? Setting::getSetting('billing', 'currency', 'text') : 'USD'));

			$increment = $billingInvoice->settled * $billingInvoice->amount;

			if($billingInvoice->settled == -1)
			{
				if($percentageComission > 0)
				{
					$increment *= (1 - $percentageComission / 100);
				}

				if($fixedComission > 0)
				{
					$increment += $billingInvoice->settled * currency_convert($fixedComission, $billing->currency, $currency);
				}
			}

			$billingData = [
				"balance" => $billing->balance + $increment,
				"old_balance" => $billing->old_balance + $increment,
			];
		}

		$this->billingInvoiceRepository->update($billingInvoice, $billingInvoiceData);
		$this->billingRepository->update($billing, $billingData);

		return redirect()->back()->with('success', __('Operation completed'));
	}
}
