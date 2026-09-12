<?php
namespace Tests\Unit\Services;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $auth;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake(); // Fake the event to avoid listener side-effects like email verification
        $this->auth = new AuthService();
        $this->request = Request::create("/", "POST");
        $this->request->setLaravelSession(app("session.store"));
    }

    public function test_register_creates_user(): void
    {
        $data = [
            "first_name" => "Test",
            "last_name" => "User",
            "email" => "test@example.com",
            "password" => "password123",
            "role" => UserRole::Client->value,
        ];

        $user = $this->auth->register($data);

        $this->assertEquals("test@example.com", $user->email);
        $this->assertEquals(UserRole::Client, $user->role);
        $this->assertEquals(UserStatus::Active, $user->status);
        $this->assertDatabaseHas("users", ["email" => "test@example.com"]);
        Event::assertDispatched(Registered::class); // Verify the event was triggered
    }

    public function test_login_success(): void
    {
        $password = "password123";
        $user = User::factory()->create([
            "password" => bcrypt($password),
            "status" => UserStatus::Active,
        ]);

        $authenticatedUser = $this->auth->login($this->request, $user->email, $password);

        $this->assertEquals($user->id, $authenticatedUser->id);
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            "password" => bcrypt("password123"),
        ]);

        $this->expectException(DomainException::class);
        $this->auth->login($this->request, $user->email, "wrong-password");
    }

    public function test_login_fails_for_suspended_account(): void
    {
        $password = "password123";
        $user = User::factory()->create([
            "password" => bcrypt($password),
            "status" => UserStatus::Suspended,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("This account has been suspended.");
        
        $this->auth->login($this->request, $user->email, $password);
        $this->assertFalse(Auth::check());
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();
        Auth::login($user);
        $this->assertTrue(Auth::check());

        $this->auth->logout($this->request);

        $this->assertFalse(Auth::check());
    }
}