@extends('layouts.app')

@section('container', 'wide')

@section('title', auth()->user()->isAdmin() ? 'Хүсэлт илгээх' : (auth()->user()->isAssignee() ? 'Ирсэн хүсэлт' : 'Миний хүсэлтүүд'))

@section('page-styles')
    .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 20px; }
    .stat {
        position: relative; display: block; border: 1px solid var(--card-border); border-radius: 12px; padding: 12px 14px 10px;
        background: #fff; text-align: center; text-decoration: none; color: inherit; overflow: hidden; transition: border-color .15s, box-shadow .15s;
    }
    .stat::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: var(--u, var(--primary)); }
    .stat:hover { border-color: #c9d6e6; background: #fafcff; }
    .stat.active { border-color: var(--u, var(--primary)); background: #fff; box-shadow: 0 0 0 3px var(--u-ring, rgba(37, 99, 235, .12)); }
    .stat-label { font-size: 11px; color: var(--muted); }
    .stat-value { font-size: 20px; font-weight: 800; color: var(--navy); margin-top: 2px; line-height: 1.2; font-variant-numeric: tabular-nums; }
    .u-normal { --u: #2563eb; --u-ring: rgba(37, 99, 235, .15); }
    .u-urgent { --u: #d97706; --u-ring: rgba(217, 119, 6, .15); }
    .u-critical { --u: #c0392b; --u-ring: rgba(192, 57, 43, .15); }

    .search { position: relative; }
    .search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
    .toolbar .search .input { padding-left: 36px; width: 260px; }

    .filter-note { display: flex; align-items: center; gap: 8px; margin-top: 16px; font-size: 13px; color: var(--muted); }
    .filter-note a { font-weight: 600; }

    tbody tr[data-href] { cursor: pointer; }
    td.urgency-bar { width: 4px; padding: 0; background: var(--u); }
    .num { font-weight: 700; color: var(--navy); text-decoration: none; white-space: nowrap; font-variant-numeric: tabular-nums; }
    tr:hover .num { color: var(--primary); }
    .sub { display: block; font-size: 12px; color: var(--muted); margin-top: 2px; }
    .desc { display: block; max-width: 360px; font-size: 12px; color: var(--muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .muted { color: var(--muted); }
    .nowrap { white-space: nowrap; }
    .clip { display: inline-flex; align-items: center; gap: 3px; font-size: 12px; color: var(--muted); margin-left: 6px; vertical-align: 1px; }

    .pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; white-space: nowrap; background: #fff; border: 1px solid currentColor; }
    .pill::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .pill-normal { color: #2563eb; }
    .pill-urgent { color: #c2650a; }
    .pill-critical { color: #d93a3f; }
    .pill-new { color: #2f6fb0; }
    .pill-assigned { color: #6d5bd0; }
    .pill-postponed { color: #b7791f; }
    .pill-returned { color: #c2410c; }
    .pill-closed { color: var(--excel); }
    tr.is-closed td:not(.urgency-bar) { color: #7b8594; }
    tr.is-closed .urgency-bar { opacity: .35; }

    .tabs { display: flex; gap: 4px; margin-top: 20px; border-bottom: 1px solid var(--card-border); overflow-x: auto; }
    .tab {
        display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; margin-bottom: -1px; white-space: nowrap;
        font-size: 14px; font-weight: 600; color: var(--muted); text-decoration: none; border-bottom: 2px solid transparent;
    }
    .tab:hover { color: var(--navy); }
    .tab.active { color: var(--primary); border-bottom-color: var(--primary); }
    .tab-count { font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 999px; background: #eef1f4; color: var(--muted); }
    .tab.active .tab-count { background: #e8f0fe; color: var(--primary); }

    .returned-notice {
        display: flex; align-items: center; gap: 12px; padding: 14px 18px; margin-bottom: 20px; border-radius: 12px;
        background: #fff4ed; border: 1px solid #fbcfb0; color: #9a3412; font-size: 14px; line-height: 1.5; text-decoration: none;
    }
    .returned-notice:hover { background: #ffedd5; }
    .returned-notice svg { flex-shrink: 0; color: #c2410c; }
    .returned-notice strong { display: block; color: #c2410c; }
    .returned-notice-link { margin-left: auto; font-weight: 700; white-space: nowrap; color: #c2410c; }
    .returned-hint { color: #c2410c; font-weight: 600; }
    tr.is-returned td { background: #fffaf5; }


    .who { white-space: nowrap; }

    .empty-state { padding: 48px 16px; text-align: center; }
    .empty-state svg { color: #b5c3d6; }
    .empty-state strong { display: block; margin-top: 12px; font-size: 15px; color: var(--navy); }
    .empty-state p { margin: 6px 0 16px; }

    @media (max-width: 900px) {
        .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .stat { padding: 10px 12px 10px 16px; }
        .stat-value { font-size: 20px; }
        .toolbar, .toolbar .search, .toolbar .search .input { width: 100%; }
        .toolbar .btn { flex: 1; }
    }
@endsection

@section('page')
@php
    $me = auth()->user();
    $isAdmin = $me->isAdmin();
    $isAssignee = $me->isAssignee();
    $showSender = $isAdmin || $isAssignee;
    $showAssignee = ! $isAssignee;
    $heading = $isAdmin ? 'Хүсэлт илгээх' : ($isAssignee ? 'Ирсэн хүсэлт' : 'Миний хүсэлтүүд');
    $subheading = $isAdmin ? 'Ирсэн бүх дуудлага, хүсэлт' : ($isAssignee ? 'Танд хуваарилагдсан дуудлага, хүсэлтүүд' : 'Таны илгээсэн дуудлага, хүсэлтүүд');
    // Одоогийн шүүлтийг хадгалж нэг утгыг солино ("open" нь анхдагч тул URL-д бичихгүй)
    $listUrl = fn (array $over) => route('tickets.index', array_filter(array_merge(
        ['q' => $search, 'urgency' => $urgency, 'status' => $status === 'open' ? null : $status,
         'from' => $from?->format('Y-m-d'), 'to' => $to?->format('Y-m-d')], $over
    )));
    $filterUrl = fn (?string $u) => $listUrl(['urgency' => $u]);
    $statusTabs = ['open' => 'Идэвхтэй', 'postponed' => 'Хойшлуулсан', 'returned' => 'Буцаасан', 'closed' => 'Хаасан', 'all' => 'Бүгд'];
    // Илгээгчид буцаагдсан, тодруулга хүлээж буй хүсэлтүүд
    $returnedCount = $me->canSubmitTickets() ? $statusCounts['returned'] : 0;
    $columns = 6 + (int) $showSender + (int) $showAssignee;
@endphp

@if ($returnedCount > 0)
    <a href="{{ $listUrl(['status' => 'returned']) }}" class="returned-notice" role="status">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
        <span>
            <strong>Таны {{ $returnedCount }} хүсэлтийг гүйцэтгэгч буцаасан байна.</strong>
            Хүсэлтээ нээж, шалтгааныг уншаад тодруулгаа бичиж дахин илгээнэ үү.
        </span>
        <span class="returned-notice-link">Харах →</span>
    </a>
@endif

<div class="panel">
    <div class="panel-head">
        <div>
            <h1>{{ $heading }}</h1>
            <p>{{ $subheading }}</p>
        </div>
        <div class="toolbar">
            <form method="GET" action="{{ route('tickets.index') }}" class="search" role="search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $search }}" class="input" placeholder="Дугаар, тайлбараар хайх..." aria-label="Дуудлага хайх">
                @if ($urgency) <input type="hidden" name="urgency" value="{{ $urgency }}"> @endif
                @if ($status !== 'open') <input type="hidden" name="status" value="{{ $status }}"> @endif
                @if ($from) <input type="hidden" name="from" value="{{ $from->format('Y-m-d') }}"> @endif
                @if ($to) <input type="hidden" name="to" value="{{ $to->format('Y-m-d') }}"> @endif
            </form>
            @if ($me->canSubmitTickets())
                <a href="{{ route('tickets.create') }}" class="btn">+ Хүсэлт илгээх</a>
            @endif
        </div>
    </div>

    <nav class="tabs" aria-label="Төлөвөөр шүүх">
        @foreach ($statusTabs as $key => $label)
            <a href="{{ $listUrl(['status' => $key === 'open' ? null : $key]) }}" class="tab {{ $status === $key ? 'active' : '' }}" @if ($status === $key) aria-current="page" @endif>
                {{ $label }} <span class="tab-count">{{ $statusCounts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="stats">
        <a href="{{ $filterUrl(null) }}" class="stat {{ $urgency ? '' : 'active' }}">
            <div class="stat-label">Бүгд</div>
            <div class="stat-value">{{ $counts->sum() }}</div>
        </a>
        @foreach (\App\Models\Ticket::URGENCIES as $value => $label)
            <a href="{{ $filterUrl($value) }}" class="stat u-{{ $value }} {{ $urgency === $value ? 'active' : '' }}">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value">{{ $counts[$value] ?? 0 }}</div>
            </a>
        @endforeach
    </div>

    @if ($search !== '' || $urgency || $from || $to)
        <div class="filter-note">
            <span>
                {{ $tickets->total() }} бичлэг олдлоо
                @if ($urgency) · {{ \App\Models\Ticket::URGENCIES[$urgency] }} @endif
                @if ($from || $to) · {{ $from?->format('Y.m.d') ?? '…' }} – {{ $to?->format('Y.m.d') ?? '…' }} @endif
                @if ($search !== '') · «{{ $search }}» @endif
            </span>
            <a href="{{ $listUrl(['q' => null, 'urgency' => null, 'from' => null, 'to' => null]) }}">Шүүлтийг цэвэрлэх</a>
        </div>
    @endif

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:4px;padding:0"></th>
                    <th>Дугаар</th>
                    <th>Ангилал / Систем</th>
                    <th>Яаралтай байдал</th>
                    @if ($showSender) <th>Илгээгч</th> @endif
                    @if ($showAssignee) <th>Гүйцэтгэгч</th> @endif
                    <th>Төлөв</th>
                    <th>Илгээсэн</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    @php
                        $url = route('tickets.show', $ticket);
                    @endphp
                    <tr data-href="{{ $url }}" class="u-{{ $ticket->urgency }} {{ $ticket->isClosed() ? 'is-closed' : '' }} {{ $ticket->canBeResubmittedBy($me) ? 'is-returned' : '' }}">
                        <td class="urgency-bar" aria-hidden="true"></td>
                        <td>
                            <a href="{{ $url }}" class="num">№{{ $ticket->number }}</a>
                            @if ($ticket->attachments_count)
                                <span class="clip" title="{{ $ticket->attachments_count }} хавсралт">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>{{ $ticket->attachments_count }}
                                </span>
                            @endif
                            <span class="sub">{{ $ticket->typeLabel() }}</span>
                        </td>
                        <td>
                            {{ $ticket->repairType?->name ?? '—' }}@if ($ticket->repairCategory) <span class="muted">/</span> {{ $ticket->repairCategory->name }}@endif
                            <span class="desc" title="{{ $ticket->description }}">{{ $ticket->description }}</span>
                        </td>
                        <td><span class="pill pill-{{ $ticket->urgency }}">{{ $ticket->urgencyLabel() }}</span></td>
                        @if ($showSender)
                            <td>
                                <span class="who">
                                    {{ $ticket->user?->name ?? '—' }}
                                </span>
                            </td>
                        @endif
                        @if ($showAssignee)
                            <td>
                                @if ($ticket->assignee)
                                    <span class="who">
                                        {{ $ticket->assigneeDisplay() }}
                                    </span>
                                @else
                                    <span class="muted">Хуваарилаагүй</span>
                                @endif
                            </td>
                        @endif
                        <td>
                            <span class="pill pill-{{ $ticket->status }}">{{ $ticket->statusLabel() }}</span>
                            @if ($ticket->canBeResubmittedBy($me)) <span class="sub returned-hint">Тодруулга шаардлагатай</span> @endif
                        </td>
                        <td class="nowrap">
                            {{ $ticket->created_at->format('Y-m-d') }}
                            <span class="sub">{{ $ticket->created_at->format('H:i') }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $columns }}">
                        <div class="empty-state">
                            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                            @if ($search !== '' || $urgency || $from || $to)
                                <strong>Шүүлтэд тохирох бичлэг олдсонгүй</strong>
                                <p>Өөр түлхүүр үгээр хайж үзнэ үү.</p>
                                <a href="{{ route('tickets.index') }}" class="btn btn-ghost">Шүүлтийг цэвэрлэх</a>
                            @elseif ($isAssignee)
                                <strong>Танд хуваарилсан дуудлага алга байна</strong>
                                <p>Админ дуудлага хуваарилахад энд харагдана.</p>
                            @elseif ($isAdmin)
                                <strong>Дуудлага, хүсэлт ирээгүй байна</strong>
                                <p>Хэрэглэгчид дуудлага илгээхэд энд харагдана.</p>
                            @else
                                <strong>Дуудлага, хүсэлт алга байна</strong>
                                <p>Систем, төхөөрөмжид асуудал гарвал энд бүртгүүлээрэй.</p>
                                <a href="{{ route('tickets.create') }}" class="btn">+ Хүсэлт илгээх</a>
                            @endif
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($tickets->hasPages())
        <div class="pagination">{{ $tickets->links('pagination::simple-default') }}</div>
    @endif
</div>

<script>
    document.querySelectorAll('tbody tr[data-href]').forEach((row) => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('a, button') || window.getSelection().toString()) return;
            if (e.ctrlKey || e.metaKey) window.open(row.dataset.href, '_blank');
            else window.location = row.dataset.href;
        });
    });
</script>
@endsection
