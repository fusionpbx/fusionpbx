<?php
namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Billing;
use App\Models\BillingFixedCharge;
use App\Repositories\BillingInvoiceRepository;
use Illuminate\Support\Facades\Auth;

class OfflineGateway implements PaymentGatewayInterface
{
	protected $billingInvoiceRepository;

	public function __construct(BillingInvoiceRepository $billingInvoiceRepository)
	{
		$this->billingInvoiceRepository = $billingInvoiceRepository;
	}

    public function createPayment(Billing $billing, array $data)
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

        $tax = $data["amount"] * ($total_tax / 100);

		$billingInvoiceData = [
			"billing_uuid" => $billing->billing_uuid,
			"payer_uuid" => Auth::user()->user_uuid,
			"billing_payment_date" => date("Y-m-d H:i:s"),
			"settled" => 0,
			"amount" => $data["amount"],
			"debt" => $billing->balance,
			"plugin_used" => "Offline",
			"domain_uuid" => $billing->domain_uuid,
			"tax" => $tax,
		];

		$this->billingInvoiceRepository->create($billingInvoiceData);

        //TODO send email
    }
}
