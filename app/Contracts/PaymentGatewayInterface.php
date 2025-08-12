<?php
namespace App\Contracts;

use App\Models\Billing;

interface PaymentGatewayInterface
{
    public function createPayment(Billing $billing, array $data);
}
