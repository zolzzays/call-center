<?php

namespace Tests\Feature;

use App\Models\RepairType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private ?User $reporter = null;

    private function ticket(array $attributes): Ticket
    {
        // Тест бүр өөрийн өгөгдлийн сантай тул илгээгчийг тухайн тестэд үүсгэнэ
        $this->reporter ??= User::factory()->create();

        return Ticket::createWithNumber($attributes + [
            'user_id' => $this->reporter->id,
            'type' => 'repair',
            'description' => 'Тест',
            'urgency' => 'normal',
        ]);
    }

    public function test_dashboard_shows_totals_by_type_urgency_and_category(): void
    {
        $scada = RepairType::create(['name' => 'Скада']);
        $network = RepairType::create(['name' => 'Сүлжээ']);

        $this->ticket(['repair_type_id' => $scada->id, 'urgency' => 'critical']);
        $this->ticket(['repair_type_id' => $scada->id, 'urgency' => 'critical', 'type' => 'change']);
        $this->ticket(['repair_type_id' => $scada->id, 'urgency' => 'urgent']);
        $this->ticket(['repair_type_id' => $network->id, 'urgency' => 'normal', 'due_at' => now()->subDay()]);
        $closed = $this->ticket(['repair_type_id' => $network->id, 'urgency' => 'normal']);
        $closed->forceFill(['status' => 'closed', 'closed_at' => now()])->save();

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();

        $stats = $response->viewData('stats');
        $this->assertSame(5, $stats['total']);
        $this->assertSame(4, $stats['open']);
        $this->assertSame(1, $stats['closed']);
        $this->assertSame(1, $stats['overdue']);

        $types = $response->viewData('types');
        $this->assertSame(4, $types['repair']['total']);
        $this->assertSame(1, $types['change']['total']);
        $this->assertSame(80.0, $types['repair']['percent']);

        $urgencies = $response->viewData('urgencies');
        $this->assertSame(2, $urgencies['critical']['total']);
        $this->assertSame(1, $urgencies['urgent']['total']);
        $this->assertSame(2, $urgencies['normal']['total']);
        $this->assertSame(1, $urgencies['normal']['closed']);

        $categories = $response->viewData('categories');
        $this->assertSame(['Скада', 'Сүлжээ'], array_column($categories, 'label'));
        $this->assertSame([3, 2], array_column($categories, 'total'));

        $response->assertSee('Засварын дуудлага')
            ->assertSee('Нэмэлт, өөрчлөлтийн хүсэлт')
            ->assertSee('Нэн яаралтай')
            ->assertSee('Скада');
    }

    public function test_dashboard_renders_with_no_tickets(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')
            ->assertOk()
            ->assertSee('Дуудлага бүртгэгдээгүй байна.');
    }

    public function test_non_admin_cannot_open_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->assignee()->create())->get('/admin')->assertForbidden();
    }

    public function test_user_dashboard_shows_only_own_tickets_in_same_layout(): void
    {
        $scada = RepairType::create(['name' => 'Скада']);
        $network = RepairType::create(['name' => 'Сүлжээ']);
        $me = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->ticket(['user_id' => $me->id, 'repair_type_id' => $scada->id, 'urgency' => 'critical']);
        $this->ticket(['user_id' => $me->id, 'repair_type_id' => $scada->id, 'type' => 'change']);
        $theirs = $this->ticket(['user_id' => $other->id, 'repair_type_id' => $network->id, 'urgency' => 'urgent']);

        $response = $this->actingAs($me)->get('/dashboard')->assertOk();

        $this->assertSame(2, $response->viewData('stats')['total']);
        $this->assertSame(1, $response->viewData('types')['repair']['total']);
        $this->assertSame(1, $response->viewData('types')['change']['total']);
        $this->assertSame(1, $response->viewData('urgencies')['critical']['total']);
        $this->assertSame(0, $response->viewData('urgencies')['urgent']['total']);
        $this->assertSame(['Скада'], array_column($response->viewData('categories'), 'label'));

        $response->assertSee('Таны илгээсэн хүсэлтийн үзүүлэлт')
            ->assertSee('Төлөвийн бүтэц')
            ->assertDontSee('Яаралтай байдлаар')
            ->assertSee($mine->number)
            ->assertDontSee($theirs->number)
            ->assertDontSee('Сүлжээ')
            ->assertSee('href="'.route('tickets.create').'"', false);
    }

    public function test_assignee_dashboard_shows_only_assigned_tickets(): void
    {
        $type = RepairType::create(['name' => 'Скада']);
        $engineer = User::factory()->assignee()->create();

        $assigned = $this->ticket(['repair_type_id' => $type->id]);
        $assigned->forceFill(['assignee_id' => $engineer->id, 'status' => 'assigned'])->save();
        $this->ticket(['repair_type_id' => $type->id]);

        $response = $this->actingAs($engineer)->get('/dashboard')->assertOk();

        $this->assertSame(1, $response->viewData('stats')['total']);
        $response->assertSee('Танд ирсэн хүсэлтийн үзүүлэлт')
            ->assertSee($assigned->number)
            ->assertDontSee('href="'.route('tickets.create').'"', false);
    }

    public function test_last_30_days_compared_with_previous_30_days(): void
    {
        $type = RepairType::create(['name' => 'Скада']);

        $this->travelTo(now()->subDays(45));
        $this->ticket(['repair_type_id' => $type->id, 'urgency' => 'urgent']);
        $this->travelBack();
        $this->ticket(['repair_type_id' => $type->id, 'urgency' => 'critical']);
        $this->ticket(['repair_type_id' => $type->id, 'urgency' => 'critical']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
        $compare = $response->viewData('compare');

        $this->assertSame([['label' => 'Скада', 'value' => 2, 'prev' => 1]], $compare['category']['rows']);
        $this->assertSame(['Нэн яаралтай', 'Яаралтай', 'Энгийн'], array_column($compare['urgency']['rows'], 'label'));
        $this->assertSame([2, 0, 0], array_column($compare['urgency']['rows'], 'value'));
        $this->assertSame([0, 1, 0], array_column($compare['urgency']['rows'], 'prev'));
        $this->assertSame('Хуваарилаагүй', $compare['assignee']['rows'][0]['label']);

        $response->assertSee('Сүүлийн 30 хоног')->assertSee('Өмнөх 30 хоног')->assertSee('Төлөв');
    }

    public function test_dashboard_filters_by_month_and_compares_with_previous_month(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(10, 0));
        $type = RepairType::create(['name' => 'Скада']);

        $this->travelTo(now()->setDate(2026, 9, 5));
        $september = $this->ticket(['repair_type_id' => $type->id, 'urgency' => 'urgent']);
        $this->travelTo(now()->setDate(2026, 10, 3));
        $octoberA = $this->ticket(['repair_type_id' => $type->id, 'urgency' => 'critical']);
        $octoberB = $this->ticket(['repair_type_id' => $type->id, 'urgency' => 'critical']);
        $this->travelTo(now()->setDate(2026, 10, 20));

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin?period=month&date=2026-10')->assertOk();
        $this->assertSame(2, $response->viewData('stats')['total']);
        $this->assertSame(2, $response->viewData('urgencies')['critical']['total']);
        $this->assertSame([['label' => 'Скада', 'value' => 2, 'prev' => 1]], $response->viewData('compare')['category']['rows']);
        $this->assertSame('2026-09', $response->viewData('period')['prev']);
        $this->assertNull($response->viewData('period')['next']);
        $response->assertSee('2026 оны 10-р сар')
            ->assertSee($octoberA->number)
            ->assertDontSee($september->number)
            ->assertSee('Энэ сар');

        $response = $this->actingAs($admin)->get('/admin?period=month&date=2026-09')->assertOk();
        $this->assertSame(1, $response->viewData('stats')['total']);
        $this->assertSame('2026-10', $response->viewData('period')['next']);

        // Буруу утгыг үл тоомсорлож бүх хугацааг харуулна
        $this->assertSame(3, $this->actingAs($admin)->get('/admin?period=year')->viewData('stats')['total']);
        $this->assertSame(2, $this->actingAs($admin)->get('/admin?period=month&date=abc')->viewData('stats')['total']);
    }

    public function test_dashboard_filters_by_week(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 14)->setTime(10, 0)); // Лхагва
        $type = RepairType::create(['name' => 'Скада']);

        $this->travelTo(now()->setDate(2026, 10, 12)->setTime(9, 0)); // Даваа
        $this->ticket(['repair_type_id' => $type->id]);
        $this->travelTo(now()->setDate(2026, 10, 11)->setTime(23, 0)); // өмнөх Ням
        $this->ticket(['repair_type_id' => $type->id]);
        $this->travelTo(now()->setDate(2026, 10, 14)->setTime(10, 0));

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin?period=week')->assertOk();

        $this->assertSame(1, $response->viewData('stats')['total']);
        $this->assertSame('2026-10-12', $response->viewData('period')['date']);
        $this->assertSame('2026-10-05', $response->viewData('period')['prev']);
        $this->assertSame([['label' => 'Скада', 'value' => 1, 'prev' => 1]], $response->viewData('compare')['category']['rows']);
        $response->assertSee('2026.10.12 – 10.18')->assertSee('href="'.e(route('tickets.index', ['from' => '2026-10-12', 'to' => '2026-10-18', 'status' => 'all'])).'"', false);
    }

    public function test_ticket_list_filters_by_date_range(): void
    {
        $type = RepairType::create(['name' => 'Скада']);
        $this->travelTo(now()->setDate(2026, 9, 5));
        $old = $this->ticket(['repair_type_id' => $type->id]);
        $this->travelTo(now()->setDate(2026, 10, 5));
        $new = $this->ticket(['repair_type_id' => $type->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/tickets?status=all&from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->assertSee($new->number)
            ->assertDontSee($old->number)
            ->assertSee('2026.10.01 – 2026.10.31');
    }

    public function test_detail_cards_data(): void
    {
        $type = RepairType::create(['name' => 'Скада']);
        $system = \App\Models\RepairCategory::create(['repair_type_id' => $type->id, 'name' => 'SCADA сервер']);
        $engineer = User::factory()->assignee()->create(['name' => 'Инженер Бат']);

        $a = $this->ticket(['repair_type_id' => $type->id, 'repair_category_id' => $system->id, 'due_at' => now()->subHour()]);
        $a->forceFill(['assignee_id' => $engineer->id, 'status' => 'assigned'])->save();
        $b = $this->ticket(['repair_type_id' => $type->id]);
        $b->forceFill(['status' => 'closed', 'closed_at' => $b->created_at->copy()->addHours(30)])->save();

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();

        $this->assertSame(['SCADA сервер', 'Систем заагаагүй'], array_column($response->viewData('systems'), 'label'));
        $this->assertContains('Инженер Бат', array_column($response->viewData('assignees'), 'label'));
        $this->assertSame(1, collect($response->viewData('resolution'))->firstWhere('label', '1–3')['value']);
        $this->assertSame(1, $response->viewData('risk')['overdue']);
        $this->assertSame(['arrived' => 2, 'closed' => 1, 'net' => 1, 'rate' => 50.0], $response->viewData('movement'));
        $this->assertSame(1, collect($response->viewData('statuses'))->firstWhere('key', 'assigned')['value']);

        $response->assertSee('Систем, төхөөрөмжөөр')->assertDontSee('Хугацааны эрсдэл')->assertDontSee('Дуудлагын хөдөлгөөн');
    }
}
