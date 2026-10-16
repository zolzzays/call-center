<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name(): void
    {
        $user = User::factory()->create(['name' => 'Хуучин']);

        $this->actingAs($user)->put('/profile', ['name' => 'Шинэ нэр'])
            ->assertSessionHas('status');

        $this->assertSame('Шинэ нэр', $user->fresh()->name);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpass123']);

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'oldpass123',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertSessionHas('status');

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'oldpass123']);

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'wrong',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->assertTrue(Hash::check('oldpass123', $user->fresh()->password));
    }
}
