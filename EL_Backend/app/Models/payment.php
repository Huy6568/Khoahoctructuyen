<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'vnpay_transaction_id',
        'amount',
        'status',
        'payment_method',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];
}
