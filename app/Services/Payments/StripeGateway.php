<?php
namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Billing;
use App\Repositories\BillingInvoiceRepository;
use App\Services\Payments\Concerns\CreateBillingInvoice;

class StripeGateway implements PaymentGatewayInterface
{
    use CreateBillingInvoice;

	private $billingInvoiceRepository;
    private $key;
    private $secret;

    public function __construct(BillingInvoiceRepository $billingInvoiceRepository)
    {
        $this->billingInvoiceRepository = $billingInvoiceRepository;

		$this->key = env('PAYMENT_STRIPE_KEY');
        $this->secret = env('PAYMENT_STRIPE_SECRET');
    }

    public function createPayment(Billing $billing, array $data)
    {
        // TODO connect to stripe
        if($billing->credit_type == "postpaid" && $billing->balance < 0 && $billing->force_postpaid_full_payment == 'true')
        {
            // if postpaid and a debt, suggest to pay it all
            $credit = abs($billing->balance);
        }
        else
        {
            $credit = $data['amount'];
        }

        // $amount_in_cents = round($credit * (100 + $total_tax));

        $stripe_response = true;

        if($stripe_response)
        {
            $this->createBillingInvoice($billing, "Stripe", 1, $data["amount"]);
        }

        //TODO send email
    }
}
