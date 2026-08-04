<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\BackMessage;

class LocalizationTest extends TestCase {
    public function test_amharic_localization() {
        // Test direct helper
        \Illuminate\Support\Facades\App::setLocale('am');
        $msg = BackMessage::get('validation_error');
        $this->assertEquals('ያስገቡት ውሂብ ስህተት አለበት ወይም ያልተሟላ ነው።', $msg);
    }

    public function test_amharic_validation_via_api() {
        $response = $this->withHeaders([
            'lang' => 'am',
            'Accept' => 'application/json'
        ])->postJson('/api/login', [
            'email' => 'wrong',
            'password' => 'wrong'
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'ያስገቡት ውሂብ ስህተት አለበት ወይም ያልተሟላ ነው።']);
    }

    public function test_permission_helper_refactor() {
        $user = User::factory()->create();
        // Give super admin role/permissions
        // Just checking if code runs without "Class not found" error
        $this->actingAs($user, 'api');

        // This route uses PermissionHelper::users()->view()
        // If the refactor is broken, this will invalid argument or class not found error
        // But wait, the middleware is set in __construct. If class missing, it fails at load time.

        $response = $this->getJson('/api/system/users');
        // We expect 403 or 200, but NOT 500 (Class not found)

        $this->assertNotEquals(500, $response->status());
    }
    public function test_permission_options_localization() {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->withHeaders([
                'lang' => 'am',
                'Accept' => 'application/json'
            ])
            ->get('/api/system/permissions/options');

        $response->assertStatus(200)
            ->assertJsonPath('modules.books', 'መጽሐፍት')
            ->assertJsonPath('actions.create', 'መፍጠር');
    }
    public function test_type_localization() {
        // Direct test
        $map = \App\Constants\Type::labelMap();
        $this->assertEquals('ተማሪ', $map['student']['am']);
    }
}
