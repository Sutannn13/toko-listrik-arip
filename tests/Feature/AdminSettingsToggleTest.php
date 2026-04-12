<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSettingsToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_turn_off_maintenance_mode(): void
    {
        $superAdmin = $this->createSuperAdmin();

        Setting::where('key', 'maintenance_mode')->update(['value' => '1']);

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.settings.update'), [
                'maintenance_mode' => '0',
            ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $this->assertFalse(Setting::get('maintenance_mode'));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'maintenance_mode',
            'value' => '0',
        ]);
    }

    public function test_super_admin_can_turn_off_notification_toggle(): void
    {
        $superAdmin = $this->createSuperAdmin();

        Setting::where('key', 'notif_order_new')->update(['value' => '1']);

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.settings.update'), [
                'notif_order_new' => '0',
            ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $this->assertFalse(Setting::get('notif_order_new'));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'notif_order_new',
            'value' => '0',
        ]);
    }

    private function createSuperAdmin(): User
    {
        Role::findOrCreate('super-admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}
