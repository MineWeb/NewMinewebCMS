<?php
declare(strict_types=1);

namespace App\Service;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

final class OtpService
{
    private Google2FA $google2fa;

    public function __construct(?Google2FA $google2fa = null)
    {
        $this->google2fa = $google2fa ?? new Google2FA();
    }

    public function generateSetup(string $issuer, string $account): array
    {
        try {
            $secret = $this->google2fa->generateSecretKey();
        } catch (Throwable) {
            return ['status' => false];
        }

        $otpauth = $this->google2fa->getQRCodeUrl($issuer, $account, $secret);

        $svgOptions = new QROptions([
            'scale' => 6,
            'outputInterface' => QRMarkupSVG::class,
        ]);

        $svg = (new QRCode($svgOptions))->render($otpauth);

        return [
            'status' => true,
            'secret' => $secret,
            'otpauth' => $otpauth,
            'qrcode_data_uri' => $svg,
        ];
    }

    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        if ($secret === '' || !preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        try {
            return (bool)$this->google2fa->verifyKey($secret, $code, $window);
        } catch (Throwable) {
            return false;
        }
    }
}
