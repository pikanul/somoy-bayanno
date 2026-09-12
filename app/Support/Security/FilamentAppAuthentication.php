<?php

namespace App\Support\Security;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use SensitiveParameter;

class FilamentAppAuthentication extends AppAuthentication
{
    public function generateQrCodeDataUri(#[SensitiveParameter] string $secret): string
    {
        $qrCode = $this->google2FA->getQRCodeInline(
            $this->getBrandName(),
            $this->getHolderName(Filament::auth()->user()),
            $secret,
        );

        if (str_starts_with($qrCode, 'data:image/')) {
            return $qrCode;
        }

        return 'data:image/svg+xml;base64,'.base64_encode($qrCode);
    }
}
