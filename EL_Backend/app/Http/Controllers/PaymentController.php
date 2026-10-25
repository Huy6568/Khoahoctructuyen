<?php

namespace App\Http\Controllers;

use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {
    }


}
