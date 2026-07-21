<?php

namespace Tests\Feature;

use Modules\Checkout\App\Exceptions\CheckoutException;
use Modules\Checkout\App\Services\CheckoutService;
use Tests\TestCase;

/**
 * Menguji lapisan kontrak endpoint terpadu POST /api/v1/checkout:
 * validasi request (CheckoutRequest) + pemetaan hasil/exception ke envelope response.
 * CheckoutService di-mock, jadi tidak menyentuh DB / Midtrans (orkestrasi diuji manual/e2e).
 */
class CheckoutApiTest extends TestCase
{
    private function validPayload(array $override = []): array
    {
        return array_merge([
            'company_id' => 'company-uuid-1',
            'customer' => [
                'name' => 'Budi',
                'email' => 'budi@example.com',
                'phone' => '08123456789',
                'address' => 'Jl. Contoh',
            ],
            'items' => [
                ['type' => 'package', 'id' => 'package-uuid-1', 'quantity' => 1, 'termin' => 'month', 'price' => 100000],
            ],
            'promo_code' => null,
            'is_renew' => false,
        ], $override);
    }

    public function test_valid_checkout_returns_standard_success_envelope(): void
    {
        $this->mock(CheckoutService::class, function ($mock) {
            $mock->shouldReceive('process')->once()->andReturn([
                'invoice_id' => 'invoice-uuid-1',
                'invoice_code' => 'ABCDE',
                'snap_token' => 'snap-token-xyz',
                'paid_directly' => false,
                'total' => 100000,
            ]);
        });

        $this->postJson('/api/v1/checkout', $this->validPayload())
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Ok',
                'data' => [
                    'invoice_id' => 'invoice-uuid-1',
                    'invoice_code' => 'ABCDE',
                    'snap_token' => 'snap-token-xyz',
                    'paid_directly' => false,
                    'total' => 100000,
                ],
            ]);
    }

    public function test_free_checkout_returns_paid_directly(): void
    {
        $this->mock(CheckoutService::class, function ($mock) {
            $mock->shouldReceive('process')->once()->andReturn([
                'invoice_id' => 'invoice-uuid-2',
                'invoice_code' => 'FREE1',
                'snap_token' => null,
                'paid_directly' => true,
                'total' => 0,
            ]);
        });

        $this->postJson('/api/v1/checkout', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('data.paid_directly', true)
            ->assertJsonPath('data.snap_token', null);
    }

    public function test_addon_only_without_active_subscription_returns_403(): void
    {
        $this->mock(CheckoutService::class, function ($mock) {
            $mock->shouldReceive('process')->once()->andThrow(CheckoutException::subscriptionRequired());
        });

        $this->postJson('/api/v1/checkout', $this->validPayload([
            'items' => [
                ['type' => 'addon', 'id' => 'addon-uuid-1', 'quantity' => 2, 'termin' => 'month', 'price' => 99000],
            ],
        ]))
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'SUBSCRIPTION_REQUIRED']);
    }

    public function test_item_not_found_returns_404_with_code(): void
    {
        $this->mock(CheckoutService::class, function ($mock) {
            $mock->shouldReceive('process')->once()->andThrow(CheckoutException::itemNotFound('package-uuid-1'));
        });

        $this->postJson('/api/v1/checkout', $this->validPayload())
            ->assertStatus(404)
            ->assertJson(['success' => false, 'code' => 'ITEM_NOT_FOUND']);
    }

    public function test_unexpected_error_returns_500_internal_error(): void
    {
        $this->mock(CheckoutService::class, function ($mock) {
            $mock->shouldReceive('process')->once()->andThrow(new \RuntimeException('boom'));
        });

        $this->postJson('/api/v1/checkout', $this->validPayload())
            ->assertStatus(500)
            ->assertJson(['success' => false, 'code' => 'INTERNAL_ERROR']);
    }

    public function test_empty_items_fails_validation_422(): void
    {
        $this->postJson('/api/v1/checkout', $this->validPayload(['items' => []]))
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'VALIDATION_ERROR']);
    }

    public function test_invalid_item_type_fails_validation_422(): void
    {
        $this->postJson('/api/v1/checkout', $this->validPayload([
            'items' => [
                ['type' => 'bundle', 'id' => 'x', 'quantity' => 1, 'termin' => 'month', 'price' => 1000],
            ],
        ]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_missing_customer_fails_validation_422(): void
    {
        $payload = $this->validPayload();
        unset($payload['customer']);

        $this->postJson('/api/v1/checkout', $payload)
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_qris_checkout_returns_qris_instructions(): void
    {
        $this->mock(CheckoutService::class, function ($mock) {
            $mock->shouldReceive('process')->once()->andReturn([
                'invoice_id' => 'invoice-uuid-3',
                'invoice_code' => 'QRIS1',
                'order_id' => 'QRIS1-1700000000',
                'payment_channel' => 'qris',
                'snap_token' => null,
                'qris' => [
                    'qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/xxx/qr-code',
                    'qr_string' => '00020101...',
                    'expiry_time' => '2026-07-20 12:00:00',
                ],
                'paid_directly' => false,
                'total' => 99000,
            ]);
        });

        $this->postJson('/api/v1/checkout', $this->validPayload([
            'payment_channel' => 'qris',
            'items' => [
                ['type' => 'addon', 'id' => 'addon-uuid-1', 'quantity' => 1, 'termin' => 'month', 'price' => 99000],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('data.payment_channel', 'qris')
            ->assertJsonPath('data.snap_token', null)
            ->assertJsonPath('data.order_id', 'QRIS1-1700000000')
            ->assertJsonPath('data.qris.qr_url', 'https://api.sandbox.midtrans.com/v2/qris/xxx/qr-code');
    }

    public function test_invalid_payment_channel_fails_validation_422(): void
    {
        $this->postJson('/api/v1/checkout', $this->validPayload(['payment_channel' => 'gopay']))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }
}
