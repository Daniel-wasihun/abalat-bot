<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\SchoolDonation;
use App\Models\MemberCredit;
use App\Models\SenbetMembership;
use App\Models\User;
use App\Models\UserInfo;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $this->admin = User::factory()->create(['name' => 'Admin User']);
        
        $this->user = User::factory()->create(['name' => 'Abebe Bikila']);
        $this->user->info()->update(['registration_id' => 'DBSS-000100']);

        SenbetMembership::create([
            'user_id'          => $this->user->id,
            'work_status'      => 'student',
            'senbet_class'     => '7',
            'date_of_birth'    => '2010-01-01',
            'registration_date'=> '2023-01-01',
        ]);
    }

    public function test_single_payment_with_gift_surplus_creates_donation_and_no_credit()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/payments', [
            'user_id'        => $this->user->id,
            'for_year'       => 2017,
            'for_month'      => 1,
            'amount_paid'    => 500.00, // covers total due, leaves surplus
            'gift_surplus'   => true,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('school_donations', [
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('member_credits', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_overpayment_without_gift_creates_credit_and_auto_applies()
    {
        // Member pays 1000 ETB for month 1 (required ~220), surplus becomes member credit
        $response = $this->actingAs($this->admin)->postJson('/api/payments', [
            'user_id'        => $this->user->id,
            'for_year'       => 2017,
            'for_month'      => 1,
            'amount_paid'    => 1000.00,
            'gift_surplus'   => false, // Normal overpayment -> Member Credit
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201);

        // Verify member credit record was created
        $this->assertDatabaseHas('member_credits', [
            'user_id' => $this->user->id,
        ]);

        // When loading month 2, credit should automatically pay off month 2 obligation
        $resMonth2 = $this->actingAs($this->admin)->getJson('/api/payments?year=2017&month=2');
        $resMonth2->assertStatus(200);

        $userRow = collect($resMonth2->json('data.users'))->firstWhere('id', $this->user->id);
        $this->assertEquals('paid', $userRow['status']);
    }

    public function test_bulk_payment_gift_surplus()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/payments/bulk', [
            'user_id'        => $this->user->id,
            'from_year'      => 2017,
            'from_month'     => 1,
            'num_months'     => 3,
            'amount_paid'    => 1000.00,
            'gift_surplus'   => true,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('school_donations', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_get_statistics_returns_separate_member_and_school_metrics()
    {
        // Record a gift payment with surplus
        $this->actingAs($this->admin)->postJson('/api/payments', [
            'user_id'      => $this->user->id,
            'for_year'     => 2017,
            'for_month'    => 1,
            'amount_paid'  => 500.00,
            'gift_surplus' => true,
        ]);

        $donationAmount = (float) SchoolDonation::where('user_id', $this->user->id)->sum('amount');

        $response = $this->actingAs($this->admin)->getJson('/api/payments/statistics?year=2017&month=1');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total_donations'      => $donationAmount,
                    'total_payment_income' => 500,
                    'total_school_assets'  => 500 + $donationAmount,
                ]
            ]);
    }

    public function test_exports_csv_and_pdf()
    {
        $csvRes = $this->get('/api/payments/export-csv?year=2017&month=1');
        $csvRes->assertStatus(200);
        $csvRes->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $pdfRes = $this->get('/api/payments/export-pdf?year=2017&month=1');
        $pdfRes->assertStatus(200);
        $pdfRes->assertHeader('Content-Type', 'application/pdf');
    }
}
