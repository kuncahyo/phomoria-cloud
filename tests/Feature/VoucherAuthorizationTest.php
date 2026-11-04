<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Models\VoucherCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createUserAndDevice(): array
    {
        $user = User::create([
            'name' => 'Voucher Test',
            'email' => 'voucher@example.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $device = Device::create([
            'user_id' => $user->id,
            'device_uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'computer_name' => 'TEST-PC',
            'windows_user' => 'tester',
            'operating_system' => 'Windows',
            'java_version' => '21',
            'app_version' => 'test',
            'status' => 'ACTIVE',
        ]);

        return [$user, $device];
    }

    public function test_active_voucher_is_reserved_until_session_upload_completes(): void
    {
        [$user, $device] = $this->createUserAndDevice();

        VoucherCode::create([
            'code' => 'ONE-TIME-001',
            'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/voucher/authorize-session', [
            'code' => 'one-time-001',
            'device_uuid' => $device->device_uuid,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['session_authorization_token', 'expires_at']);

        $this->assertDatabaseHas('voucher_codes', [
            'code' => 'ONE-TIME-001',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('voucher_redemptions', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'status' => 'authorized',
        ]);

        $this->actingAs($user)->postJson('/api/voucher/authorize-session', [
            'code' => 'ONE-TIME-001',
            'device_uuid' => $device->device_uuid,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('voucher_redemptions', 1);
    }

    public function test_secret_voucher_remains_reusable(): void
    {
        [$user, $device] = $this->createUserAndDevice();

        VoucherCode::create([
            'code' => 'SECRET-001',
            'status' => 'secret',
            'expires_at' => null,
        ]);

        $first = $this->actingAs($user)->postJson('/api/voucher/authorize-session', [
            'code' => 'SECRET-001',
            'device_uuid' => $device->device_uuid,
        ]);
        $first->assertOk();

        $second = $this->actingAs($user)->postJson('/api/voucher/authorize-session', [
            'code' => 'SECRET-001',
            'device_uuid' => $device->device_uuid,
        ]);
        $second->assertOk();

        $this->assertDatabaseHas('voucher_codes', [
            'code' => 'SECRET-001',
            'status' => 'secret',
        ]);
        $this->assertDatabaseCount('voucher_redemptions', 2);
    }

    public function test_used_voucher_cannot_be_authorized_again(): void
    {
        [$user, $device] = $this->createUserAndDevice();

        VoucherCode::create([
            'code' => 'ALREADY-USED',
            'status' => 'used',
        ]);

        $this->actingAs($user)->postJson('/api/voucher/authorize-session', [
            'code' => 'ALREADY-USED',
            'device_uuid' => $device->device_uuid,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('voucher_redemptions', 0);
    }

    public function test_device_must_belong_to_authenticated_user_and_be_active(): void
    {
        [$user] = $this->createUserAndDevice();
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $device = Device::where('device_uuid', '123e4567-e89b-12d3-a456-426614174000')->first();
        $device->user_id = $otherUser->id;
        $device->save();

        VoucherCode::create(['code' => 'DEVICE-CHECK', 'status' => 'active']);

        $this->actingAs($user)->postJson('/api/voucher/authorize-session', [
            'code' => 'DEVICE-CHECK',
            'device_uuid' => $device->device_uuid,
        ])->assertForbidden();

        $this->assertDatabaseHas('voucher_codes', [
            'code' => 'DEVICE-CHECK',
            'status' => 'active',
        ]);
    }
}
