<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_page_renders_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.password'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Settings/Password'));
    }

    public function test_guest_is_redirected_from_password_page_to_login(): void
    {
        $this->get(route('settings.password'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_a_password(): void
    {
        $this->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertRedirect(route('login'));
    }

    public function test_correct_current_password_updates_the_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('settings.password'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertRedirect(route('settings.password'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-password', $user->refresh()->password));
    }

    public function test_wrong_current_password_keeps_the_existing_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('settings.password'))
            ->put(route('user-password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_mismatched_confirmation_keeps_the_existing_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('settings.password'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'something-else',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_wrong_current_password_error_reaches_the_page_in_the_update_password_bag(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('settings.password'))
            ->put(route('user-password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ]);

        $this->get(route('settings.password'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where(
                    'errors.updatePassword.current_password',
                    'The provided password does not match your current password.',
                ));
    }
}
