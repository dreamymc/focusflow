<?php

namespace App\Http\Controllers\Webhooks;

use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeController extends WebhookController
{
    // Cashier handles invoice.paid and customer.subscription.deleted automatically.
    // If we need custom behavior, we can override methods here.
}
