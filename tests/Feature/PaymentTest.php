<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SenbetMembership;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_instantiate_payment_service()
    {
        $service = app(PaymentService::class);
        $this->assertNotNull($service);
    }
}
