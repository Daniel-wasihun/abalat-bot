<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Helpers\Response;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    /**
     * Enable 2FA and generate secret / QR Code
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        if ($user->two_factor_confirmed) {
            return response()->json(['message' => '2FA is already enabled and confirmed.'], 400);
        }

        $google2fa = new Google2FA();
        $secret = $user->two_factor_secret ?: $google2fa->generateSecretKey();

        $recoveryCodes = collect(range(1, 8))->map(function () {
            return Str::random(10) . '-' . Str::random(10);
        })->toArray();

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed' => false
        ]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(256),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return response()->json([
            'status' => 'success',
            'data' => [
                'secret' => $secret,
                'qr_code_svg' => base64_encode($qrCodeSvg),
                'recovery_codes' => $recoveryCodes
            ]
        ]);
    }

    /**
     * Verify OTP and confirm 2FA setup
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = $request->user();
        $google2fa = new Google2FA();

        if (!$user->two_factor_secret) {
            return response()->json(['message' => '2FA has not been initialized.'], 400);
        }

        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if ($valid) {
            $user->update([
                'two_factor_confirmed' => true
            ]);
            return Response::_200('2FA has been successfully enabled and verified.');
        }

        return response()->json(['message' => 'Invalid OTP code.'], 400);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $user = $request->user();

        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed' => false,
            'two_factor_recovery_codes' => null,
        ]);

        return Response::_200('2FA has been disabled.');
    }
}
