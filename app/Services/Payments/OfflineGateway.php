<?php
namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Billing;
use App\Repositories\BillingInvoiceRepository;
use App\Services\Payments\Concerns\CreateBillingInvoice;

class OfflineGateway implements PaymentGatewayInterface
{
	use CreateBillingInvoice;

	private $billingInvoiceRepository;

    public function __construct(BillingInvoiceRepository $billingInvoiceRepository)
    {
        $this->billingInvoiceRepository = $billingInvoiceRepository;
    }

    public function createPayment(Billing $billing, array $data)
    {
        $this->createBillingInvoice($billing, "Offline", 0, $data["amount"]);

        //TODO send email
    }
}
