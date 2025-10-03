<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

class DashboardService
{
    public function modifyProfile(array $data): array
    {
        if ($data['profile_image']) {
            $path = 'profile_images/';
            /** @var UploadedFile $file */
            $file = $data['profile_image'];
            $fileName = $this->generateFileName($file);
            Storage::putFileAs($path, $file, $fileName);

            $data['profile_image'] = $path . '/' . $fileName;
        }

        /** @var User $user */
        $user = auth()->user();
        $user->update($data);

        return [
            'user' => $user,
        ];
    }

    public function sendEmailVerificationCode(): array
    {
        /** @var User $user */
        $user = auth()->user();
        abort_if($user->hasVerifiedEmail(), 400, 'User has already been verified.');

        return []; // todo
    }

    public function verifyEmail(array $data): array
    {
        /** @var User $user */
        $user = auth()->user();
        abort_if($user->hasVerifiedEmail(), 400, 'User has already been verified.');

        return []; // todo
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     * @throws Exception
     */
    public function activate2FA(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $twoFA = $user->generate2FASecret();

        if ($user->twoFactorKey !== $twoFA) {
            throw new Exception('Generating 2FA Secret failed.');
        }

        return [
            'secret' => $twoFA['secret'],
            'qrUrl' => $twoFA['qrUrl'],
        ];
    }

    public function verify2FA(array $data): array
    {

        return [];
    }

    public function referral(): array
    {
        return [];
    }

    private function generateFileName(UploadedFile $file): string
    {
        return Str::random(20) . '.' . $file->getClientOriginalExtension();
    }
}