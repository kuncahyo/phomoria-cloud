<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentProtectionTest extends TestCase
{
    private string $markerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markerPath = public_path('payment-suspended.txt');
        if (is_file($this->markerPath)) {
            unlink($this->markerPath);
        }

        config(['services.payment_protection.token' => 'test-payment-protection-token']);
    }

    protected function tearDown(): void
    {
        if (is_file($this->markerPath)) {
            unlink($this->markerPath);
        }

        parent::tearDown();
    }

    public function test_site_is_available_when_suspension_marker_is_absent(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_site_pages_are_suspended_when_marker_exists(): void
    {
        file_put_contents($this->markerPath, 'test suspension');

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Layanan Sementara Ditangguhkan');
    }

    public function test_api_requests_are_suspended_when_marker_exists(): void
    {
        file_put_contents($this->markerPath, 'test suspension');

        $this->getJson('/api/auth/me')
            ->assertStatus(503)
            ->assertJsonPath('code', 'SERVICE_SUSPENDED');
    }

    public function test_only_a_valid_operator_token_can_suspend_the_site(): void
    {
        $this->postJson('/api/payment-protection/suspend')
            ->assertUnauthorized();

        $this->assertFileDoesNotExist($this->markerPath);

        $this->withToken('test-payment-protection-token')
            ->postJson('/api/payment-protection/suspend')
            ->assertOk()
            ->assertJsonPath('status', 'suspended');

        $this->assertFileExists($this->markerPath);
    }

    public function test_authorized_operator_can_resume_the_site(): void
    {
        file_put_contents($this->markerPath, 'test suspension');

        $this->withToken('test-payment-protection-token')
            ->postJson('/api/payment-protection/resume')
            ->assertOk()
            ->assertJsonPath('status', 'active');

        $this->assertFileDoesNotExist($this->markerPath);
        $this->get('/')->assertOk();
    }

    public function test_control_endpoints_remain_available_during_suspension(): void
    {
        file_put_contents($this->markerPath, 'test suspension');

        $this->withToken('test-payment-protection-token')
            ->postJson('/api/payment-protection/resume')
            ->assertOk();
    }
}
