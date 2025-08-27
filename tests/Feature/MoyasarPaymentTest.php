<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\User;
use App\PaymentChannels\Drivers\Moyasar\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MoyasarPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Order $order;
    protected PaymentChannel $paymentChannel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();

        // Create a test order
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'status' => 'pending'
        ]);

        // Create a test payment channel
        $this->paymentChannel = PaymentChannel::create([
            'title' => 'Moyasar',
            'class_name' => 'Moyasar',
            'status' => 'active',
            'credentials' => json_encode([
                'secret_key' => 'test_secret_key',
                'publishable_key' => 'test_publishable_key',
                'test_mode' => true
            ])
        ]);
    }

    public function test_moyasar_channel_creation()
    {
        $channel = new Channel($this->paymentChannel);

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertEquals(['secret_key', 'publishable_key'], $channel->getCredentialItems());
    }

    public function test_moyasar_credential_setting()
    {
        $channel = new Channel($this->paymentChannel);

        $reflection = new \ReflectionClass($channel);

        $secretKeyProperty = $reflection->getProperty('secret_key');
        $secretKeyProperty->setAccessible(true);

        $publishableKeyProperty = $reflection->getProperty('publishable_key');
        $publishableKeyProperty->setAccessible(true);

        $testModeProperty = $reflection->getProperty('test_mode');
        $testModeProperty->setAccessible(true);

        $this->assertEquals('test_secret_key', $secretKeyProperty->getValue($channel));
        $this->assertEquals('test_publishable_key', $publishableKeyProperty->getValue($channel));
        $this->assertTrue($testModeProperty->getValue($channel));
    }

    public function test_moyasar_currency_conversion()
    {
        $channel = new Channel($this->paymentChannel);

        $reflection = new \ReflectionClass($channel);
        $method = $reflection->getMethod('convertToSmallestUnit');
        $method->setAccessible(true);

        $result = $method->invoke($channel, 100.50, 'SAR');
        $this->assertEquals(10050, $result);
    }

    public function test_moyasar_currency_is_sar_only()
    {
        $channel = new Channel($this->paymentChannel);

        $reflection = new \ReflectionClass($channel);
        $currencyProperty = $reflection->getProperty('currency');
        $currencyProperty->setAccessible(true);

        $this->assertEquals('SAR', $currencyProperty->getValue($channel));
    }

    public function test_moyasar_callback_url_generation()
    {
        $channel = new Channel($this->paymentChannel);

        $reflection = new \ReflectionClass($channel);
        $method = $reflection->getMethod('makeCallbackUrl');
        $method->setAccessible(true);

        $result = $method->invoke($channel, 'success');
        $this->assertStringContainsString('/payments/verify/Moyasar?status=success', $result);
    }
}
