<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\RepairCategory;
use App\Models\RepairType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_sees_settings_submenu(): void
    {
        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['Хянах самбар', 'Тохиргоо', 'Хэрэглэгч', 'Эрхийн түвшин', 'Албан тушаал', 'Алба, хэлтэс', 'Засвар төрөл']);
    }

    public function test_regular_user_has_no_settings_menu_or_access(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('Эрхийн түвшин');

        foreach (['users', 'roles', 'positions', 'departments', 'repair-types', 'repair-categories'] as $section) {
            $this->actingAs($user)->get("/settings/{$section}")->assertForbidden();
        }
    }

    public function test_all_settings_pages_render(): void
    {
        $admin = $this->admin();
        $position = Position::create(['name' => 'Инженер']);
        $type = RepairType::create(['name' => 'Компьютер']);
        $department = Department::create(['name' => 'МТ алба']);

        $pages = [
            '/settings/users', '/settings/users/create', "/settings/users/{$admin->id}/edit",
            '/settings/roles', '/settings/roles/create', '/settings/roles/1/edit',
            '/settings/positions', '/settings/positions/create', "/settings/positions/{$position->id}/edit",
            '/settings/departments', '/settings/departments/create', "/settings/departments/{$department->id}/edit",
            '/settings/repair-types', '/settings/repair-types/create', "/settings/repair-types/{$type->id}/edit",
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_admin_can_create_user_who_can_then_log_in(): void
    {
        $department = Department::create(['name' => 'Диспетчерийн алба']);
        $position = Position::create(['department_id' => $department->id, 'name' => 'Диспетчер']);
        $roleId = Role::where('slug', Role::USER)->value('id');

        $this->actingAs($this->admin())->post('/settings/users', [
            'name' => 'Бат',
            'username' => 'bat01',
            'role_id' => $roleId,
            'position_id' => $position->id,
            'department_id' => $department->id,
            'is_active' => '1',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect('/settings/users');

        $user = User::where('username', 'bat01')->firstOrFail();
        $this->assertSame($position->id, $user->position_id);
        $this->assertSame($department->id, $user->department_id);
        $this->assertTrue(Hash::check('secret123', $user->password));

        auth()->logout();
        $this->post('/login', ['username' => 'bat01', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_username_may_contain_cyrillic_letters(): void
    {
        $this->actingAs($this->admin())->post('/settings/users', [
            'name' => 'Өлзий',
            'username' => 'өлзий.бат01',
            'role_id' => Role::where('slug', Role::USER)->value('id'),
            'is_active' => '1',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasNoErrors()->assertRedirect('/settings/users');

        $this->assertDatabaseHas('users', ['username' => 'өлзий.бат01']);

        auth()->logout();
        $this->post('/login', ['username' => 'өлзий.бат01', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_username_rejects_spaces_and_symbols(): void
    {
        $this->actingAs($this->admin())->post('/settings/users', [
            'name' => 'Бат',
            'username' => 'бат болд!',
            'role_id' => Role::where('slug', Role::USER)->value('id'),
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('username');
    }

    public function test_user_position_must_belong_to_selected_department(): void
    {
        $admin = $this->admin();
        $it = Department::create(['name' => 'МТ алба']);
        $finance = Department::create(['name' => 'Санхүү']);
        $engineer = Position::create(['department_id' => $it->id, 'name' => 'Инженер']);
        Position::create(['department_id' => $finance->id, 'name' => 'Нягтлан']);

        $this->actingAs($admin)->get('/settings/users/create')
            ->assertOk()
            ->assertSeeInOrder(['name="department_id"', 'name="position_id"'], false)
            ->assertSee('"department_id":'.$it->id, false);

        $payload = [
            'name' => 'Дорж', 'username' => 'dorj', 'role_id' => Role::where('slug', Role::USER)->value('id'),
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ];

        $this->actingAs($admin)->post('/settings/users', $payload + ['department_id' => $finance->id, 'position_id' => $engineer->id])
            ->assertSessionHasErrors('position_id');
        $this->actingAs($admin)->post('/settings/users', $payload + ['position_id' => $engineer->id])
            ->assertSessionHasErrors('position_id');

        $this->actingAs($admin)->post('/settings/users', $payload + ['department_id' => $it->id, 'position_id' => $engineer->id])
            ->assertRedirect('/settings/users');
        $this->assertSame($engineer->id, User::where('username', 'dorj')->value('position_id'));
    }

    public function test_editing_user_without_password_keeps_old_one(): void
    {
        $user = User::factory()->create(['password' => 'keepme123']);

        $this->actingAs($this->admin())->put("/settings/users/{$user->id}", [
            'name' => 'Шинэ',
            'username' => $user->username,
            'role_id' => $user->role_id,
            'is_active' => '1',
        ])->assertRedirect('/settings/users');

        $this->assertSame('Шинэ', $user->fresh()->name);
        $this->assertTrue(Hash::check('keepme123', $user->fresh()->password));
    }

    public function test_admin_cannot_delete_or_demote_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete("/settings/users/{$admin->id}")->assertSessionHas('error');
        $this->assertModelExists($admin);

        $this->actingAs($admin)->put("/settings/users/{$admin->id}", [
            'name' => $admin->name,
            'username' => $admin->username,
            'role_id' => Role::where('slug', Role::USER)->value('id'),
        ]);
        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_system_role_cannot_be_deleted_or_renamed_slug(): void
    {
        $admin = $this->admin();
        $role = Role::where('slug', Role::ADMIN)->first();

        $this->actingAs($admin)->delete("/settings/roles/{$role->id}")->assertSessionHas('error');
        $this->assertModelExists($role);

        $this->actingAs($admin)->put("/settings/roles/{$role->id}", ['name' => 'Супер админ', 'slug' => 'hacked']);
        $this->assertSame('Супер админ', $role->fresh()->name);
        $this->assertSame('admin', $role->fresh()->slug);
    }

    public function test_deleting_department_detaches_users(): void
    {
        $admin = $this->admin();
        $department = Department::create(['name' => 'Санхүүгийн алба']);
        $user = User::factory()->create(['department_id' => $department->id]);

        $this->actingAs($admin)->get('/settings/departments')->assertSee('Санхүүгийн алба');
        $this->actingAs($admin)->delete("/settings/departments/{$department->id}")
            ->assertRedirect('/settings/departments');

        $this->assertModelMissing($department);
        $this->assertNull($user->fresh()->department_id);
    }

    public function test_crud_positions_and_repair_types(): void
    {
        $admin = $this->admin();

        $it = Department::create(['name' => 'Мэдээллийн технологийн алба']);
        $finance = Department::create(['name' => 'Санхүүгийн хэлтэс']);

        $this->actingAs($admin)->get('/settings/positions/create')
            ->assertOk()
            ->assertSee('name="department_id"', false)
            ->assertSee('Санхүүгийн хэлтэс');

        $this->actingAs($admin)->post('/settings/positions', ['name' => 'Ахлах инженер'])
            ->assertSessionHasErrors('department_id');
        $this->actingAs($admin)->post('/settings/positions', ['department_id' => $it->id, 'name' => 'Ахлах инженер', 'is_active' => '1'])
            ->assertRedirect('/settings/positions');
        $this->actingAs($admin)->post('/settings/positions', ['department_id' => $it->id, 'name' => 'Ахлах инженер'])
            ->assertSessionHasErrors('name');
        // Өөр алба, хэлтэст ижил нэртэй албан тушаал бүртгэж болно
        $this->actingAs($admin)->post('/settings/positions', ['department_id' => $finance->id, 'name' => 'Ахлах инженер'])
            ->assertSessionHasNoErrors();

        $position = Position::where('department_id', $it->id)->firstOrFail();
        $this->actingAs($admin)->get('/settings/positions')->assertSee('Мэдээллийн технологийн алба');
        $this->actingAs($admin)->put("/settings/positions/{$position->id}", ['department_id' => $finance->id, 'name' => 'Ахлах инженер'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)->delete("/settings/departments/{$it->id}")->assertSessionHas('error');
        $this->assertModelExists($it);

        $this->actingAs($admin)->post('/settings/repair-types', ['name' => 'Принтер', 'description' => 'Хэвлэх төхөөрөмж'])
            ->assertRedirect('/settings/repair-types');
        $type = RepairType::where('name', 'Принтер')->firstOrFail();
        $this->assertFalse($type->is_active);

        $this->actingAs($admin)->put("/settings/repair-types/{$type->id}", ['name' => 'Принтер, хувилагч', 'is_active' => '1']);
        $this->assertSame('Принтер, хувилагч', $type->fresh()->name);
        $this->assertTrue($type->fresh()->is_active);

        $this->actingAs($admin)->delete("/settings/repair-types/{$type->id}");
        $this->assertModelMissing($type);
    }

    public function test_crud_repair_categories(): void
    {
        $admin = $this->admin();
        $printer = RepairType::create(['name' => 'Принтер']);
        $network = RepairType::create(['name' => 'Сүлжээ']);

        $this->actingAs($admin)->get('/settings/users')->assertSee('Засвар ангилал');
        $this->actingAs($admin)->get('/settings/repair-categories/create')
            ->assertOk()
            ->assertSee('name="repair_type_id"', false)
            ->assertSee('Сүлжээ');

        $this->actingAs($admin)->post('/settings/repair-categories', ['name' => 'Хор солих'])
            ->assertSessionHasErrors('repair_type_id');
        $this->actingAs($admin)->post('/settings/repair-categories', ['repair_type_id' => $printer->id, 'name' => 'Хор солих', 'is_active' => '1'])
            ->assertRedirect('/settings/repair-categories');
        $this->actingAs($admin)->post('/settings/repair-categories', ['repair_type_id' => $printer->id, 'name' => 'Хор солих'])
            ->assertSessionHasErrors('name');
        $this->actingAs($admin)->post('/settings/repair-categories', ['repair_type_id' => $network->id, 'name' => 'Хор солих'])
            ->assertSessionHasNoErrors();

        $category = RepairCategory::where('repair_type_id', $printer->id)->firstOrFail();
        $this->assertTrue($category->is_active);
        $this->actingAs($admin)->get('/settings/repair-categories')->assertSee('Принтер');

        $this->actingAs($admin)->delete("/settings/repair-types/{$printer->id}")->assertSessionHas('error');
        $this->assertModelExists($printer);

        $this->actingAs($admin)->delete("/settings/repair-categories/{$category->id}");
        $this->assertModelMissing($category);
    }
}
