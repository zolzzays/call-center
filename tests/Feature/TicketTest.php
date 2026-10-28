<?php

namespace Tests\Feature;

use App\Models\RepairCategory;
use App\Models\RepairType;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketResubmitted;
use App\Notifications\TicketStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private RepairType $software;

    private RepairCategory $system;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->software = RepairType::create(['name' => 'Програм хангамж']);
        $this->system = RepairCategory::create(['repair_type_id' => $this->software->id, 'name' => 'dis.energy.mn']);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'repair',
            'repair_type_id' => $this->software->id,
            'repair_category_id' => $this->system->id,
            'description' => 'Нэвтрэх үед 500 алдаа гарч байна.',
            'urgency' => 'urgent',
        ];
    }

    public function test_create_form_shows_categories_and_systems(): void
    {
        $this->actingAs(User::factory()->create())->get('/tickets/create')
            ->assertOk()
            ->assertSee('Засварын дуудлага')
            ->assertSee('Нэмэлт, өөрчлөлтийн хүсэлт')
            ->assertSee('Програм хангамж')
            ->assertSee('dis.energy.mn')
            ->assertSee('Нэн яаралтай');
    }

    public function test_submission_date_is_filled_automatically(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(9, 15));
        $user = User::factory()->create();

        $this->actingAs($user)->get('/tickets/create')
            ->assertSee('Илгээх огноо')
            ->assertSee('value="2026-10-26 09:15" readonly', false)
            ->assertDontSee('Хүссэн хугацаа')
            ->assertDontSee('name="due_at"', false);

        // Маягтаас ирсэн огноог үл тооцож серверийн цагаар бүртгэнэ
        $this->travel(3)->minutes();
        $this->actingAs($user)->post('/tickets', $this->payload([
            'sent_at' => '2020-01-01 00:00', 'created_at' => '2020-01-01 00:00', 'due_at' => '2026-12-01T10:00',
        ]))->assertSessionHasNoErrors();

        $ticket = Ticket::firstOrFail();
        $this->assertSame('2026-10-26 09:18', $ticket->created_at->format('Y-m-d H:i'));
        // Хүссэн хугацааг маягтаас хассан тул илгээсэн ч хадгалахгүй
        $this->assertNull($ticket->due_at);
    }

    public function test_submit_link_is_on_my_requests_page_not_in_menu(): void
    {
        $user = User::factory()->create();
        $create = route('tickets.create');

        $this->actingAs($user)->get('/tickets')
            ->assertSee('<a href="'.$create.'" class="btn">+ Хүсэлт илгээх</a>', false)
            ->assertDontSee('href="'.$create.'" class="nav-item', false);

        // Маягт дээр байхад «Миний хүсэлтүүд» цэс идэвхтэй
        $this->actingAs($user)->get('/tickets/create')
            ->assertSee('href="'.route('tickets.index').'" class="nav-item active"', false);
    }

    public function test_user_submits_ticket_with_files_and_gets_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tickets', $this->payload([
            'attachments' => [
                UploadedFile::fake()->image('screen.png'),
                UploadedFile::fake()->create('log.pdf', 200, 'application/pdf'),
            ],
        ]));

        $ticket = Ticket::firstOrFail();
        $response->assertRedirect("/tickets/{$ticket->id}")->assertSessionHas('status');

        $this->assertSame(now()->format('Y').'-00001', $ticket->number);
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame('new', $ticket->status);
        $this->assertSame('urgent', $ticket->urgency);
        $this->assertCount(2, $ticket->attachments);
        Storage::disk('local')->assertExists($ticket->attachments->pluck('path')->all());

        $this->actingAs($user)->get("/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSee($ticket->number)
            ->assertSee('screen.png')
            ->assertSee('dis.energy.mn');

        $file = $ticket->attachments->first();
        $this->actingAs($user)->get("/tickets/{$ticket->id}/attachments/{$file->id}")->assertOk();
    }

    public function test_numbers_increment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tickets', $this->payload());
        $this->actingAs($user)->post('/tickets', $this->payload(['type' => 'change', 'urgency' => 'normal']));

        $this->assertSame(
            [now()->format('Y').'-00001', now()->format('Y').'-00002'],
            Ticket::orderBy('id')->pluck('number')->all()
        );
    }

    public function test_validation(): void
    {
        $user = User::factory()->create();
        $other = RepairType::create(['name' => 'Сүлжээ']);
        $otherSystem = RepairCategory::create(['repair_type_id' => $other->id, 'name' => 'Свич']);

        $this->actingAs($user)->post('/tickets', [])
            ->assertSessionHasErrors(['type', 'repair_type_id', 'description', 'urgency']);

        // Ангилалд систем бүртгэлтэй бол заавал сонгоно
        $this->actingAs($user)->post('/tickets', $this->payload(['repair_category_id' => '']))
            ->assertSessionHasErrors('repair_category_id');

        // Өөр ангиллын систем сонгож болохгүй
        $this->actingAs($user)->post('/tickets', $this->payload(['repair_category_id' => $otherSystem->id]))
            ->assertSessionHasErrors('repair_category_id');

        $this->actingAs($user)->post('/tickets', $this->payload(['urgency' => 'asap']))
            ->assertSessionHasErrors('urgency');

        $this->actingAs($user)->post('/tickets', $this->payload(['attachments' => [UploadedFile::fake()->create('virus.exe', 10)]]))
            ->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, Ticket::count());
    }

    public function test_system_is_optional_when_category_has_none(): void
    {
        $hardware = RepairType::create(['name' => 'Техник хангамж']);

        $this->actingAs(User::factory()->create())
            ->post('/tickets', $this->payload(['repair_type_id' => $hardware->id, 'repair_category_id' => '', 'due_at' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull(Ticket::firstOrFail()->repair_category_id);
    }

    public function test_users_see_only_own_tickets_and_admin_sees_all(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $this->actingAs($owner)->post('/tickets', $this->payload(['attachments' => [UploadedFile::fake()->image('a.png')]]));
        $ticket = Ticket::firstOrFail();
        $file = $ticket->attachments->first();

        $this->actingAs($stranger)->get("/tickets/{$ticket->id}")->assertForbidden();
        $this->actingAs($stranger)->get("/tickets/{$ticket->id}/attachments/{$file->id}")->assertForbidden();
        $this->actingAs($stranger)->get('/tickets')->assertOk()->assertDontSee($ticket->number);

        $this->actingAs($owner)->get('/tickets')->assertSee($ticket->number);
        $this->actingAs(User::factory()->admin()->create())->get('/tickets')
            ->assertSee($ticket->number)
            ->assertSee($owner->name);
    }

    public function test_index_filters_by_urgency_and_shows_counts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/tickets', $this->payload(['urgency' => 'critical']));
        $this->actingAs($user)->post('/tickets', $this->payload(['urgency' => 'normal']));
        [$critical, $normal] = Ticket::orderBy('id')->pluck('number')->all();

        $this->actingAs($user)->get('/tickets')->assertOk()->assertSee($critical)->assertSee($normal);

        $this->actingAs($user)->get('/tickets?urgency=critical')
            ->assertOk()
            ->assertSee($critical)
            ->assertDontSee($normal)
            ->assertSee('Шүүлтийг цэвэрлэх');

        // Буруу утгыг үл тоомсорлоно
        $this->actingAs($user)->get('/tickets?urgency=bogus')->assertOk()->assertSee($normal);
    }

    public function test_assignee_cannot_submit_tickets(): void
    {
        $assignee = User::factory()->assignee()->create();

        $this->actingAs($assignee)->get('/tickets/create')->assertForbidden();
        $this->actingAs($assignee)->post('/tickets', $this->payload())->assertForbidden();
        $this->assertSame(0, Ticket::count());

        $this->actingAs($assignee)->get('/tickets')
            ->assertOk()
            ->assertSee('Ирсэн хүсэлт')
            ->assertDontSee('href="'.route('tickets.create').'"', false);
    }

    public function test_admin_assigns_and_assignee_sees_only_own(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $mine = User::factory()->assignee()->create(['name' => 'Инженер Бат']);
        $other = User::factory()->assignee()->create();

        $this->actingAs($reporter)->post('/tickets', $this->payload());
        $this->actingAs($reporter)->post('/tickets', $this->payload());
        [$first, $second] = Ticket::orderBy('id')->get()->all();

        $this->actingAs($admin)->get("/tickets/{$first->id}")->assertOk()->assertSee('Инженер Бат');

        $this->actingAs($admin)->put("/tickets/{$first->id}/assign", ['assignee_id' => $mine->id])
            ->assertSessionHas('status');
        $this->actingAs($admin)->put("/tickets/{$second->id}/assign", ['assignee_id' => $other->id]);

        $first->refresh();
        $this->assertSame($mine->id, $first->assignee_id);
        $this->assertSame('assigned', $first->status);
        $this->assertNotNull($first->assigned_at);

        // Админы хүсэлтээс үлдсэн flash мэдэгдлийг цэвэрлэнэ
        $this->flushSession();
        $this->actingAs($mine)->get('/tickets')->assertSee($first->number)->assertDontSee($second->number);
        $this->actingAs($mine)->get("/tickets/{$first->id}")->assertOk();
        $this->actingAs($mine)->get("/tickets/{$second->id}")->assertForbidden();

        // Илгээгч өөрийн дуудлагын гүйцэтгэгчийг харна
        $this->actingAs($reporter)->get('/tickets')->assertSee('Инженер Бат');
    }

    public function test_only_admin_can_assign_and_only_to_assignees(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $assignee = User::factory()->assignee()->create();
        $inactive = User::factory()->assignee()->create(['is_active' => false]);

        $this->actingAs($reporter)->post('/tickets', $this->payload());
        $ticket = Ticket::firstOrFail();

        $this->actingAs($reporter)->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $assignee->id])->assertForbidden();
        $this->actingAs($assignee)->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $assignee->id])->assertForbidden();

        $this->actingAs($admin)->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $reporter->id])
            ->assertSessionHasErrors('assignee_id');
        $this->actingAs($admin)->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $inactive->id])
            ->assertSessionHasErrors('assignee_id');

        $this->assertNull($ticket->fresh()->assignee_id);
    }

    public function test_category_assignee_gets_ticket_and_email_automatically(): void
    {
        Notification::fake();
        $engineer = User::factory()->assignee()->create(['name' => 'Инженер Дорж', 'email' => 'dorj@example.mn']);
        $this->system->update(['assignee_id' => $engineer->id]);

        $this->actingAs(User::factory()->create())->get('/tickets/create')
            ->assertOk()
            ->assertSee('Инженер Дорж');

        $this->actingAs(User::factory()->create())->post('/tickets', $this->payload())
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'Инженер Дорж'));

        $ticket = Ticket::firstOrFail();
        $this->assertSame($engineer->id, $ticket->assignee_id);
        $this->assertSame('assigned', $ticket->status);

        Notification::assertSentTo($engineer, TicketAssigned::class, function (TicketAssigned $n) use ($ticket, $engineer) {
            $mail = $n->toMail($engineer);

            return $n->ticket->is($ticket)
                && str_contains($mail->subject, $ticket->number)
                && $mail->actionUrl === route('tickets.show', $ticket);
        });
    }

    public function test_no_auto_assignment_without_active_category_assignee(): void
    {
        Notification::fake();
        $inactive = User::factory()->assignee()->create(['is_active' => false]);
        $this->system->update(['assignee_id' => $inactive->id]);

        $this->actingAs(User::factory()->create())->post('/tickets', $this->payload());

        $ticket = Ticket::firstOrFail();
        $this->assertNull($ticket->assignee_id);
        $this->assertSame('new', $ticket->status);
        Notification::assertNothingSent();
    }

    public function test_manual_assignment_sends_email(): void
    {
        Notification::fake();
        $engineer = User::factory()->assignee()->create(['email' => 'eng@example.mn']);
        $this->actingAs(User::factory()->create())->post('/tickets', $this->payload());
        $ticket = Ticket::firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $engineer->id])
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'Мэйлээр мэдэгдлээ'));

        Notification::assertSentTo($engineer, TicketAssigned::class);
    }

    public function test_admin_sets_category_assignee_in_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $engineer = User::factory()->assignee()->create(['name' => 'Инженер Сүх']);
        $regular = User::factory()->create();

        $this->actingAs($admin)->get("/settings/repair-categories/{$this->system->id}/edit")
            ->assertOk()
            ->assertSee('Инженер Сүх')
            ->assertDontSee($regular->name);

        $base = ['repair_type_id' => $this->software->id, 'name' => $this->system->name, 'is_active' => '1'];

        $this->actingAs($admin)->put("/settings/repair-categories/{$this->system->id}", $base + ['assignee_id' => $regular->id])
            ->assertSessionHasErrors('assignee_id');

        $this->actingAs($admin)->put("/settings/repair-categories/{$this->system->id}", $base + ['assignee_id' => $engineer->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($engineer->id, $this->system->fresh()->assignee_id);

        $this->actingAs($admin)->get('/settings/repair-categories')->assertSee('Инженер Сүх');

        // Гүйцэтгэгчгүй болгож болно
        $this->actingAs($admin)->put("/settings/repair-categories/{$this->system->id}", $base + ['assignee_id' => '']);
        $this->assertNull($this->system->fresh()->assignee_id);
    }

    public function test_category_with_two_assignees_shares_ticket(): void
    {
        Notification::fake();
        $bat = User::factory()->assignee()->create(['name' => 'Инженер Бат', 'email' => 'bat@example.mn']);
        $dorj = User::factory()->assignee()->create(['name' => 'Инженер Дорж', 'email' => 'dorj@example.mn']);
        $other = User::factory()->assignee()->create();
        $this->system->update(['assignee_id' => $bat->id, 'co_assignee_id' => $dorj->id]);

        $this->actingAs(User::factory()->create())->get('/tickets/create')->assertSee('Инженер Бат, Инженер Дорж');

        $reporter = User::factory()->create(['email' => 'reporter@example.mn']);
        $this->actingAs($reporter)->post('/tickets', $this->payload())
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'Гүйцэтгэгч: Инженер Бат, Инженер Дорж.'));

        $ticket = Ticket::firstOrFail();
        $this->assertSame([$bat->id, $dorj->id], [$ticket->assignee_id, $ticket->co_assignee_id]);

        // Хоёуланд нь мэйл очно
        Notification::assertSentTo($bat, TicketAssigned::class);
        Notification::assertSentTo($dorj, TicketAssigned::class);
        Notification::assertNotSentTo($other, TicketAssigned::class);

        // Хоёулаа «Ирсэн хүсэлт»-дээ харж, нээж чадна; бусад гүйцэтгэгч харахгүй
        foreach ([$bat, $dorj] as $engineer) {
            $this->actingAs($engineer)->get('/tickets')->assertSee($ticket->number);
            $this->actingAs($engineer)->get("/tickets/{$ticket->id}")->assertOk()->assertSee('Инженер Бат, Инженер Дорж');
        }
        $this->actingAs($other)->get('/tickets')->assertDontSee($ticket->number);
        $this->actingAs($other)->get("/tickets/{$ticket->id}")->assertForbidden();

        // Хамтран гүйцэтгэгч хойшлуулж, хааж чадна
        $this->actingAs($dorj)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'reason' => 'Сэлбэг хүлээж байна.',
        ])->assertSessionHasNoErrors();
        $this->actingAs($dorj)->put("/tickets/{$ticket->id}/close", ['resolution' => 'Сервер дахин ачаалсан.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('closed', $ticket->fresh()->status);

        // Хаасан гүйцэтгэгчийн нэр гарна
        Notification::assertSentTo($reporter, TicketStatusChanged::class, function (TicketStatusChanged $n) use ($reporter) {
            return collect($n->toMail($reporter)->introLines)->contains('**Гүйцэтгэгч:** Инженер Дорж');
        });
        $this->assertSame($dorj->id, $ticket->fresh()->handled_by_id);
        $this->actingAs($reporter)->get('/tickets?status=all')->assertSee('Инженер Дорж')->assertDontSee('Инженер Бат, Инженер Дорж');
        $this->actingAs($reporter)->get("/tickets/{$ticket->id}")->assertSee('Хуваарилсан: Инженер Бат, Инженер Дорж');
    }

    public function test_handler_is_the_assignee_who_acted_last(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $bat = User::factory()->assignee()->create(['name' => 'Инженер Бат']);
        $dorj = User::factory()->assignee()->create(['name' => 'Инженер Дорж']);
        $this->system->update(['assignee_id' => $bat->id, 'co_assignee_id' => $dorj->id]);
        $reporter = User::factory()->create();
        $this->actingAs($reporter)->post('/tickets', $this->payload());
        $ticket = Ticket::firstOrFail();

        // Хуваарилагдсан даруйд хоёр нэр
        $this->assertSame('Инженер Бат, Инженер Дорж', $ticket->fresh()->assigneeDisplay());

        // Бат хойшлуулбал Бат, Дорж буцаавал Дорж
        $this->actingAs($bat)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => now()->addDay()->format('Y-m-d\TH:i'), 'reason' => 'Сэлбэг хүлээнэ.',
        ]);
        $this->assertSame('Инженер Бат', $ticket->fresh()->assigneeDisplay());

        $this->actingAs($dorj)->put("/tickets/{$ticket->id}/return", ['return_reason' => 'Зураг хавсаргана уу.']);
        $this->assertSame('Инженер Дорж', $ticket->fresh()->assigneeDisplay());
        Notification::assertSentTo($reporter, TicketStatusChanged::class, fn (TicketStatusChanged $n) => $n->ticket->isReturned()
            && collect($n->toMail($reporter)->introLines)->contains('**Гүйцэтгэгч:** Инженер Дорж'));

        // Админ хойшлуулбал гүйцэтгэгчийн нэр өөрчлөгдөхгүй
        $this->actingAs($reporter)->put("/tickets/{$ticket->id}/resubmit", ['clarification' => 'Хавсаргалаа.']);
        $this->actingAs($admin)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => now()->addDay()->format('Y-m-d\TH:i'), 'reason' => 'Админ хойшлуулав.',
        ]);
        $this->assertSame($dorj->id, $ticket->fresh()->handled_by_id);

        // Гүйцэтгэлийг бичиж хаасан хүний нэр (админ ч байсан) «Миний хүсэлтүүд»-д гарна
        $admin->update(['name' => 'Админ Сараа']);
        $this->actingAs($admin)->put("/tickets/{$ticket->id}/close", ['resolution' => 'Админ хаав.']);
        $this->assertSame($admin->id, $ticket->fresh()->handled_by_id);
        $this->actingAs($reporter)->get('/tickets?status=closed')->assertSee('Админ Сараа');
    }

    public function test_reassigning_clears_handler(): void
    {
        $admin = User::factory()->admin()->create();
        [$ticket, , $engineer] = $this->assignedTicket();
        $other = User::factory()->assignee()->create(['name' => 'Инженер Сүх']);

        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => now()->addDay()->format('Y-m-d\TH:i'), 'reason' => 'Хүлээнэ.',
        ]);
        $this->assertSame($engineer->id, $ticket->fresh()->handled_by_id);

        $this->actingAs($admin)->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $other->id]);
        $this->assertNull($ticket->fresh()->handled_by_id);
        $this->assertSame('Инженер Сүх', $ticket->fresh()->assigneeDisplay());
    }

    public function test_inactive_main_assignee_falls_back_to_co_assignee(): void
    {
        $inactive = User::factory()->assignee()->create(['is_active' => false]);
        $dorj = User::factory()->assignee()->create();
        $this->system->update(['assignee_id' => $inactive->id, 'co_assignee_id' => $dorj->id]);

        $this->actingAs(User::factory()->create())->post('/tickets', $this->payload());

        $ticket = Ticket::firstOrFail();
        $this->assertSame([$dorj->id, null], [$ticket->assignee_id, $ticket->co_assignee_id]);
    }

    public function test_admin_assigns_two_assignees_manually_and_in_settings(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $bat = User::factory()->assignee()->create(['name' => 'Инженер Бат']);
        $dorj = User::factory()->assignee()->create(['name' => 'Инженер Дорж']);
        $this->actingAs(User::factory()->create())->post('/tickets', $this->payload());
        $ticket = Ticket::firstOrFail();

        $this->actingAs($admin)->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $bat->id, 'co_assignee_id' => $bat->id])
            ->assertSessionHasErrors('co_assignee_id');

        $this->actingAs($admin)->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $bat->id, 'co_assignee_id' => $dorj->id])
            ->assertSessionHas('status', fn ($m) => str_contains($m, 'Инженер Бат, Инженер Дорж-д хуваариллаа'));
        Notification::assertSentTo([$bat, $dorj], TicketAssigned::class);

        // Хамтран гүйцэтгэгчгүйгээр дахин хуваарилбал хасагдана
        $this->actingAs($admin)->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $dorj->id, 'co_assignee_id' => '']);
        $this->assertSame([$dorj->id, null], [$ticket->fresh()->assignee_id, $ticket->fresh()->co_assignee_id]);

        // Тохиргоонд ангилалд хоёр гүйцэтгэгч оноох
        $base = ['repair_type_id' => $this->software->id, 'name' => $this->system->name, 'is_active' => '1', 'assignee_id' => $bat->id];
        $this->actingAs($admin)->put("/settings/repair-categories/{$this->system->id}", $base + ['co_assignee_id' => $bat->id])
            ->assertSessionHasErrors('co_assignee_id');
        $this->actingAs($admin)->put("/settings/repair-categories/{$this->system->id}", $base + ['co_assignee_id' => $dorj->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($dorj->id, $this->system->fresh()->co_assignee_id);
        $this->actingAs($admin)->get('/settings/repair-categories')->assertSee('Хамтран гүйцэтгэгч')->assertSee('Инженер Дорж');
    }

    /** Илгээгч + гүйцэтгэгчтэй хуваарилагдсан дуудлага үүсгэнэ */
    private function assignedTicket(): array
    {
        $reporter = User::factory()->create(['email' => 'reporter@example.mn']);
        $engineer = User::factory()->assignee()->create(['email' => 'eng@example.mn']);
        $this->system->update(['assignee_id' => $engineer->id]);

        $this->actingAs($reporter)->post('/tickets', $this->payload());
        $this->flushSession();

        return [Ticket::firstOrFail(), $reporter, $engineer];
    }

    public function test_description_is_right_below_ticket_number(): void
    {
        [$ticket, $reporter, $engineer] = $this->assignedTicket();
        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => now()->addDay()->format('Y-m-d\TH:i'), 'reason' => 'Сэлбэг хүлээнэ.',
        ]);

        // Хүсэлтийн дугаар → тайлбар → хойшлуулсан мэдэгдэл (илгээгч, гүйцэтгэгч хоёуланд)
        foreach ([$reporter, $engineer] as $user) {
            $html = $this->actingAs($user)->get("/tickets/{$ticket->id}")
                ->assertSeeInOrder(['Хүсэлтийн дугаар', '№'.$ticket->number, 'Гэмтэл, хүсэлтийн тайлбар', $ticket->description, 'хүртэл хойшлуулсан'])
                ->getContent();
            $this->assertSame(1, substr_count($html, 'Гэмтэл, хүсэлтийн тайлбар'));
        }
    }

    public function test_assignee_returns_ticket_and_reporter_resubmits(): void
    {
        Notification::fake();
        [$ticket, $reporter, $engineer] = $this->assignedTicket();

        // Гурван үйлдэл: хаах, хойшлуулах, буцаах
        $this->actingAs($engineer)->get("/tickets/{$ticket->id}")
            ->assertSeeInOrder(['Хаах', 'Хойшлуулах', 'Буцаах']);

        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/return", ['return_reason' => ''])
            ->assertSessionHasErrors('return_reason');

        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/return", ['return_reason' => 'Алдааны зургийг хавсаргана уу.'])
            ->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame('returned', $ticket->status);
        $this->assertSame('Буцаасан', $ticket->statusLabel());
        $this->assertSame($engineer->id, $ticket->assignee_id);

        Notification::assertSentTo($reporter, TicketStatusChanged::class, function (TicketStatusChanged $n) use ($reporter) {
            $mail = $n->toMail($reporter);

            return str_contains($mail->subject, 'тодруулга авахаар танд буцаагдлаа')
                && collect($mail->introLines)->contains('**Шалтгаан:** Алдааны зургийг хавсаргана уу.');
        });

        // Буцаасан хүсэлт нээлттэй хэвээр; гүйцэтгэгчид «Хүсэлт гүйцэтгэх» гарахгүй, «Буцаасан» гэж харагдана
        $this->assertContains('returned', Ticket::OPEN_STATUSES);
        $this->actingAs($engineer)->get("/tickets/{$ticket->id}")
            ->assertDontSee('Хүсэлт гүйцэтгэх')
            ->assertDontSee('id="pane-close"', false)
            ->assertSee('Илгээгчийн тодруулгыг хүлээж байна');

        // Тодруулга ирэх хүртэл хаах, хойшлуулах, дахин буцаах боломжгүй
        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/return", ['return_reason' => 'Дахин'])->assertForbidden();
        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/close", ['resolution' => 'Хаав'])->assertForbidden();
        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => now()->addDay()->format('Y-m-d\TH:i'), 'reason' => 'x',
        ])->assertForbidden();

        // Илгээгч шалтгааныг харж тодруулна; бусад хүн дахин илгээж чадахгүй
        $this->actingAs($reporter)->get("/tickets/{$ticket->id}")
            ->assertSee('Тодруулга авахаар танд буцаасан')
            ->assertSee('Алдааны зургийг хавсаргана уу.')
            ->assertSee('Дахин илгээх');
        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/resubmit", ['clarification' => 'x'])->assertForbidden();

        $this->actingAs($reporter)->put("/tickets/{$ticket->id}/resubmit", ['clarification' => 'Зургийг хавсаргалаа, 3-р давхрын компьютер.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('assigned', $ticket->fresh()->status);
        Notification::assertSentTo($engineer, TicketResubmitted::class, function (TicketResubmitted $n) use ($engineer) {
            return collect($n->toMail($engineer)->introLines)->contains('**Тодруулга:** Зургийг хавсаргалаа, 3-р давхрын компьютер.');
        });

        // Тодруулга ирсний дараа дахин гүйцэтгэх боломжтой; явцад буцаасан, дахин илгээсэн нь харагдана
        $this->actingAs($engineer)->get("/tickets/{$ticket->id}")
            ->assertSee('Хүсэлт гүйцэтгэх')
            ->assertSeeInOrder(['Илгээгчид буцаасан', 'Тодруулж дахин илгээсэн']);
    }

    public function test_my_requests_shows_returned_tickets_to_reporter(): void
    {
        [$ticket, $reporter, $engineer] = $this->assignedTicket();

        $this->actingAs($reporter)->get('/tickets')->assertDontSee('гүйцэтгэгч буцаасан байна');

        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/return", ['return_reason' => 'Зураг хавсаргана уу.']);

        // Жагсаалтын дээр мэдэгдэл, мөрөнд «Тодруулга шаардлагатай», «Буцаасан» таб
        $this->actingAs($reporter)->get('/tickets')
            ->assertSee('Таны 1 хүсэлтийг гүйцэтгэгч буцаасан байна.')
            ->assertSee('Тодруулга шаардлагатай')
            ->assertSee('Буцаасан');

        $this->actingAs($reporter)->get('/tickets?status=returned')->assertOk()->assertSee($ticket->number);
        $this->assertSame(1, $this->actingAs($reporter)->get('/tickets?status=returned')->viewData('tickets')->total());

        // Гүйцэтгэгчид илгээгчийн мэдэгдэл гарахгүй
        $this->actingAs($engineer)->get('/tickets')->assertDontSee('гүйцэтгэгч буцаасан байна')->assertDontSee('Тодруулга шаардлагатай');

        // Дахин илгээсний дараа мэдэгдэл алга болно
        $this->actingAs($reporter)->put("/tickets/{$ticket->id}/resubmit", ['clarification' => 'Хавсаргалаа.']);
        $this->actingAs($reporter)->get('/tickets')->assertDontSee('гүйцэтгэгч буцаасан байна');
    }

    public function test_assignee_closes_ticket_and_reporter_is_notified(): void
    {
        Notification::fake();
        [$ticket, $reporter, $engineer] = $this->assignedTicket();

        // Гүйцэтгэгч эхлээд гэмтлийн тайлбарыг уншаад, доор нь гүйцэтгэх хэсгийг харна
        $this->actingAs($engineer)->get("/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSeeInOrder(['Гэмтэл, хүсэлтийн тайлбар', $ticket->description, 'Хүсэлт гүйцэтгэх']);

        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/close", ['resolution' => ''])
            ->assertSessionHasErrors('resolution');

        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/close", ['resolution' => 'Диск цэвэрлэж, үйлчилгээг дахин ачааллав.'])
            ->assertRedirect("/tickets/{$ticket->id}");

        $ticket->refresh();
        $this->assertSame('closed', $ticket->status);
        $this->assertNotNull($ticket->closed_at);
        $this->assertSame('Диск цэвэрлэж, үйлчилгээг дахин ачааллав.', $ticket->resolution);
        $this->assertSame(['assigned', 'closed'], $ticket->activities()->pluck('action')->all());

        Notification::assertSentTo($reporter, TicketStatusChanged::class, function (TicketStatusChanged $n) use ($reporter) {
            $mail = $n->toMail($reporter);

            return str_contains($mail->subject, 'хаагдлаа') && str_contains((string) $mail->render(), 'Диск цэвэрлэж');
        });

        // Хаасны дараа дахин өөрчлөх, хуваарилах боломжгүй
        $this->actingAs($engineer)->get("/tickets/{$ticket->id}")
            ->assertSee('Шийдвэрлэгдэж хаагдсан')
            ->assertDontSee('Хүсэлт гүйцэтгэх');
        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => now()->addDay()->format('Y-m-d\TH:i'), 'reason' => 'x',
        ])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())
            ->put("/tickets/{$ticket->id}/assign", ['assignee_id' => $engineer->id])
            ->assertSessionHas('error');
    }

    public function test_assignee_postpones_ticket_with_reason_and_date(): void
    {
        Notification::fake();
        [$ticket, $reporter, $engineer] = $this->assignedTicket();
        $until = now()->addDays(3)->startOfMinute();

        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => now()->subHour()->format('Y-m-d\TH:i'), 'reason' => '',
        ])->assertSessionHasErrors(['postponed_until', 'reason']);

        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/postpone", [
            'postponed_until' => $until->format('Y-m-d\TH:i'),
            'reason' => 'Сэлбэг захиалсан.',
        ])->assertRedirect("/tickets/{$ticket->id}");

        $ticket->refresh();
        $this->assertSame('postponed', $ticket->status);
        $this->assertTrue($ticket->postponed_until->equalTo($until));
        Notification::assertSentTo($reporter, TicketStatusChanged::class, function (TicketStatusChanged $n) use ($reporter) {
            $mail = $n->toMail($reporter);

            return str_contains($mail->subject, 'хойшлогдлоо') && str_contains((string) $mail->render(), 'Сэлбэг захиалсан.');
        });

        $this->actingAs($reporter)->get("/tickets/{$ticket->id}")
            ->assertSee('хүртэл хойшлуулсан')
            ->assertSee('Сэлбэг захиалсан.');

        // Хойшлуулсан дуудлагыг дараа нь хааж болно
        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/close", ['resolution' => 'Сэлбэг солив.']);
        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->postponed_until);
    }

    public function test_only_assigned_assignee_or_admin_can_close_or_postpone(): void
    {
        Notification::fake();
        [$ticket, $reporter] = $this->assignedTicket();
        $otherEngineer = User::factory()->assignee()->create();

        $this->actingAs($reporter)->put("/tickets/{$ticket->id}/close", ['resolution' => 'x'])->assertForbidden();
        $this->actingAs($otherEngineer)->put("/tickets/{$ticket->id}/close", ['resolution' => 'x'])->assertForbidden();
        $this->actingAs($reporter)->get("/tickets/{$ticket->id}")->assertDontSee('Хүсэлт гүйцэтгэх');

        $this->actingAs(User::factory()->admin()->create())
            ->put("/tickets/{$ticket->id}/close", ['resolution' => 'Админ хаав.'])
            ->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_list_status_tabs(): void
    {
        Notification::fake();
        [$ticket, , $engineer] = $this->assignedTicket();
        $this->actingAs($engineer)->put("/tickets/{$ticket->id}/close", ['resolution' => 'Болсон.']);
        $this->actingAs(User::factory()->create())->post('/tickets', $this->payload(['repair_category_id' => $this->system->id]));
        $open = Ticket::where('status', '!=', 'closed')->firstOrFail();
        $this->flushSession();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/tickets')->assertSee($open->number)->assertDontSee($ticket->number);
        $this->actingAs($admin)->get('/tickets?status=closed')->assertSee($ticket->number)->assertDontSee($open->number);
        $this->actingAs($admin)->get('/tickets?status=all')->assertSee($ticket->number)->assertSee($open->number);
    }

    public function test_admin_monitors_but_cannot_submit_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create())->post('/tickets', $this->payload());
        $ticket = Ticket::firstOrFail();
        $this->flushSession();

        $this->actingAs($admin)->get('/tickets/create')->assertForbidden();
        $this->actingAs($admin)->post('/tickets', $this->payload())->assertForbidden();
        $this->assertSame(1, Ticket::count());

        // Бүх дуудлагыг хянана, тохиргоо цэс харагдана, илгээх холбоос харагдахгүй
        $this->actingAs($admin)->get('/tickets')
            ->assertOk()
            ->assertSee($ticket->number)
            ->assertSee('Тохиргоо')
            ->assertDontSee('href="'.route('tickets.create').'"', false);
        $this->actingAs($admin)->get("/tickets/{$ticket->id}")
            ->assertOk()
            ->assertDontSee('href="'.route('tickets.create').'"', false);
        $this->actingAs($admin)->get('/settings/users')->assertOk();
    }
}
