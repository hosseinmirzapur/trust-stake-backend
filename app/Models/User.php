<?php

namespace App\Models;

use Exception;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

/**
 * @property Wallet $wallet
 * @property Collection<Ticket> $tickets
 * @property string $role
 * @property string $password
 * @property int $id
 * @property string|null $email
 * @property bool|null $hasTwoFactor
 */
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, Notifiable, HasFactory;

    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';

    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin' && !is_null($this->password);
    }

    /**
     * @return HasOne
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return HasMany
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public static function generateReferralCode(): string
    {
        return Str::lower(Str::random(6));
    }

    /**
     * @return array
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws Exception
     */
    public function generate2FASecret(): array
    {
        if (!$this->email) {
            throw new Exception('User has no email specified.');
        }
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $this->update([
            'twoFactorKey' => $secret,
        ]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'TrustStake',
            $this->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ];
    }
}
