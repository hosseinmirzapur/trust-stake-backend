<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Random\RandomException;

class Transaction extends Model
{
    const PAYMENT_GATEWAY = 'paymentGateway';
    const WALLET = 'wallet';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_DEPOSIT = 'deposit';

    const TYPE_PAYMENT = 'payment';

    protected $guarded = [];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(User::class, Wallet::class, 'id', 'id', 'wallet_id', 'user_id');
    }

    /**
     * @return string
     * @throws RandomException
     */
    public static function generateManualHash(): string
    {
        return 'TRUSTSTAKE-' .  Hash::make(
            random_int(100000, 999999)
        );
    }
}
