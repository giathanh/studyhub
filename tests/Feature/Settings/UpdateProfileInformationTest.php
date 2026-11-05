<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UpdateProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_for_authenticated_users(): void
    {
        $user = User::factory()->create([
            'name' => 'Lan Nguyen',
            'email' => 'lan@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('settings.profile'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Settings/Profile')
                ->where('user', [
                    'name' => 'Lan Nguyen',
                    'email' => 'lan@example.com',
                ]));
    }

    public function test_guest_is_redirected_from_profile_page_to_login(): void
    {
        $this->get(route('settings.profile'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_profile_information(): void
    {
        $this->put(route('user-profile-information.update'), [
            'name' => 'Lan Nguyen',
            'email' => 'lan@example.com',
        ])->assertRedirect(route('login'));
    }

    public function test_valid_payload_updates_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user)
            ->from(route('settings.profile'))
            ->put(route('user-profile-information.update'), [
                'name' => 'Lan Nguyen',
                'email' => 'lan@example.com',
            ])
            ->assertRedirect(route('settings.profile'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Lan Nguyen', $user->name);
        $this->assertSame('lan@example.com', $user->email);
    }

    public function test_email_is_stored_in_lowercase(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('settings.profile'))
            ->put(route('user-profile-information.update'), [
                'name' => 'Lan Nguyen',
                'email' => 'Lan@Example.COM',
            ])
            ->assertRedirect(route('settings.profile'))
            ->assertSessionHasNoErrors();

        $this->assertSame('lan@example.com', $user->refresh()->email);
    }

    public function test_user_can_keep_their_existing_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'lan@example.com',
        ]);

        $this->actingAs($user)
            ->from(route('settings.profile'))
            ->put(route('user-profile-information.update'), [
                'name' => 'New Name',
                'email' => 'lan@example.com',
            ])
            ->assertRedirect(route('settings.profile'))
            ->assertSessionHasNoErrors();

        $this->assertSame('New Name', $user->refresh()->name);
    }

    public function test_duplicate_email_from_another_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'lan@example.com']);

        $this->actingAs($user)
            ->from(route('settings.profile'))
            ->put(route('user-profile-information.update'), [
                'name' => 'Lan Nguyen',
                'email' => 'taken@example.com',
            ])
            ->assertRedirect(route('settings.profile'))
            ->assertSessionHasErrorsIn('updateProfileInformation', 'email');

        $this->assertSame('lan@example.com', $user->refresh()->email);
    }

    public function test_empty_payload_error_reaches_the_page_in_the_update_profile_information_bag(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('settings.profile'))
            ->put(route('user-profile-information.update'), [
                'name' => '',
                'email' => '',
            ]);

        $this->get(route('settings.profile'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('errors.updateProfileInformation.name', 'The name field is required.')
                ->where('errors.updateProfileInformation.email', 'The email field is required.'));
    }
}
