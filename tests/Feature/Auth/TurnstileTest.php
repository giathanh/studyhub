<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    private const string VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * @var array{name: string, email: string, password: string, password_confirmation: string}
     */
    private array $registration = [
        'name' => 'Lan Nguyen',
        'email' => 'lan@example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.site_key' => 'site-key',
            'services.turnstile.secret_key' => 'secret-key',
        ]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function authPages(): array
    {
        return [
            'login' => ['login', 'Auth/Login'],
            'register' => ['register', 'Auth/Register'],
        ];
    }

    #[DataProvider('authPages')]
    public function test_auth_page_receives_the_site_key_but_not_the_secret(string $routeName, string $component): void
    {
        $this->get(route($routeName))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component($component)
                ->where('turnstileSiteKey', 'site-key'));

        $this->get(route($routeName))->assertDontSee('secret-key');
    }

    #[DataProvider('authPages')]
    public function test_auth_page_receives_no_site_key_while_turnstile_is_disabled(string $routeName, string $component): void
    {
        config(['services.turnstile.enabled' => false]);

        $this->get(route($routeName))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component($component)
                ->where('turnstileSiteKey', null));
    }

    public function test_login_succeeds_with_a_token_cloudflare_accepts(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            Turnstile::RESPONSE_FIELD => 'valid-token',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::VERIFY_URL
            && $request['secret'] === 'secret-key'
            && $request['response'] === 'valid-token');
    }

    public function test_login_without_a_token_is_rejected_without_calling_cloudflare(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(Turnstile::RESPONSE_FIELD);

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_login_with_a_token_cloudflare_rejects_is_refused(): void
    {
        Http::fake([self::VERIFY_URL => Http::response([
            'success' => false,
            'error-codes' => ['timeout-or-duplicate'],
        ])]);
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            Turnstile::RESPONSE_FIELD => 'spent-token',
        ])->assertSessionHasErrors(Turnstile::RESPONSE_FIELD);

        $this->assertGuest();
    }

    public function test_login_is_refused_when_cloudflare_returns_a_server_error(): void
    {
        Http::fake([self::VERIFY_URL => Http::response('Bad gateway', 502)]);
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            Turnstile::RESPONSE_FIELD => 'valid-token',
        ])->assertSessionHasErrors(Turnstile::RESPONSE_FIELD);

        $this->assertGuest();
    }

    public function test_login_is_refused_and_reported_when_cloudflare_is_unreachable(): void
    {
        Exceptions::fake();
        Http::fake([self::VERIFY_URL => Http::failedConnection()]);
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            Turnstile::RESPONSE_FIELD => 'valid-token',
        ])->assertSessionHasErrors(Turnstile::RESPONSE_FIELD);

        $this->assertGuest();
        Exceptions::assertReported(ConnectionException::class);
    }

    public function test_login_skips_cloudflare_while_turnstile_is_disabled(): void
    {
        config(['services.turnstile.enabled' => false]);
        Http::fake([self::VERIFY_URL => Http::response(['success' => false])]);
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        Http::assertNothingSent();
    }

    public function test_registration_succeeds_with_a_token_cloudflare_accepts(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);

        $this->post(route('register.store'), [...$this->registration, Turnstile::RESPONSE_FIELD => 'valid-token'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'lan@example.com']);
    }

    public function test_registration_with_a_token_cloudflare_rejects_creates_no_user(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => false])]);

        $this->post(route('register.store'), [...$this->registration, Turnstile::RESPONSE_FIELD => 'bad-token'])
            ->assertSessionHasErrors(Turnstile::RESPONSE_FIELD);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_without_a_token_does_not_reveal_that_an_email_is_taken(): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);
        User::factory()->create(['email' => 'lan@example.com']);

        $this->post(route('register.store'), $this->registration)
            ->assertSessionHasErrors(Turnstile::RESPONSE_FIELD)
            ->assertSessionDoesntHaveErrors('email');

        Http::assertNothingSent();
    }
}
