<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\PhotoSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceAccountIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $email): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function registrationPayload(string $uuid): array
    {
        return [
            'device_uuid' => $uuid,
            'computer_name' => 'PHOMORIA-PC',
            'windows_user' => 'operator',
            'operating_system' => 'Windows 11',
            'java_version' => '21',
            'app_version' => 'test',
        ];
    }

    public function test_same_physical_device_can_be_registered_to_two_accounts_without_moving_account_data(): void
    {
        $firstUser = $this->createUser('first@example.test');
        $secondUser = $this->createUser('second@example.test');
        $uuid = '123e4567-e89b-12d3-a456-426614174000';

        Sanctum::actingAs($firstUser);
        $firstRegistration = $this->postJson('/api/device/register', $this->registrationPayload($uuid))
            ->assertOk();

        $firstDeviceId = $firstRegistration->json('device_id');

        Device::whereKey($firstDeviceId)->update([
            'session_price' => 25000,
            'password_hash' => bcrypt('1111'),
        ]);

        Sanctum::actingAs($secondUser);
        $secondRegistration = $this->postJson('/api/device/register', $this->registrationPayload($uuid))
            ->assertOk();

        $secondDeviceId = $secondRegistration->json('device_id');

        $this->assertNotSame($firstDeviceId, $secondDeviceId);
        $this->assertDatabaseCount('devices', 2);
        $this->assertDatabaseHas('devices', [
            'id' => $firstDeviceId,
            'user_id' => $firstUser->id,
            'device_uuid' => $uuid,
            'session_price' => 25000,
        ]);
        $this->assertDatabaseHas('devices', [
            'id' => $secondDeviceId,
            'user_id' => $secondUser->id,
            'device_uuid' => $uuid,
            'session_price' => null,
            'password_hash' => null,
        ]);

        Sanctum::actingAs($firstUser);
        $this->getJson('/api/device/config?device_uuid=' . $uuid)
            ->assertOk()
            ->assertJsonPath('data.user.id', $firstUser->id)
            ->assertJsonPath('data.device.id', $firstDeviceId)
            ->assertJsonPath('data.device.session_price', 25000)
            ->assertJsonPath('data.device.password_hash', Device::findOrFail($firstDeviceId)->password_hash);

        Sanctum::actingAs($secondUser);
        $this->getJson('/api/device/config?device_uuid=' . $uuid)
            ->assertOk()
            ->assertJsonPath('data.user.id', $secondUser->id)
            ->assertJsonPath('data.device.id', $secondDeviceId)
            ->assertJsonPath('data.device.session_price', null)
            ->assertJsonPath('data.device.password_hash', null);

        // The public gallery session-code prefix must also distinguish accounts
        // that are using the same physical installation UUID.
        Sanctum::actingAs($firstUser);
        $this->postJson('/api/session/reserve', [
            'device_uuid' => $uuid,
            'client_session_id' => '123e4567-e89b-12d3-a456-426614174010',
            'frame_name' => 'First Account Frame',
            'assets' => [[
                'asset_key' => 'result',
                'asset_type' => 'result',
                'filename' => 'result.png',
            ]],
        ])->assertOk();

        Sanctum::actingAs($secondUser);
        $this->postJson('/api/session/reserve', [
            'device_uuid' => $uuid,
            'client_session_id' => '123e4567-e89b-12d3-a456-426614174011',
            'frame_name' => 'Second Account Frame',
            'assets' => [[
                'asset_key' => 'result',
                'asset_type' => 'result',
                'filename' => 'result.png',
            ]],
        ])->assertOk();

        $sessionCodes = PhotoSession::orderBy('id')->pluck('session_code')->all();
        $this->assertCount(2, $sessionCodes);
        $this->assertNotSame(
            explode('-', $sessionCodes[0])[0],
            explode('-', $sessionCodes[1])[0]
        );

        // Re-registering under the same account updates its existing row instead
        // of creating a duplicate account/device association.
        $this->postJson('/api/device/register', $this->registrationPayload($uuid))
            ->assertOk()
            ->assertJsonPath('device_id', $secondDeviceId);

        $this->assertDatabaseCount('devices', 2);
    }

    public function test_legacy_upload_cannot_use_another_accounts_device_uuid(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherUser = $this->createUser('other@example.test');

        $device = Device::create([
            'user_id' => $owner->id,
            'device_uuid' => '123e4567-e89b-12d3-a456-426614174001',
            'computer_name' => 'OWNER-PC',
            'windows_user' => 'owner',
            'operating_system' => 'Windows 11',
            'java_version' => '21',
            'app_version' => 'test',
            'status' => 'ACTIVE',
        ]);

        Sanctum::actingAs($otherUser);

        $this->post('/api/session/upload', [
            'device_uuid' => $device->device_uuid,
            'frame_name' => 'Owner Frame',
            'result' => \Illuminate\Http\UploadedFile::fake()->image('result.png'),
        ], ['Accept' => 'application/json'])->assertNotFound();

        $this->assertDatabaseCount('photo_sessions', 0);
    }
}
