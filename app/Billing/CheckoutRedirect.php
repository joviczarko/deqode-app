<?php

namespace App\Billing;

readonly class CheckoutRedirect
{
    public function __construct(
        public string $token,
        public string $redirectUrl,
    ) {}
}
