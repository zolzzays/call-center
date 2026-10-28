<?php

namespace Tests\Feature;

use App\Models\AccessRequest;
use App\Models\AccessRequestType;
use App\Models\AccessType;
use App\Models\Department;
use App\Models\Position;
use App\Models\RepairCategory;
use App\Models\RepairType;
use App\Models\SystemModule;
use App\Models\User;
use App\Notifications\AccessRequestUpdated;
use App\Notifications\AccessReviewDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccessRequestTest extends TestCase
{
    use RefreshDatabase;

    private Department $mode;

    private User $requester;

    private User $head;

    private User $security;

    private User $chief;

    private User $sysadmin;

    private RepairCategory $dis;

    private RepairCategory $vpn;

    private SystemModule $outage;

    private SystemModule $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mode = Department::create(['name' => 'Горим төлөвлөлт, тооцооны алба']);
        $it = Department::create(['name' => 'Мэдээллийн технологийн төв']);
        $board = Department::create(['name' => 'Удирдлага']);

        $person = fn (string $name, Department $d, string $position, ?string $role = null) => User::factory()->create([
            'name' => $name,
            'email' => str()->slug($name).'@example.mn',
            'department_id' => $d->id,
            'position_id' => Position::create(['department_id' => $d->id, 'name' => $position, 'access_role' => $role])->id,
        ]);

        $this->requester = $person('Эрдэнэчимэг', $this->mode, 'Төлөвлөлтийн ахлах инженер');
        $this->head = $person('Дарга Бат', $this->mode, 'Албаны дарга', 'head');
        $this->security = $person('Мөнхбат', $it, 'Мэдээллийн аюулгүй байдлын инженер', 'security');
        $this->chief = $person('Ерөнхий Дорж', $board, 'Ерөнхий диспетчер', 'chief');
        $this->sysadmin = $person('Баттулга', $it, 'Системийн админ', 'system');

        // Системүүд: dis.energy.mn модультай, VPN модульгүй
        $software = RepairType::create(['name' => 'Програм хангамж']);
        $this->dis = RepairCategory::create(['repair_type_id' => $software->id, 'name' => 'dis.energy.mn']);
        $this->vpn = RepairCategory::create(['repair_type_id' => RepairType::create(['name' => 'Сүлжээ'])->id, 'name' => 'VPN']);
        $group = SystemModule::create(['repair_category_id' => $this->dis->id, 'name' => 'Шуурхай ажиллагаа']);
        $this->outage = SystemModule::create(['repair_category_id' => $this->dis->id, 'parent_id' => $group->id, 'name' => 'Тасралтын мэдээ']);
        $this->source = SystemModule::create(['repair_category_id' => $this->dis->id, 'parent_id' => $group->id, 'name' => 'Эх үүсвэр']);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'request_type_id' => AccessRequestType::where('name', 'Шинэ хэрэглэгч үүсгэх')->value('id'),
            'repair_category_id' => $this->dis->id,
            'modules' => [$this->outage->id],
            'access_type_id' => AccessType::where('name', 'Унших, засах')->value('id'),
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
            'reason' => 'Горимын тооцоо оруулах ажлын хүрээнд.',
        ];
    }

    /** Бүх шатаар батлуулж, системийн инженерээр эрх олгуулсан хүсэлт */
    private function grantedRequest(array $overrides = []): AccessRequest
    {
        $this->actingAs($this->requester)->post('/access-requests', $this->payload($overrides));
        $r = AccessRequest::latest('id')->firstOrFail();
        foreach ([$this->head, $this->security, $this->chief] as $approver) {
            $this->actingAs($approver)->put("/access-requests/{$r->id}/approve");
        }
        $this->actingAs($this->sysadmin)->put("/access-requests/{$r->id}/grant", ['account_id' => 'erdenechimeg']);

        return $r->fresh();
    }

    public function test_menu_is_visible_to_everyone(): void
    {
        foreach ([User::factory()->create(), User::factory()->assignee()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get('/tickets')->assertSee('href="'.route('access-requests.index').'"', false)->assertSee('МАБ');
            $this->actingAs($user)->get('/access-requests/create')->assertOk()->assertSee('Хандах эрх хүсэх');
        }
    }

    public function test_full_flow_unit_head_security_chief_then_system_engineer_grants(): void
    {
        Notification::fake();

        // 1. Хүсэлт гаргах
        $this->actingAs($this->requester)->post('/access-requests', $this->payload())->assertSessionHasNoErrors();
        $r = AccessRequest::firstOrFail();
        $this->assertSame('МАБ-'.now()->format('Y').'-0001', $r->number);
        $this->assertSame('pending_head', $r->status);
        Notification::assertSentTo($this->head, AccessRequestUpdated::class, fn ($n) => $n->event === 'pending');

        // Дараагийн шатны хүмүүс одоо батлах эрхгүй; илгээгч өөрөө батлахгүй
        $this->actingAs($this->security)->put("/access-requests/{$r->id}/approve")->assertForbidden();
        $this->actingAs($this->requester)->put("/access-requests/{$r->id}/approve")->assertForbidden();
        $this->actingAs($this->head)->get('/access-requests')->assertSee($r->number)->assertSee('class="nav-count"', false);

        // 2. Нэгжийн удирдлага → 3. МАБ инженер → 4. Ерөнхий диспетчер
        $this->actingAs($this->head)->put("/access-requests/{$r->id}/approve", ['note' => 'Ажлын шаардлагатай.']);
        $this->assertSame('pending_security', $r->fresh()->status);
        Notification::assertSentTo($this->security, AccessRequestUpdated::class, fn ($n) => $n->event === 'pending');

        $this->actingAs($this->security)->put("/access-requests/{$r->id}/approve", ['note' => 'Эрсдэл бага.']);
        $this->assertSame('pending_chief', $r->fresh()->status);
        Notification::assertSentTo($this->chief, AccessRequestUpdated::class, fn ($n) => $n->event === 'pending');

        $this->actingAs($this->chief)->put("/access-requests/{$r->id}/approve");
        $this->assertSame('approved', $r->fresh()->status);
        Notification::assertSentTo($this->requester, AccessRequestUpdated::class, fn ($n) => $n->event === 'approved');
        Notification::assertSentTo($this->sysadmin, AccessRequestUpdated::class, fn ($n) => $n->event === 'grant');

        // 5. Системийн инженер эрх үүсгэнэ; МАБ инженер, Ерөнхий диспетчер олгохгүй
        $this->actingAs($this->security)->put("/access-requests/{$r->id}/grant", ['account_id' => 'x'])->assertForbidden();
        $this->actingAs($this->chief)->put("/access-requests/{$r->id}/grant", ['account_id' => 'x'])->assertForbidden();
        $this->actingAs($this->sysadmin)->get('/access-requests')->assertSee($r->number);
        $this->actingAs($this->sysadmin)->put("/access-requests/{$r->id}/grant", [])->assertSessionHasErrors('account_id');

        $this->actingAs($this->sysadmin)->put("/access-requests/{$r->id}/grant", [
            'account_id' => 'erdenechimeg',
            'temporary_password' => 'Tmp#2026x',
            'instructions' => 'VPN-ээр холбогдоод нэвтэрнэ.',
        ])->assertSessionHasNoErrors();

        $r->refresh();
        $this->assertSame(['granted', 'erdenechimeg'], [$r->status, $r->account_id]);
        $this->assertNotNull($r->granted_at);

        // 6. Хэрэглэгчид ID, түр нууц үг, заавар очно; түр нууц үг системд хадгалагдахгүй
        Notification::assertSentTo($this->requester, AccessRequestUpdated::class, function ($n) {
            $lines = collect($n->toMail($this->requester)->introLines);

            return $n->event === 'granted'
                && $lines->contains('**Хэрэглэгчийн нэр / ID:** erdenechimeg')
                && $lines->contains('**Түр нууц үг:** Tmp#2026x')
                && $lines->contains('**Заавар:** VPN-ээр холбогдоод нэвтэрнэ.');
        });
        $this->assertDatabaseMissing('access_requests', ['instructions' => 'Tmp#2026x']);
        $this->assertDatabaseMissing('access_request_steps', ['note' => 'Tmp#2026x']);

        $this->actingAs($this->requester)->get("/access-requests/{$r->id}")
            ->assertSee('erdenechimeg')
            ->assertDontSee('Tmp#2026x')
            ->assertSeeInOrder(['Илгээсэн', 'Нэгжийн удирдлага · Зөвшөөрсөн', 'МАБ инженер · Зөвшөөрсөн', 'Ерөнхий диспетчер · Зөвшөөрсөн', 'Системийн инженер эрх үүсгэж олгосон', 'МАБ инженер · тогтмол хяналт']);
    }

    public function test_unit_head_stage_is_skipped_when_department_has_no_head(): void
    {
        $this->head->update(['is_active' => false]);

        $this->actingAs($this->requester)->post('/access-requests', $this->payload());
        $r = AccessRequest::firstOrFail();

        $this->assertSame('pending_security', $r->status);
        $this->assertSame(['submitted', 'skipped'], $r->steps->pluck('action')->all());
    }

    public function test_head_only_sees_and_approves_own_department(): void
    {
        $other = User::factory()->create(['department_id' => Department::create(['name' => 'Санхүү'])->id]);
        $otherHead = User::factory()->create([
            'department_id' => $other->department_id,
            'position_id' => Position::create(['department_id' => $other->department_id, 'name' => 'Албаны дарга', 'access_role' => 'head'])->id,
        ]);

        $this->actingAs($this->requester)->post('/access-requests', $this->payload());
        $r = AccessRequest::firstOrFail();

        $this->actingAs($otherHead)->put("/access-requests/{$r->id}/approve")->assertForbidden();
        $this->actingAs($otherHead)->get("/access-requests/{$r->id}")->assertForbidden();
        $this->actingAs($other)->get("/access-requests/{$r->id}")->assertForbidden();
    }

    public function test_rejection_stops_the_flow(): void
    {
        Notification::fake();
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['access_type_id' => AccessType::where('name', 'Админ (бүрэн эрх)')->value('id')]));
        $r = AccessRequest::firstOrFail();
        $this->actingAs($this->head)->put("/access-requests/{$r->id}/approve");

        $this->actingAs($this->security)->put("/access-requests/{$r->id}/reject", ['reject_reason' => ''])
            ->assertSessionHasErrors('reject_reason');
        $this->actingAs($this->security)->put("/access-requests/{$r->id}/reject", ['reject_reason' => 'Админ эрх шаардлагагүй.']);

        $this->assertSame('rejected', $r->fresh()->status);
        Notification::assertSentTo($this->requester, AccessRequestUpdated::class, function ($n) {
            return $n->event === 'rejected'
                && collect($n->toMail($this->requester)->introLines)->contains('**Шалтгаан:** Админ эрх шаардлагагүй.');
        });
        $this->actingAs($this->chief)->put("/access-requests/{$r->id}/approve")->assertForbidden();
    }

    public function test_security_engineer_reviews_and_revokes_access(): void
    {
        $r = $this->grantedRequest();
        Notification::fake();

        // Хяналт, аудит хуудсыг зөвхөн МАБ инженер, админ харна
        $this->actingAs($this->requester)->get('/access-requests/audit')->assertForbidden();
        $this->actingAs($this->sysadmin)->get('/access-requests/audit')->assertForbidden();
        $this->actingAs($this->security)->get('/access-requests/audit')->assertOk()->assertSee($r->number)->assertSee('erdenechimeg');

        // 60 хоног өнгөрөхөд «хянагдаагүй» гэж тэмдэглэгдэнэ, хянасны дараа арилна
        $this->travel(61)->days();
        $this->assertTrue($r->fresh()->needsReview());
        $this->actingAs($this->security)->put("/access-requests/{$r->id}/review");
        $this->assertFalse($r->fresh()->needsReview());

        // Хэрэглэгч өөрөө хянах, цуцлах эрхгүй
        $this->actingAs($this->requester)->put("/access-requests/{$r->id}/revoke", ['revoke_reason' => 'inactive'])->assertForbidden();

        $this->actingAs($this->security)->put("/access-requests/{$r->id}/revoke", ['revoke_reason' => 'inactive', 'revoke_note' => 'Нэвтрээгүй.']);

        $r->refresh();
        $this->assertSame('revoked', $r->status);
        $this->assertSame('60 хоног идэвхгүй: Нэвтрээгүй.', $r->revoke_reason);
        Notification::assertSentTo($this->requester, AccessRequestUpdated::class, fn ($n) => $n->event === 'revoked');
        Notification::assertSentTo($this->sysadmin, AccessRequestUpdated::class, fn ($n) => $n->event === 'disable');

        $this->actingAs($this->security)->get('/access-requests/audit')->assertSee('Эрх цуцалсан')->assertSee('МАБ инженер хянасан');
    }

    public function test_access_is_revoked_automatically_when_user_leaves_or_transfers(): void
    {
        $r = $this->grantedRequest();
        $this->requester->update(['department_id' => Department::create(['name' => 'Техник хяналтын алба'])->id]);

        $this->assertSame('revoked', $r->fresh()->status);
        $this->assertStringContainsString('шилжсэн', $r->fresh()->revoke_reason);

        $second = $this->grantedRequest(['repair_category_id' => $this->vpn->id, 'modules' => []]);
        $this->requester->update(['is_active' => false]);

        $this->assertSame('revoked', $second->fresh()->status);
        $this->assertStringContainsString('Ажлаас гарсан', $second->fresh()->revoke_reason);
        $this->assertNull($second->fresh()->steps->last()->user_id);
    }

    public function test_daily_monitor_expires_reminds_and_sends_review_digest(): void
    {
        $expiring = $this->grantedRequest(['ends_on' => now()->addDays(10)->format('Y-m-d')]);
        $longLived = $this->grantedRequest(['ends_on' => '', 'repair_category_id' => $this->vpn->id, 'modules' => []]);
        Notification::fake();

        $this->travel(7)->months();
        $this->artisan('access:monitor')->assertSuccessful();

        $this->assertSame('expired', $expiring->fresh()->status);
        Notification::assertSentTo($this->requester, AccessRequestUpdated::class, fn ($n) => $n->event === 'expired');

        // 6 сар өнгөрсөн тул нууц үг солих сануулга
        $this->assertNotNull($longLived->fresh()->password_reminded_at);
        Notification::assertSentTo($this->requester, AccessRequestUpdated::class, fn ($n) => $n->event === 'password'
            && $n->request->is($longLived));

        // 60+ хоног хянагдаагүй тул МАБ инженерт жагсаалт
        Notification::assertSentTo($this->security, AccessReviewDigest::class, fn ($n) => $n->requests->contains('id', $longLived->id));

        // Дахин ажиллуулахад сануулга давхардахгүй
        Notification::fake();
        $this->artisan('access:monitor');
        Notification::assertNotSentTo($this->requester, AccessRequestUpdated::class);
    }

    public function test_system_name_and_its_modules_are_selected(): void
    {
        Notification::fake();

        // Маягт: «Системийн нэр» сонголт, систем бүрийн модулиуд
        $this->actingAs($this->requester)->get('/access-requests/create')
            ->assertSee('Системийн нэр')
            ->assertDontSee('Систем, нөөц')
            ->assertSee('<option value="'.$this->dis->id.'"', false)
            ->assertSee('Тасралтын мэдээ');

        // Модультай системд модуль сонгохгүй бол алдаа; өөр системийн модуль болохгүй
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['modules' => []]))
            ->assertSessionHasErrors('modules');
        $other = SystemModule::create(['repair_category_id' => $this->vpn->id, 'name' => 'Алсын холболт']);
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['modules' => [$other->id]]))
            ->assertSessionHasErrors('modules.0');
        $other->delete();

        // Модульгүй систем модульгүйгээр илгээгдэнэ
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['repair_category_id' => $this->vpn->id, 'modules' => []]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['modules' => [$this->outage->id, $this->source->id]]))
            ->assertSessionHasNoErrors();
        $r = AccessRequest::latest('id')->firstOrFail();

        $this->assertSame('dis.energy.mn', $r->system);
        $this->assertSame($this->dis->id, $r->repair_category_id);
        $this->assertSame(['Шуурхай ажиллагаа › Тасралтын мэдээ', 'Шуурхай ажиллагаа › Эх үүсвэр'], $r->moduleNames());

        // Дэлгэрэнгүй, батлагчийн мэйлд модулиуд харагдана
        $this->actingAs($this->requester)->get("/access-requests/{$r->id}")
            ->assertSee('Модулиуд')->assertSee('Шуурхай ажиллагаа › Эх үүсвэр');
        Notification::assertSentTo($this->head, AccessRequestUpdated::class, fn ($n) => $n->request->is($r)
            && collect($n->toMail($this->head)->introLines)->contains('**Модулиуд:** Шуурхай ажиллагаа › Тасралтын мэдээ, Шуурхай ажиллагаа › Эх үүсвэр'));
    }

    public function test_validation(): void
    {
        $this->actingAs($this->requester)->post('/access-requests', [])
            ->assertSessionHasErrors(['request_type_id', 'repair_category_id', 'access_type_id', 'starts_on', 'reason']);

        $this->actingAs($this->requester)->post('/access-requests', $this->payload([
            'starts_on' => now()->format('Y-m-d'), 'ends_on' => now()->subDay()->format('Y-m-d'),
        ]))->assertSessionHasErrors('ends_on');

        // Дуусах огноогүй бол байнгын эрх
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['ends_on' => '']))->assertSessionHasNoErrors();
        $this->assertStringContainsString('Байнгын', AccessRequest::firstOrFail()->periodLabel());
    }

    public function test_admin_manages_access_types_in_settings(): void
    {
        $admin = User::factory()->admin()->create();

        // Анхдагч гурван төрөл бүртгэгдсэн
        $this->actingAs($admin)->get('/settings/access-types')->assertOk()
            ->assertSee('Эрхийн төрөл')->assertSee('Унших')->assertSee('Админ (бүрэн эрх)');

        $this->actingAs($admin)->post('/settings/access-types', ['name' => 'VPN холболт', 'is_active' => '1'])
            ->assertRedirect('/settings/access-types');
        $vpn = AccessType::where('name', 'VPN холболт')->firstOrFail();

        // Шинэ төрөл маягтад гарч, сонгож илгээж болно
        $this->actingAs($this->requester)->get('/access-requests/create')->assertSee('VPN холболт');
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['access_type_id' => $vpn->id]))->assertSessionHasNoErrors();
        $this->assertSame('VPN холболт', AccessRequest::firstOrFail()->accessTypeLabel());

        // Ашиглагдсан төрлийг устгахгүй; идэвхгүй болгосон төрөл маягтад гарахгүй, сонгож болохгүй
        $this->actingAs($admin)->delete("/settings/access-types/{$vpn->id}")->assertSessionHas('error');
        $this->assertModelExists($vpn);

        $this->actingAs($admin)->put("/settings/access-types/{$vpn->id}", ['name' => 'VPN холболт']);
        $this->assertFalse($vpn->fresh()->is_active);
        $this->actingAs($this->requester)->get('/access-requests/create')->assertDontSee('VPN холболт');
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['access_type_id' => $vpn->id]))
            ->assertSessionHasErrors('access_type_id');

        // Энгийн хэрэглэгч тохиргоонд хандахгүй
        $this->actingAs($this->requester)->get('/settings/access-types')->assertForbidden();
    }

    public function test_admin_manages_request_types_in_settings(): void
    {
        $admin = User::factory()->admin()->create();

        // Анхдагч гурван төрөл
        $this->actingAs($admin)->get('/settings/access-request-types')->assertOk()
            ->assertSee('Хүсэлтийн төрөл')
            ->assertSee('Шинэ хэрэглэгч үүсгэх')->assertSee('Хандах эрх өөрчлөх')->assertSee('Хандах эрх цуцлах');

        $this->actingAs($admin)->post('/settings/access-request-types', ['name' => 'Нууц үг сэргээх', 'is_active' => '1'])
            ->assertRedirect('/settings/access-request-types');
        $type = AccessRequestType::where('name', 'Нууц үг сэргээх')->firstOrFail();

        // Маягтад гарч, сонгож илгээнэ; жагсаалт, дэлгэрэнгүйд харагдана
        $this->actingAs($this->requester)->get('/access-requests/create')->assertSee('Нууц үг сэргээх');
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['request_type_id' => $type->id]))->assertSessionHasNoErrors();
        $r = AccessRequest::firstOrFail();
        $this->assertSame('Нууц үг сэргээх', $r->requestTypeLabel());
        $this->actingAs($this->requester)->get('/access-requests')->assertSee('Нууц үг сэргээх');
        $this->actingAs($this->requester)->get("/access-requests/{$r->id}")->assertSee('Нууц үг сэргээх');

        // Ашиглагдсан төрлийг устгахгүй; идэвхгүй бол сонгох боломжгүй
        $this->actingAs($admin)->delete("/settings/access-request-types/{$type->id}")->assertSessionHas('error');
        $this->actingAs($admin)->put("/settings/access-request-types/{$type->id}", ['name' => 'Нууц үг сэргээх']);
        $this->actingAs($this->requester)->get('/access-requests/create')->assertDontSee('Нууц үг сэргээх');
        $this->actingAs($this->requester)->post('/access-requests', $this->payload(['request_type_id' => $type->id]))
            ->assertSessionHasErrors('request_type_id');

        $this->actingAs($this->requester)->get('/settings/access-request-types')->assertForbidden();
    }

    public function test_admin_sets_access_role_on_position(): void
    {
        $admin = User::factory()->admin()->create();
        $position = Position::create(['department_id' => $this->mode->id, 'name' => 'Тооцооны ахлах инженер']);

        $this->actingAs($admin)->put("/settings/positions/{$position->id}", [
            'department_id' => $this->mode->id, 'name' => $position->name, 'access_role' => 'system', 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('system', $position->fresh()->access_role);

        $this->actingAs($admin)->get('/settings/positions')->assertSee('МАБ батлах үүрэг')->assertSee('Системийн инженер');
    }
}
