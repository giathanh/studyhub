<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array{name: string, email: string, password: string, password_confirmation: string}
     */
    private array $validPayload = [
        'name' => 'Lan Nguyen',
        'email' => 'lan@example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ];

    public function test_register_page_renders_for_guests(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Register'));
    }

    public function test_valid_payload_creates_the_user_and_logs_them_in(): void
    {
        $this->post(route('register.store'), $this->validPayload)
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'lan@example.com')->sole();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Lan Nguyen', $user->name);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
    }

    public function test_email_is_stored_in_lowercase(): void
    {
        $this->post(route('register.store'), [...$this->validPayload, 'email' => 'Lan@Example.COM'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'lan@example.com']);
    }

    public function test_empty_payload_is_rejected(): void
    {
        $this->post(route('register.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'lan@example.com']);

        $this->post(route('register.store'), $this->validPayload)
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_mismatched_password_confirmation_is_rejected(): void
    {
        $this->post(route('register.store'), [...$this->validPayload, 'password_confirmation' => 'something-else'])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_authenticated_user_cannot_register_another_account(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('register.store'), $this->validPayload)
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('users', ['email' => 'lan@example.com']);
    }
}
