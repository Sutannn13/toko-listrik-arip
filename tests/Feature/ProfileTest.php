<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        $this->assertTrue(Storage::disk('public')->exists($user->profile_photo_path));
    }

    public function test_user_can_remove_existing_profile_photo(): void
    {
        Storage::fake('public');

        $existingPhotoPath = UploadedFile::fake()->image('existing.jpg')->store('profile-photos', 'public');

        $user = User::factory()->create([
            'profile_photo_path' => $existingPhotoPath,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'remove_profile_photo' => '1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNull($user->fresh()->profile_photo_path);
        $this->assertFalse(Storage::disk('public')->exists($existingPhotoPath));
    }

    public function test_user_can_access_their_own_profile_photo_endpoint(): void
    {
        Storage::fake('public');

        $existingPhotoPath = UploadedFile::fake()->image('self.jpg')->store('profile-photos', 'public');
        $user = User::factory()->create([
            'profile_photo_path' => $existingPhotoPath,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('profile.photo', $user));

        $response->assertOk();
    }

    public function test_user_cannot_access_other_users_profile_photo_endpoint(): void
    {
        Storage::fake('public');

        $ownerPhotoPath = UploadedFile::fake()->image('owner.jpg')->store('profile-photos', 'public');
        $owner = User::factory()->create([
            'profile_photo_path' => $ownerPhotoPath,
        ]);
        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($otherUser)
            ->get(route('profile.photo', $owner));

        $response->assertForbidden();
    }

    public function test_admin_profile_page_uses_admin_specific_layout(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Profil Admin');
        $response->assertDontSee('Alamat Pengiriman');
    }

    public function test_regular_user_profile_page_keeps_customer_sections(): void
    {
        Role::findOrCreate('user', 'web');

        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Profil Akun');
        $response->assertSee('Alamat Pengiriman');
    }

    public function test_user_can_delete_their_account(): void
    {
        Storage::fake('public');

        $existingPhotoPath = UploadedFile::fake()->image('to-delete.jpg')->store('profile-photos', 'public');

        $user = User::factory()->create([
            'profile_photo_path' => $existingPhotoPath,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
        $this->assertFalse(Storage::disk('public')->exists($existingPhotoPath));
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
