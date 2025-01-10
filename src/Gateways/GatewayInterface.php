<?php

namespace MultiPaymentGateway\Gateways;

interface GatewayInterface
{
    public function processPayment(float $amount, string $currency): array;
    public function getStatus(string $transactionId): array;
} 