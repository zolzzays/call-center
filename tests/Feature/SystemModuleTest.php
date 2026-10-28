<?php

namespace Tests\Feature;

use App\Models\RepairCategory;
use App\Models\RepairType;
use App\Models\SystemModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private RepairCategory $dis;

    private RepairCategory $stat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $software = RepairType::create(['name' => 'Програм хангамж']);
        $this->dis = RepairCategory::create(['repair_type_id' => $software->id, 'name' => 'dis.energy.mn']);
        $this->stat = RepairCategory::create(['repair_type_id' => $software->id, 'name' => 'stat.energy.mn']);
        RepairCategory::create(['repair_type_id' => RepairType::create(['name' => 'Скада'])->id, 'name' => 'Свич']);
    }

    private function store(array $data)
    {
        return $this->actingAs($this->admin)->post('/settings/system-modules', $data + ['is_active' => '1']);
    }

    public function test_admin_registers_groups_and_modules_per_system(): void
    {
        // Зөвхөн програм хангамжийн системүүдийг сонгоно
        $this->actingAs($this->admin)->get('/settings/system-modules')
            ->assertOk()->assertSee('dis.energy.mn')->assertSee('stat.energy.mn')->assertDontSee('Свич');

        $this->store(['repair_category_id' => $this->dis->id, 'name' => 'Шуурхай ажиллагаа'])->assertSessionHasNoErrors();
        $group = SystemModule::where('name', 'Шуурхай ажиллагаа')->firstOrFail();

        $this->store(['repair_category_id' => $this->dis->id, 'parent_id' => $group->id, 'name' => 'Тасралтын мэдээ'])
            ->assertSessionHasNoErrors();
        $this->store(['repair_category_id' => $this->dis->id, 'parent_id' => $group->id, 'name' => 'Эх үүсвэр'])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Тасралтын мэдээ', 'Эх үүсвэр'], $group->children()->orderBy('id')->pluck('name')->all());

        // Жагсаалт: систем → бүлэг → модулиуд
        $this->actingAs($this->admin)->get("/settings/system-modules?system={$this->dis->id}")
            ->assertOk()
            ->assertSeeInOrder(['Шуурхай ажиллагаа', 'Тасралтын мэдээ', 'Эх үүсвэр']);
    }

    public function test_selecting_system_shows_only_its_modules(): void
    {
        $disGroup = SystemModule::create(['repair_category_id' => $this->dis->id, 'name' => 'Шуурхай ажиллагаа']);
        SystemModule::create(['repair_category_id' => $this->dis->id, 'parent_id' => $disGroup->id, 'name' => 'Тасралтын мэдээ']);
        $statGroup = SystemModule::create(['repair_category_id' => $this->stat->id, 'name' => 'Статистик тайлан']);

        // dis.energy.mn сонгоход зөвхөн түүний модулиуд
        $this->actingAs($this->admin)->get("/settings/system-modules?system={$this->dis->id}")
            ->assertOk()
            ->assertSeeInOrder(['Шуурхай ажиллагаа', 'Тасралтын мэдээ'])
            ->assertDontSee('Статистик тайлан');

        // stat.energy.mn сонгоход өөр модулиуд
        $this->actingAs($this->admin)->get("/settings/system-modules?system={$this->stat->id}")
            ->assertSee('Статистик тайлан')->assertDontSee('Тасралтын мэдээ');

        // Модульгүй систем (consumer) сонговол шинээр бүртгэхийг санал болгоно
        $software = RepairType::where('name', 'Програм хангамж')->first();
        $consumer = RepairCategory::create(['repair_type_id' => $software->id, 'name' => 'consumer.energy.mn']);
        $this->actingAs($this->admin)->get("/settings/system-modules?system={$consumer->id}")
            ->assertSee('consumer.energy.mn системд модуль бүртгэгдээгүй байна')
            ->assertSee(route('settings.system-modules.create', ['system' => $consumer->id]), false);

        // Нэмэх маягт тухайн систем, бүлгээр урьдчилан бөглөгдөж, зөвхөн тэр системийн бүлгүүд гарна
        $this->actingAs($this->admin)->get("/settings/system-modules/create?system={$this->dis->id}&parent={$disGroup->id}")
            ->assertOk()
            ->assertSee('<option value="'.$this->dis->id.'" selected', false)
            ->assertSee('<option value="'.$disGroup->id.'" selected', false)
            ->assertDontSee('stat.energy.mn')
            ->assertDontSee('Статистик тайлан');

        // Хадгалсны дараа тухайн системийн жагсаалт руу буцна
        $this->actingAs($this->admin)->post('/settings/system-modules', [
            'repair_category_id' => $consumer->id, 'name' => 'Хэрэглэгчийн бүртгэл', 'is_active' => '1',
        ])->assertRedirect(route('settings.system-modules.index', ['system' => $consumer->id]));
        $this->assertSame(1, $consumer->modules()->count());
    }

    public function test_module_rules(): void
    {
        $group = SystemModule::create(['repair_category_id' => $this->dis->id, 'name' => 'Горим төлөвлөлт']);
        $module = SystemModule::create(['repair_category_id' => $this->dis->id, 'parent_id' => $group->id, 'name' => 'Дулааны горим']);

        // Давхардсан нэр (нэг бүлэгт) болохгүй; өөр бүлэгт ижил нэр болно
        $this->store(['repair_category_id' => $this->dis->id, 'parent_id' => $group->id, 'name' => 'Дулааны горим'])
            ->assertSessionHasErrors('name');
        $other = SystemModule::create(['repair_category_id' => $this->dis->id, 'name' => 'Шуурхай ажиллагаа']);
        $this->store(['repair_category_id' => $this->dis->id, 'parent_id' => $other->id, 'name' => 'Дулааны горим'])
            ->assertSessionHasNoErrors();

        // Өөр системийн бүлгийг сонгох, модулийн доор модуль нэмэх боломжгүй
        $this->store(['repair_category_id' => $this->stat->id, 'parent_id' => $group->id, 'name' => 'X'])
            ->assertSessionHasErrors('parent_id');
        $this->store(['repair_category_id' => $this->dis->id, 'parent_id' => $module->id, 'name' => 'Y'])
            ->assertSessionHasErrors('parent_id');

        // Модультай бүлэг, системийг устгахгүй
        $this->actingAs($this->admin)->delete("/settings/system-modules/{$group->id}")->assertSessionHas('error');
        $this->actingAs($this->admin)->delete("/settings/repair-categories/{$this->dis->id}")->assertSessionHas('error');
        $this->assertModelExists($group);

        // Энгийн хэрэглэгч хандахгүй
        $this->actingAs(User::factory()->create())->get('/settings/system-modules')->assertForbidden();
    }

    public function test_settings_menu_has_system_modules(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertSee('Системийн модуль')
            ->assertSee('href="'.route('settings.system-modules.index').'"', false);
    }
}
