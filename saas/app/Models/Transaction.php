<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'plan',
        'currency',
        'cashfree_order_id',
        'cashfree_payment_id',
        'cashfree_payment_status',
        'cashfree_raw_response',
        'cashfree_link_id',
        'cashfree_link_url',
        'website_id',
        'cashfree_payment_session_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
