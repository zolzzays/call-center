<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Нэвтрэх нэр');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_seeded_admin_can_log_in_and_reach_admin(): void
    {
        $this->seed();

        $this->post('/login', ['username' => 'adminuser', 'password' => 'zol123456@'])
            ->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')->assertOk()->assertSee('Хянах самбар')->assertSee('Системийн админ');
    }

    public function test_regular_user_cannot_access_admin(): void
    {
        User::factory()->create(['username' => 'bat', 'password' => 'secret123']);

        $this->post('/login', ['username' => 'bat', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create(['username' => 'off', 'password' => 'secret123', 'is_active' => false]);

        $this->post('/login', ['username' => 'off', 'password' => 'secret123'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_locks_out_after_five_failed_attempts(): void
    {
        $this->seed();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'adminuser', 'password' => 'wrong']);
        }

        $this->post('/login', ['username' => 'adminuser', 'password' => 'zol123456@'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_get_logout_redirects_instead_of_erroring(): void
    {
        $this->get('/logout')->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/logout')->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_post_logout_logs_out(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
