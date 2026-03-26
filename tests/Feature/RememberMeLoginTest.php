<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SerpRememberState;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RememberMeLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('serp_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->text('serp_token')->nullable();
            $table->string('role')->default('user')->nullable();
            $table->boolean('can_upload')->default(false);
            $table->boolean('can_share')->default(false);
            $table->integer('upload_limit')->default(0);
            $table->timestamp('upload_reset_at')->nullable();
            $table->integer('share_limit')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->string('status')->nullable();
            $table->string('created_from')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Route::middleware(['web', 'auth', 'serp.auth', 'serp.refresh'])
            ->get('/test-remember-home', fn () => response('ok', 200));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_login_with_remember_me_sets_recaller_and_serp_state_cookie(): void
    {
        Http::fake([
            'https://api-serp.smarter.com.ph/api/auth/login' => Http::response([
                'success' => true,
                'token' => 'access-token-1',
                'refreshToken' => 'refresh-token-1',
                'expiration' => now()->addHour()->toIso8601String(),
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ]),
        ]);

        $response = $this->post('/serp-login', [
            'username' => 'jane.doe',
            'password' => 'secret',
            'remember' => '1',
        ]);

        $response->assertRedirect('/home');
        $response->assertCookie(SerpRememberState::COOKIE_NAME);
        $response->assertCookie(Auth::guard('web')->getRecallerName());
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'serp_id' => 'jane.doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_authenticated_user_can_rebuild_missing_serp_session_from_remember_cookie(): void
    {
        Http::fake([
            'https://api-serp.smarter.com.ph/api/auth/refresh' => Http::response([
                'token' => 'access-token-2',
                'refreshToken' => 'refresh-token-2',
                'expiration' => now()->addHour()->toIso8601String(),
            ]),
        ]);

        $user = User::create([
            'serp_id' => 'jane.doe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'serp_token' => 'access-token-1',
            'role' => 'user',
        ]);

        $this->flushSession();

        $request = Request::create('/test-remember-home', 'GET', [], [
            SerpRememberState::COOKIE_NAME => json_encode([
                'auth_source' => 'serp',
                'serp_refresh' => 'refresh-token-1',
            ], JSON_THROW_ON_ERROR),
        ]);

        $restored = app(SerpRememberState::class)->restoreSessionFromRememberCookie($request, $user);

        $this->assertTrue($restored);
        $this->assertSame('serp', session('auth_source'));
        $this->assertSame('refresh-token-2', session('serp_refresh'));
        $this->assertSame('access-token-2', $user->fresh()->serp_token);
    }
}
