<?php

namespace Tests\Feature;

use App\Models\BnplProvider;
use App\Models\Order;
use App\Models\User;
use App\Models\Webinar;
use App\Services\BnplPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BnplPaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $bnplService;
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bnplService = new BnplPaymentService();

        // Create test user
        $this->user = User::factory()->create();

        // Create test BNPL provider
        $this->provider = BnplProvider::create([
            'name' => 'TestProvider',
            'fee_percentage' => 8.00,
            'installment_count' => 4,
            'is_active' => true,
            'config' => [
                'min_amount' => 50.00,
                'max_amount' => 5000.00,
                'max_concurrent_payments' => 3
            ]
        ]);
    }

    /** @test */
    public function it_can_calculate_bnpl_payment_breakdown()
    {
        $basePrice = 100.00;
        $vatPercentage = 15;

        $result = $this->bnplService->calculateBnplPayment($basePrice, $vatPercentage, 'TestProvider');

        $this->assertIsArray($result);
        $this->assertEquals(100.00, $result['base_price']);
        $this->assertEquals(15, $result['vat_percentage']);
        $this->assertEquals(15.00, $result['vat_amount']);
        $this->assertEquals(115.00, $result['price_with_vat']);
        $this->assertEquals('TestProvider', $result['bnpl_provider']);
        $this->assertEquals(8.00, $result['bnpl_fee_percentage']);
        $this->assertEquals(9.20, $result['bnpl_fee']); // 115 * 0.08
        $this->assertEquals(4, $result['installment_count']);
        $this->assertEquals(124.20, $result['total_amount']); // 115 + 9.20
        $this->assertEquals(31.05, $result['installment_amount']); // 124.20 / 4
    }

    /** @test */
    public function it_validates_input_parameters_for_calculation()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->bnplService->calculateBnplPayment(-100, 15, 'TestProvider');
    }

    /** @test */
    public function it_throws_exception_for_inactive_provider()
    {
        $this->provider->update(['is_active' => false]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('BNPL provider not available or inactive');

        $this->bnplService->calculateBnplPayment(100, 15, 'TestProvider');
    }

    /** @test */
    public function it_can_validate_user_eligibility()
    {
        $amount = 200.00;

        $result = $this->bnplService->validateEligibility($this->user->id, $amount, 'TestProvider');

        $this->assertIsArray($result);
        $this->assertTrue($result['eligible']);
        $this->assertEquals($this->provider, $result['provider']);
    }

    /** @test */
    public function it_rejects_eligibility_for_amount_below_minimum()
    {
        $amount = 25.00; // Below minimum of 50

        $result = $this->bnplService->validateEligibility($this->user->id, $amount, 'TestProvider');

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('Amount below minimum threshold', $result['reason']);
    }

    /** @test */
    public function it_rejects_eligibility_for_amount_above_maximum()
    {
        $amount = 10000.00; // Above maximum of 5000

        $result = $this->bnplService->validateEligibility($this->user->id, $amount, 'TestProvider');

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('Amount above maximum threshold', $result['reason']);
    }

    /** @test */
    public function it_can_create_bnpl_order()
    {
        $amount = 200.00;
        $items = [
            [
                'webinar_id' => 1,
                'amount' => 200.00,
                'total_amount' => 200.00,
                'tax' => 0,
                'discount' => 0
            ]
        ];

        $result = $this->bnplService->createBnplOrder(
            $this->user->id,
            $amount,
            'TestProvider',
            $items,
            15
        );

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Order::class, $result['order']);
        $this->assertEquals($this->user->id, $result['order']->user_id);
        $this->assertEquals(Order::$bnpl, $result['order']->payment_method);
        $this->assertEquals('TestProvider', $result['order']->bnpl_provider);
        $this->assertEquals(8.00, $result['order']->bnpl_fee_percentage);
        $this->assertEquals(4, $result['order']->installment_count);
    }

    /** @test */
    public function it_validates_input_parameters_for_order_creation()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->bnplService->createBnplOrder(
            $this->user->id,
            -100, // Invalid amount
            'TestProvider',
            [],
            15
        );
    }

    /** @test */
    public function it_checks_eligibility_before_creating_order()
    {
        $amount = 25.00; // Below minimum

        $result = $this->bnplService->createBnplOrder(
            $this->user->id,
            $amount,
            'TestProvider',
            [['webinar_id' => 1, 'amount' => 25.00, 'total_amount' => 25.00, 'tax' => 0, 'discount' => 0]],
            15
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('BNPL eligibility check failed', $result['error']);
    }

    /** @test */
    public function it_can_get_available_providers()
    {
        $providers = $this->bnplService->getAvailableProviders();

        $this->assertCount(1, $providers);
        $this->assertEquals('TestProvider', $providers->first()->name);
        $this->assertTrue($providers->first()->is_active);
    }

    /** @test */
    public function it_returns_empty_collection_when_no_providers_available()
    {
        $this->provider->update(['is_active' => false]);

        $providers = $this->bnplService->getAvailableProviders();

        $this->assertCount(0, $providers);
    }

    /** @test */
    public function it_can_process_installment_payment()
    {
        // Create a BNPL order first
        $order = Order::create([
            'user_id' => $this->user->id,
            'status' => Order::$pending,
            'payment_method' => Order::$bnpl,
            'amount' => 200.00,
            'tax' => 30.00,
            'total_amount' => 230.00,
            'bnpl_provider' => 'TestProvider',
            'bnpl_fee' => 18.40,
            'bnpl_fee_percentage' => 8.00,
            'installment_count' => 4,
            'bnpl_payment_schedule' => [
                [
                    'installment_number' => 1,
                    'amount' => 62.10,
                    'due_date' => now()->format('Y-m-d'),
                    'status' => 'pending'
                ],
                [
                    'installment_number' => 2,
                    'amount' => 62.10,
                    'due_date' => now()->addDays(30)->format('Y-m-d'),
                    'status' => 'pending'
                ]
            ],
            'created_at' => time()
        ]);

        $result = $this->bnplService->processInstallmentPayment($order->id, 1);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['all_paid']); // Only first installment paid
    }

    /** @test */
    public function it_throws_exception_for_invalid_sale_id()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->bnplService->processInstallmentPayment(99999, 1);
    }

    /** @test */
    public function it_throws_exception_for_non_bnpl_payment()
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'status' => Order::$pending,
            'payment_method' => Order::$credit,
            'amount' => 200.00,
            'total_amount' => 200.00,
            'created_at' => time()
        ]);

        $result = $this->bnplService->processInstallmentPayment($order->id, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('Not a BNPL payment', $result['error']);
    }

    /** @test */
    public function it_can_get_bnpl_statistics()
    {
        $result = $this->bnplService->getBnplStatistics();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_payments']);
        $this->assertEquals(0, $result['total_amount']);
        $this->assertEquals(0, $result['total_fees']);
        $this->assertCount(0, $result['providers']);
    }

    /** @test */
    public function it_handles_database_errors_gracefully()
    {
        // Mock a database error by using an invalid user ID
        $result = $this->bnplService->validateEligibility(99999, 200.00, 'TestProvider');

        // Should handle the error gracefully and return false eligibility
        $this->assertFalse($result['eligible']);
    }

    /** @test */
    public function it_generates_unique_order_numbers()
    {
        $orderNumber1 = $this->invokeMethod($this->bnplService, 'generateOrderNumber');
        $orderNumber2 = $this->invokeMethod($this->bnplService, 'generateOrderNumber');

        $this->assertNotEquals($orderNumber1, $orderNumber2);
        $this->assertStringStartsWith('BNPL', $orderNumber1);
        $this->assertStringStartsWith('BNPL', $orderNumber2);
    }

    /** @test */
    public function it_can_handle_edge_cases_in_payment_schedule()
    {
        $schedule = $this->invokeMethod($this->bnplService, 'generatePaymentSchedule', [100.00, 3]);

        $this->assertCount(3, $schedule);
        $this->assertEquals(33.33, $schedule[0]['amount']);
        $this->assertEquals(33.33, $schedule[1]['amount']);
        $this->assertEquals(33.34, $schedule[2]['amount']); // Handles rounding
    }

    /** @test */
    public function it_validates_payment_schedule_parameters()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid amount or installment count for payment schedule');

        $this->invokeMethod($this->bnplService, 'generatePaymentSchedule', [0, 3]);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
