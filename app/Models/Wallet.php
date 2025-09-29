<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|float $spendable_balance
 * @property int $id
 * @property string $currency
 * @property int|float $balance
 * @method Builder whereCurrency(string $currency)
 */
class Wallet extends Model
{
    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return int|float
     */
    public function getSpendableBalanceAttribute(): int|float
    {
        $pendingWalletTransactionsAmount = Transaction::query()
            ->where('wallet_id', $this->id)
            ->where('status', Transaction::STATUS_PENDING)
            ->with('wallet', function ($query) {
                $query->whereCurrency('USDT');
            })
            ->where('type', Transaction::TYPE_WITHDRAW)
            ->sum('amount');

        $usdtBalance = $this->whereCurrency('USDT')->sum('balance');

        return $usdtBalance - $pendingWalletTransactionsAmount;
    }
}
