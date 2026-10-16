<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\RepairType;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Хянах самбар. Бүх эрхэд нэг загвар, харин өгөгдөл нь Ticket::visibleTo-оор шүүгдэнэ:
 * админ бүгдийг, гүйцэтгэгч өөрт ирснийг, хэрэглэгч өөрийн илгээснийг харна.
 */
class DashboardController extends Controller
{
    /** Ангиллын картын дээд тоо, үлдсэнийг «Бусад» болгоно */
    private const MAX_CATEGORY_CARDS = 8;

    /** Харьцуулах хугацаа (хоног) ба график бүрийн дээд мөрийн тоо */
    private const WINDOW_DAYS = 30;

    private const TOP_BARS = 5;

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $period = $this->period($request);

        // Харагдах бүх дуудлага; самбарын үзүүлэлтүүд сонгосон хугацаанд ирснээр шүүгдэнэ
        $visible = fn () => Ticket::visibleTo($user);
        $tickets = fn () => $visible()->when($period['from'], fn ($q) => $q->whereBetween('created_at', [$period['from'], $period['to']]));

        $byStatus = $tickets()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $byStatus->sum();

        $stats = [
            'total' => $total,
            'open' => (int) collect(Ticket::OPEN_STATUSES)->sum(fn ($s) => $byStatus[$s] ?? 0),
            'unassigned' => (int) ($byStatus['new'] ?? 0),
            'postponed' => (int) ($byStatus['postponed'] ?? 0),
            'closed' => (int) ($byStatus['closed'] ?? 0),
            // Хойшлуулсан бол шинэ хугацаагаар, бусад нь хүссэн хугацаагаар
            'overdue' => $tickets()->whereIn('status', Ticket::OPEN_STATUSES)->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('status', 'postponed')->where('postponed_until', '<', now()))
                ->orWhere(fn ($q) => $q->where('status', '!=', 'postponed')->where('due_at', '<', now())))
                ->count(),
        ];

        return view('dashboard', [
            'period' => $period,
            'stats' => $stats,
            'types' => $this->breakdown($tickets, 'type', Ticket::TYPES, $total),
            'urgencies' => $this->breakdown($tickets, 'urgency', Ticket::URGENCIES, $total),
            'categories' => $this->categories($user, $period),
            'compare' => $this->compare($visible, $period),
            'recent' => $tickets()->with(['user', 'assignee', 'repairType', 'repairCategory'])->latest('id')->limit(8)->get(),
        ] + $this->details($tickets, $visible, $period, $byStatus));
    }

    /**
     * Самбарын нэмэлт картуудын өгөгдөл. Сонгосон хугацааны дуудлагуудыг нэг удаа уншиж PHP-д бүлэглэнэ.
     *
     * @param  \Closure(): Builder  $tickets  сонгосон хугацаанд ирсэн
     * @param  \Closure(): Builder  $visible  бүх харагдах
     */
    private function details(\Closure $tickets, \Closure $visible, array $period, $byStatus): array
    {
        $now = now();
        $rows = $tickets()
            ->with(['user:id,department_id', 'repairCategory:id,name', 'assignee:id,name'])
            ->get(['id', 'user_id', 'assignee_id', 'repair_category_id', 'status', 'due_at', 'postponed_until', 'created_at', 'closed_at']);
        $total = $rows->count();
        $open = $rows->whereIn('status', Ticket::OPEN_STATUSES);

        $share = fn (int $v) => $total > 0 ? round($v / $total * 100, 1) : 0.0;
        $top = function ($groups, int $limit, string $otherLabel = 'Бусад') use ($share) {
            $sorted = $groups->sortDesc();
            $items = $sorted->take($limit)->map(fn ($v, $k) => ['label' => $k, 'value' => $v, 'percent' => $share($v)])->values();
            $rest = $sorted->slice($limit)->sum();
            if ($rest > 0) {
                $items->push(['label' => $otherLabel, 'value' => $rest, 'percent' => $share($rest)]);
            }

            return $items->all();
        };

        // Хоногийн бүлэглэл (гистограм)
        $buckets = fn () => ['0–1' => 0, '1–3' => 0, '3–7' => 0, '7–14' => 0, '14+' => 0];
        $bucketOf = fn (float $days) => match (true) {
            $days < 1 => '0–1', $days < 3 => '1–3', $days < 7 => '3–7', $days < 14 => '7–14', default => '14+',
        };

        $resolution = $buckets();
        foreach ($rows->where('status', 'closed')->filter(fn ($t) => $t->closed_at) as $t) {
            $resolution[$bucketOf($t->created_at->diffInMinutes($t->closed_at) / 1440)]++;
        }
        $daysOpen = $buckets();
        foreach ($open as $t) {
            $daysOpen[$bucketOf($t->created_at->diffInMinutes($now) / 1440)]++;
        }

        $deptNames = Department::pluck('name', 'id');
        $deadline = fn (Ticket $t) => $t->status === 'postponed' ? $t->postponed_until : $t->due_at;

        // Сүүлийн 6 сар (сонгосон хугацаанаас үл хамааран)
        $monthStart = $now->copy()->startOfMonth()->subMonthsNoOverflow(5);
        $monthly = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $monthStart->copy()->addMonthsNoOverflow($i);
            $monthly[$m->format('Y-m')] = ['label' => $m->month.'-р сар', 'value' => 0];
        }
        $visible()->where('created_at', '>=', $monthStart)->pluck('created_at')
            ->each(function ($at) use (&$monthly) {
                $key = $at->format('Y-m');
                if (isset($monthly[$key])) {
                    $monthly[$key]['value']++;
                }
            });

        // Сонгосон хугацаанд хаагдсан (хэзээ ирсэнээс үл хамааран)
        $closedInPeriod = $visible()->where('status', 'closed')
            ->when($period['from'], fn ($q) => $q->whereBetween('closed_at', [$period['from'], $period['to']]))
            ->count();

        return [
            'statuses' => collect(Ticket::STATUSES)->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label, 'value' => (int) ($byStatus[$key] ?? 0), 'percent' => $share((int) ($byStatus[$key] ?? 0)),
            ])->values()->all(),
            'systems' => $top($rows->groupBy(fn ($t) => $t->repairCategory?->name ?? 'Систем заагаагүй')->map->count(), 7),
            'assignees' => $top($rows->groupBy(fn ($t) => $t->assignee?->name ?? 'Хуваарилаагүй')->map->count(), 6),
            'departments' => $top($rows->groupBy(fn ($t) => $deptNames[$t->user?->department_id] ?? 'Тодорхойгүй')->map->count(), 7),
            'resolution' => collect($resolution)->map(fn ($v, $k) => ['label' => $k, 'value' => $v])->values()->all(),
            'daysOpen' => collect($daysOpen)->map(fn ($v, $k) => ['label' => $k, 'value' => $v])->values()->all(),
            'monthly' => array_values($monthly),
            'risk' => [
                'overdue' => $open->filter(fn ($t) => $deadline($t)?->isPast())->count(),
                'due_today' => $open->filter(fn ($t) => ($d = $deadline($t)) && $d->isFuture() && $d->lte($now->copy()->addDay()))->count(),
                'postponed' => $open->where('status', 'postponed')->count(),
            ],
            'movement' => [
                'arrived' => $total,
                'closed' => $closedInPeriod,
                'net' => $total - $closedInPeriod,
                'rate' => $total > 0 ? round($rows->where('status', 'closed')->count() / $total * 100, 1) : 0.0,
            ],
        ];
    }

    /**
     * Нэг баганаар бүлэглэж нийт, идэвхтэй, хаасан тоо болон хувийг буцаана.
     *
     * @param  \Closure(): Builder  $tickets
     * @return array<string, array{label: string, total: int, open: int, closed: int, percent: float}>
     */
    private function breakdown(\Closure $tickets, string $column, array $labels, int $total): array
    {
        $rows = $tickets()
            ->selectRaw("{$column} as k, count(*) as total, sum(case when status = 'closed' then 1 else 0 end) as closed")
            ->groupBy($column)
            ->get()
            ->keyBy('k');

        $result = [];
        foreach ($labels as $key => $label) {
            $count = (int) ($rows[$key]->total ?? 0);
            $closed = (int) ($rows[$key]->closed ?? 0);

            $result[$key] = [
                'label' => $label,
                'total' => $count,
                'open' => $count - $closed,
                'closed' => $closed,
                'percent' => $total > 0 ? round($count / $total * 100, 1) : 0.0,
            ];
        }

        return $result;
    }

    /**
     * Сүүлийн 30 хоногийг өмнөх 30 хоногтой харьцуулсан баганууд.
     *
     * @param  \Closure(): Builder  $tickets
     * @return array<string, array{title: string, rows: list<array{label: string, value: int, prev: int}>}>
     */
    private function compare(\Closure $tickets, array $period): array
    {
        [$curFrom, $curTo] = $period['compare']['current'];
        [$prevFrom, $prevTo] = $period['compare']['previous'];

        $recent = $tickets()
            ->whereBetween('created_at', [$prevFrom, $curTo])
            ->with('user:id,department_id')
            ->get(['id', 'user_id', 'assignee_id', 'repair_type_id', 'urgency', 'created_at']);

        [$current, $previous] = $recent->partition(fn (Ticket $t) => $t->created_at->gte($curFrom));

        $typeNames = RepairType::pluck('name', 'id');
        $userNames = User::whereIn('id', $recent->pluck('assignee_id')->filter()->unique())->pluck('name', 'id');
        $deptNames = Department::pluck('name', 'id');

        $build = function (string $title, callable $key, callable $label, ?array $order = null) use ($current, $previous) {
            $cur = $current->groupBy($key)->map->count();
            $prev = $previous->groupBy($key)->map->count();

            // Тогтмол дараалалгүй бол сүүлийн 30 хоногоор ихээс бага руу эрэмбэлнэ
            $keys = $order ?? $cur->keys()->merge($prev->keys())->unique()
                ->sortByDesc(fn ($k) => ($cur[$k] ?? 0) * 10000 + ($prev[$k] ?? 0))
                ->take(self::TOP_BARS)
                ->all();

            return [
                'title' => $title,
                'rows' => collect($keys)->map(fn ($k) => [
                    'label' => $label($k),
                    'value' => (int) ($cur[$k] ?? 0),
                    'prev' => (int) ($prev[$k] ?? 0),
                ])->values()->all(),
            ];
        };

        return [
            'category' => $build('Ангилал', fn ($t) => $t->repair_type_id ?? 0, fn ($k) => $typeNames[$k] ?? 'Тодорхойгүй'),
            'assignee' => $build('Гүйцэтгэгч', fn ($t) => $t->assignee_id ?? 0, fn ($k) => $k ? ($userNames[$k] ?? '—') : 'Хуваарилаагүй'),
            'urgency' => $build('Яаралтай байдал', fn ($t) => $t->urgency, fn ($k) => Ticket::URGENCIES[$k] ?? $k, array_reverse(array_keys(Ticket::URGENCIES))),
            'department' => $build('Алба, хэлтэс', fn ($t) => $t->user?->department_id ?? 0, fn ($k) => $deptNames[$k] ?? 'Тодорхойгүй'),
        ];
    }

    /**
     * Хугацааны сонголт: all (бүгд) | month (?date=2026-10) | week (?date=2026-10-12, Даваа гараг).
     * Харьцуулалт: сар/долоо хоногийг өмнөх сар/долоо хоногтой, «бүгд» үед сүүлийн 30 хоногийг өмнөх 30 хоногтой.
     */
    private function period(Request $request): array
    {
        $mode = in_array($request->query('period'), ['month', 'week'], true) ? $request->query('period') : 'all';
        $date = (string) $request->query('date');

        try {
            $anchor = $date !== '' ? CarbonImmutable::parse($mode === 'month' && strlen($date) === 7 ? $date.'-01' : $date) : CarbonImmutable::now();
        } catch (\Throwable) {
            $anchor = CarbonImmutable::now();
        }

        $now = CarbonImmutable::now();

        if ($mode === 'all') {
            return [
                'mode' => 'all',
                'from' => null,
                'to' => null,
                'label' => 'Бүх хугацаа',
                'compare' => [
                    'current' => [$now->subDays(self::WINDOW_DAYS), $now],
                    'previous' => [$now->subDays(self::WINDOW_DAYS * 2), $now->subDays(self::WINDOW_DAYS)],
                ],
                'compare_labels' => ['Сүүлийн 30 хоног', 'Өмнөх 30 хоног'],
            ];
        }

        $isMonth = $mode === 'month';
        $from = $isMonth ? $anchor->startOfMonth() : $anchor->startOfWeek(CarbonImmutable::MONDAY);
        $to = $isMonth ? $from->endOfMonth() : $from->endOfWeek(CarbonImmutable::SUNDAY);
        $prevFrom = $isMonth ? $from->subMonthNoOverflow() : $from->subWeek();
        $prevTo = $isMonth ? $prevFrom->endOfMonth() : $prevFrom->endOfWeek(CarbonImmutable::SUNDAY);
        $nextFrom = $isMonth ? $from->addMonthNoOverflow() : $from->addWeek();
        $param = fn (CarbonImmutable $d) => $isMonth ? $d->format('Y-m') : $d->format('Y-m-d');

        $label = $isMonth
            ? $from->format('Y').' оны '.$from->month.'-р сар'
            : $from->format('Y.m.d').' – '.$to->format('m.d').' ('.$from->isoWeek().'-р долоо хоног)';

        $isCurrent = $now->betweenIncluded($from, $to);

        return [
            'mode' => $mode,
            'from' => $from,
            'to' => $to,
            'label' => $label,
            'date' => $param($from),
            'prev' => $param($prevFrom),
            // Ирээдүй рүү шилжихгүй
            'next' => $nextFrom->lte($now) ? $param($nextFrom) : null,
            'is_current' => $isCurrent,
            'compare' => ['current' => [$from, $to], 'previous' => [$prevFrom, $prevTo]],
            'compare_labels' => $isMonth
                ? [$isCurrent ? 'Энэ сар' : 'Сонгосон сар', 'Өмнөх сар']
                : [$isCurrent ? 'Энэ долоо хоног' : 'Сонгосон долоо хоног', 'Өмнөх долоо хоног'],
        ];
    }

    /** Ангилал (засварын төрөл) бүрийн харагдах дуудлагын тоо, ихээс бага руу */
    private function categories(User $user, array $period): array
    {
        $scope = fn ($q) => $q->visibleTo($user)
            ->when($period['from'], fn ($q) => $q->whereBetween('created_at', [$period['from'], $period['to']]));

        $rows = RepairType::query()
            ->withCount([
                'tickets as total' => $scope,
                'tickets as open' => fn ($q) => $scope($q)->whereIn('status', Ticket::OPEN_STATUSES),
            ])
            ->get()
            ->filter(fn ($t) => $t->total > 0)
            ->sortByDesc('total')
            ->values();

        $items = $rows->take(self::MAX_CATEGORY_CARDS)
            ->map(fn ($t) => ['id' => $t->id, 'label' => $t->name, 'total' => (int) $t->total, 'open' => (int) $t->open])
            ->all();

        $rest = $rows->slice(self::MAX_CATEGORY_CARDS);
        if ($rest->isNotEmpty()) {
            $items[] = ['id' => null, 'label' => 'Бусад', 'total' => (int) $rest->sum('total'), 'open' => (int) $rest->sum('open')];
        }

        return $items;
    }
}
