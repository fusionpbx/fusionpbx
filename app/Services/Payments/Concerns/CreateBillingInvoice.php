<?php
namespace App\Services\Payments\Concerns;

use App\Models\Billing;
use App\Repositories\BillingInvoiceRepository;
use Illuminate\Support\Facades\Auth;

trait CreateBillingInvoice
{
    public function createBillingInvoice(Billing $billing, string $pluginUsed, int $settled, float $amount): void
	{
        $billingFixedCharges = $billing->billingFixedCharges()
            ->where("currency", "%")
            ->where("times", ">", 0)
            ->get();

        $total_tax = 0;

	    foreach($billingFixedCharges as $billingFixedCharge)
		{
            $total_tax += $billingFixedCharge->value;
        }

        $tax = $amount * ($total_tax / 100);

        $billingInvoiceData = [
            "billing_uuid" => $billing->billing_uuid,
            "payer_uuid" => Auth::user()->user_uuid,
            "billing_payment_date" => now(),
            "settled" => $settled,
            "amount" => $amount,
            "debt" => $billing->balance,
            "plugin_used" => $pluginUsed,
            "domain_uuid" => $billing->domain_uuid,
            "tax" => $tax,
        ];

        $this->billingInvoiceRepository->create($billingInvoiceData);
    }
}
