<?php
namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;

class PaymentGatewayFactory
{
    public static function make(string $provider): PaymentGatewayInterface
    {
        $paymentgateways = config('paymentgateways');

        if(!isset($paymentgateways[$provider]))
        {
            throw new \Exception("{$provider} not found");
        }

        $class = $paymentgateways[$provider]["class"];

        $instance = app($class);

        if(!$instance instanceof PaymentGatewayInterface)
        {
            throw new \Exception("Class {$class} must implement PaymentGatewayInterface");
        }

        return $instance;
    }
}

