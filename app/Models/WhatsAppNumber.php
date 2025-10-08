<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WhatsAppNumber extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_numbers';

    protected $fillable = [
        'mobile',
        'session_id',
        'name',
        'description',
        'status',
        'is_active',
        'connected_at',
        'last_used_at',
        'usage_count',
        'error_count',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'connected_at' => 'datetime',
        'last_used_at' => 'datetime',
        'settings' => 'array',
        'usage_count' => 'integer',
        'error_count' => 'integer',
    ];

    /**
     * Get available WhatsApp numbers for OTP sending
     */
    public static function getAvailableNumbers()
    {
        return static::where('is_active', true)
            ->where('status', 'connected')
            ->orderBy('last_used_at')
            ->orderBy('usage_count')
            ->get();
    }

    /**
     * Get the least used available number
     */
    public static function getLeastUsedAvailableNumber()
    {
        return static::where('is_active', true)
            ->where('status', 'connected')
            ->orderBy('usage_count')
            ->orderBy('last_used_at')
            ->first();
    }

    /**
     * Mark number as used
     */
    public function markAsUsed(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Mark number with error
     */
    public function markError(): void
    {
        $this->increment('error_count');

        // If too many errors, mark as inactive
        if ($this->error_count >= 5) {
            $this->update([
                'status' => 'error',
                'is_active' => false,
            ]);
        }
    }

    /**
     * Mark number as connected
     */
    public function markAsConnected(): void
    {
        $this->update([
            'status' => 'connected',
            'connected_at' => now(),
            'error_count' => 0, // Reset error count on successful connection
        ]);
    }

    /**
     * Mark number as disconnected
     */
    public function markAsDisconnected(): void
    {
        $this->update([
            'status' => 'disconnected',
            'connected_at' => null,
        ]);
    }

    /**
     * Get status badge color for admin panel
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'connected' => 'success',
            'active' => 'warning',
            'inactive' => 'gray',
            'disconnected' => 'danger',
            'error' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Clear session cache when model is updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Clear WhatsApp session cache when number is updated
            if ($model->isDirty('session_id') || $model->isDirty('status')) {
                Cache::forget("whatsapp_session_{$model->session_id}_status");
                Cache::forget("whatsapp_session_{$model->session_id}_info");
            }
        });
    }
}
