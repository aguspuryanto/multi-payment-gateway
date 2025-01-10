<?php

namespace MultiPaymentGateway\Payflows;

interface PayflowInterface
{
    public function getConfig(): array;
    public function validateCurrency(string $currency): bool;
} 