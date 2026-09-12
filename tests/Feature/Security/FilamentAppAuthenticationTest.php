<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Support\Security\FilamentAppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAppAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_code_is_rendered_as_a_single_svg_data_uri(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $appAuthentication = FilamentAppAuthentication::make()->brandName('Somoy Bayanno');

        $qrCode = $appAuthentication->generateQrCodeDataUri('BKRB6VNLQAKDWJED');
        $decodedQrCode = base64_decode(substr($qrCode, strlen('data:image/svg+xml;base64,')), true);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qrCode);
        $this->assertIsString($decodedQrCode);
        $this->assertStringStartsWith('<?xml', $decodedQrCode);
    }
}
