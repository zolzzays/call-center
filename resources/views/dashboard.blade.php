@extends('layouts.app')

@section('title', 'Хянах самбар')

@section('container', 'wide')

@section('page-styles')
    .dash {
        /* Категорийн өнгө (тогтмол дараалал, өнгөний харалган хүнд шалгасан) */
        --c-blue: #2563eb; --c-blue-soft: #93c5fd;
        --c-red: #ef4444; --c-orange: #f59e0b; --c-teal: #14b8a6;
        --c-purple: #8b5cf6; --c-pink: #ec4899; --c-sky: #0ea5e9; --c-lime: #84cc16;
        --c-gray: #94a3b8;
        --ink: var(--navy); --ink-2: var(--card-title); --ink-3: var(--muted);
        --hair: #edf1f5;
    }

    /* Толгой ба хугацааны сонголт */
    .dash-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
    .dash-head h1 { margin: 0; font-size: 22px; font-weight: 800; color: var(--navy); }
    .dash-head p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .period-label { color: var(--card-title); font-weight: 700; }
    .period-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-left: auto; }
    .seg-tabs { display: inline-flex; padding: 4px; background: #e8edf2; border-radius: 12px; gap: 2px; }
    .seg-tabs a { padding: 7px 14px; border-radius: 9px; font-size: 13px; font-weight: 700; color: var(--muted); text-decoration: none; }
    .seg-tabs a:hover { color: var(--card-title); }
    .seg-tabs a.active { background: #fff; color: var(--c-blue); box-shadow: 0 1px 3px rgba(15, 42, 51, .12); }
    .stepper { display: inline-flex; align-items: center; gap: 6px; padding: 4px; background: #fff; border: 1px solid var(--card-border); border-radius: 12px; }
    .step-btn { width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; color: var(--card-title); text-decoration: none; }
    .step-btn:hover { background: #eef3f8; }
    .step-btn.disabled { color: #c3cbd0; }
    .step-label { min-width: 150px; text-align: center; font-size: 13px; font-weight: 700; color: var(--card-title); padding: 0 4px; }
    .step-today { font-size: 12px; font-weight: 700; color: var(--c-blue); text-decoration: none; padding: 6px 10px; border-radius: 8px; background: #eaf1fe; }

    /* Картын сүлжээ */
    .row { display: grid; gap: 14px; margin-bottom: 14px; }
    .row-4 { grid-template-columns: 1.15fr 1.35fr .85fr 1.15fr; }
    .row-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .row-2 { grid-template-columns: minmax(0, 1fr) minmax(0, 1.7fr); }
    .tile {
        background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--card-radius);
        box-shadow: var(--card-shadow); padding: 16px 16px 14px; min-width: 0; display: flex; flex-direction: column;
    }
    .tile-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 12px; }
    .tile-title { margin: 0; font-size: 13px; font-weight: 800; color: var(--ink-2); text-transform: uppercase; letter-spacing: .02em; line-height: 1.3; }
    .tile-sub { display: block; margin-top: 3px; font-size: 11px; font-weight: 600; color: var(--c-blue); text-transform: none; letter-spacing: 0; }
    .tile-link { margin-top: auto; padding-top: 10px; text-align: right; font-size: 12px; font-weight: 700; color: var(--c-blue); text-decoration: none; }
    .tile-link:hover { text-decoration: underline; }
    .tile-body { flex: 1; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
    .empty-chart { padding: 18px 0; text-align: center; color: var(--muted); font-size: 13px; }

    /* Хэвтээ баганууд */
    .hbars { display: grid; gap: 9px; }
    .hbar { display: grid; grid-template-columns: minmax(70px, 34%) minmax(0, 1fr); align-items: center; gap: 10px; outline: none; border-radius: 4px; }
    .hbar:focus-visible { box-shadow: 0 0 0 2px var(--ink); }
    .hbar-label { font-size: 12.5px; color: var(--ink-2); text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hbar-track { position: relative; display: flex; align-items: center; height: 16px; min-width: 0; }
    .hbar-fill { height: 100%; border-radius: 0 3px 3px 0; min-width: 2px; flex-shrink: 0; max-width: calc(100% - 40px); }
    .hbar-val { margin-left: 6px; font-size: 11.5px; color: var(--ink-3); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .hbar:hover .hbar-fill { filter: brightness(1.1); }
    .hbars.thin { gap: 6px; }
    .hbars.thin .hbar-track { height: 11px; }

    /* Дугуй график */
    .donut { position: relative; flex-shrink: 0; }
    .donut svg { width: 100%; height: 100%; transform: rotate(-90deg); display: block; }
    .donut-seg { cursor: default; transition: stroke-width .12s; }
    .donut-seg:hover { stroke-width: 20; }
    .donut-center { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; pointer-events: none; }
    .donut-center b { font-size: 20px; font-weight: 800; color: var(--ink); line-height: 1; }
    .donut-center span { font-size: 11px; color: var(--ink-3); margin-top: 3px; }
    .donut-row { display: flex; align-items: center; gap: 18px; min-width: 0; }
    .donut-col { display: flex; flex-direction: column; align-items: center; gap: 14px; }

    /* Тайлбар: цэг + нэр (+ утга, хувь) */
    .legend { display: grid; gap: 7px; min-width: 0; flex: 1; }
    .lg { display: grid; grid-template-columns: 10px minmax(0, 1fr) auto; align-items: center; gap: 8px; font-size: 12px; color: var(--ink-2); }
    .lg.with-pct { grid-template-columns: 10px minmax(0, 1fr) auto 44px; }
    .lg i { width: 9px; height: 9px; border-radius: 50%; }
    .lg span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lg b { font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }
    .lg em { font-style: normal; color: var(--ink-3); text-align: right; font-variant-numeric: tabular-nums; }
    .legend-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 22px; width: 100%; }
    .lp-name { display: flex; align-items: flex-start; gap: 6px; font-size: 12px; color: var(--ink-2); line-height: 1.3; }
    .lp-name i { margin-top: 3px; }
    .lp-name i { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .lp-val { font-size: 17px; font-weight: 800; color: var(--ink); margin-top: 2px; }
    .lp-pct { font-size: 13px; font-weight: 700; }
    .type-icons { display: flex; gap: 2px; }
    .type-icons svg { width: 22px; height: 22px; }

    /* Хоёр талт (идэвхтэй ← → хаасан) */
    .bfly-head { display: grid; grid-template-columns: 1fr 96px 1fr; font-size: 12px; color: var(--ink-3); text-align: center; margin-bottom: 8px; }
    .bfly { display: grid; gap: 8px; }
    .bfly-row { display: grid; grid-template-columns: 1fr 96px 1fr; align-items: center; outline: none; text-decoration: none; }
    .bfly-row:focus-visible { box-shadow: 0 0 0 2px var(--ink); border-radius: 4px; }
    .bfly-side { display: flex; align-items: center; gap: 6px; height: 18px; }
    .bfly-side.left { flex-direction: row-reverse; }
    .bfly-bar { height: 100%; min-width: 2px; }
    .bfly-side.left .bfly-bar { border-radius: 3px 0 0 3px; }
    .bfly-side.right .bfly-bar { border-radius: 0 3px 3px 0; }
    .bfly-side small { font-size: 11.5px; color: var(--ink-3); font-variant-numeric: tabular-nums; }
    .bfly-mid { font-size: 12px; font-weight: 700; color: var(--ink-2); text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 0 4px; }

    /* Босоо баганууд */
    .cols-chart { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 8px; height: calc(var(--h) + 22px); }
    .cols-axis { display: flex; flex-direction: column; justify-content: space-between; height: var(--h); font-size: 11px; color: var(--ink-3); text-align: right; font-variant-numeric: tabular-nums; }
    .cols-axis span { line-height: 0; }
    .cols-plot { position: relative; display: flex; align-items: flex-end; gap: 8px; height: var(--h); border-bottom: 1px solid #cbd5e1; }
    .cols-grid { position: absolute; left: 0; right: 0; height: 1px; background: var(--hair); }
    .col { position: relative; flex: 1; height: 100%; display: flex; align-items: flex-end; justify-content: center; outline: none; }
    .col:focus-visible { box-shadow: inset 0 0 0 2px var(--ink); border-radius: 4px; }
    .col-bar { position: relative; width: 100%; max-width: 34px; border-radius: 3px 3px 0 0; }
    .col:hover .col-bar { filter: brightness(1.08); }
    .col-val { position: absolute; font-size: 11px; font-weight: 600; color: var(--ink-2); font-variant-numeric: tabular-nums; }
    .col-label { position: absolute; top: calc(100% + 5px); font-size: 11px; color: var(--ink-3); white-space: nowrap; }

    /* Тоон хайрцаг */
    .stat-boxes { display: grid; gap: 8px; }
    .stat-boxes.four { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 16px; }
    .sbox { border: 1px solid var(--card-border); border-radius: 12px; padding: 9px 12px; text-align: center; text-decoration: none; display: block; }
    a.sbox:hover { border-color: #c9d6e6; background: #fafcff; }
    .sbox-label { display: block; font-size: 11px; color: var(--ink-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sbox-value { display: block; font-size: 20px; font-weight: 800; color: var(--ink); margin-top: 2px; font-variant-numeric: tabular-nums; }
    .sbox-value.warn { color: #ea8a00; }
    .sbox-value.good { color: #16a34a; }
    .sbox-value.bad { color: var(--c-red); }
    .risk { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 16px; align-items: start; }
    .mini-title { font-size: 11px; font-weight: 700; color: var(--ink-2); text-transform: uppercase; letter-spacing: .03em; text-align: center; margin: 0 0 12px; }

    /* Өмнөх үетэй харьцуулалт (багана + цэг) */
    .cmp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; }
    .cmp-legend { display: flex; gap: 14px; justify-content: flex-end; font-size: 11.5px; color: var(--ink-3); }
    .cmp-legend span { display: inline-flex; align-items: center; gap: 5px; }
    .cmp-legend i { width: 12px; height: 8px; border-radius: 2px; background: var(--c-blue); }
    .cmp-legend i.dot { width: 9px; height: 9px; border-radius: 50%; background: var(--c-orange); }
    .hb { display: grid; grid-template-columns: minmax(70px, 36%) minmax(0, 1fr); align-items: center; gap: 10px; outline: none; }
    .hb:focus-visible { box-shadow: 0 0 0 2px var(--ink); border-radius: 4px; }
    .hb-label { font-size: 12.5px; color: var(--ink-2); text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hb-track { position: relative; height: 14px; margin-right: 30px; }
    .hb-bar { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 0 3px 3px 0; background: var(--c-blue); min-width: 2px; }
    .hb-num { position: absolute; top: 50%; transform: translateY(-50%); margin-left: 6px; font-size: 11.5px; color: var(--ink-3); font-variant-numeric: tabular-nums; }
    .hb-mark { position: absolute; top: 50%; width: 9px; height: 9px; margin-left: -4.5px; transform: translateY(-50%); border-radius: 50%; background: var(--c-orange); box-shadow: 0 0 0 2px #fff; pointer-events: none; }
    .hbs { display: grid; gap: 9px; }

    /* Сүүлийн дуудлагууд */
    .table-tile { padding: 0; overflow: hidden; }
    .table-tile .tile-head { padding: 16px 16px 0; }
    .table-tile .table-wrap { margin-top: 0; }
    .table-tile th:first-child, .table-tile td:first-child { padding-left: 16px; }
    .table-tile th { border-radius: 0 !important; }
    .num { font-weight: 700; color: var(--ink); text-decoration: none; white-space: nowrap; }
    tr:hover .num { color: var(--c-blue); }
    .sub { display: block; font-size: 12px; color: var(--muted); margin-top: 2px; }
    tbody tr[data-href] { cursor: pointer; }

    .tip {
        position: fixed; z-index: 50; pointer-events: none; padding: 8px 11px; border-radius: 8px; min-width: 130px;
        background: #0f172a; color: #fff; font-size: 12px; line-height: 1.5; box-shadow: 0 8px 24px rgba(0, 0, 0, .2);
        opacity: 0; transition: opacity .1s;
    }
    .tip.show { opacity: 1; }
    .tip strong { display: block; font-size: 13px; }
    .tip span { display: block; color: rgba(255, 255, 255, .78); }

    @media (max-width: 1500px) {
        .row-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .row-6 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 1100px) { .row-2 { grid-template-columns: 1fr; } }
    @media (max-width: 760px) {
        .row-4, .row-6, .cmp-grid { grid-template-columns: 1fr; }
        .stat-boxes.four { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .risk { grid-template-columns: 1fr; }
        .period-bar { margin-left: 0; width: 100%; }
        .step-label { min-width: 0; flex: 1; }
    }
@endsection

@section('page')
@php
    $me = auth()->user();
    $isAdmin = $me->isAdmin();
    $isAssignee = $me->isAssignee();
    $scopeText = $isAdmin ? 'Бүх дуудлага, хүсэлтийн нэгдсэн үзүүлэлт'
        : ($isAssignee ? 'Танд ирсэн дуудлагын үзүүлэлт' : 'Таны илгээсэн дуудлагын үзүүлэлт');
    $showSender = ! $me->canSubmitTickets();
    $showAssignee = ! $isAssignee;
    $total = $stats['total'];

    $range = $period['from'] ? ['from' => $period['from']->format('Y-m-d'), 'to' => $period['to']->format('Y-m-d')] : [];
    $list = fn (array $q = []) => route('tickets.index', $q + $range + ['status' => 'all']);
    $openList = fn (array $q = []) => route('tickets.index', $q + $range);
    $periodUrl = fn (string $mode, ?string $date = null) => route(request()->route()->getName(), array_filter(['period' => $mode === 'all' ? null : $mode, 'date' => $date]));
    [$curLabel, $prevLabel] = $period['compare_labels'];

    $palette = ['var(--c-blue)', 'var(--c-red)', 'var(--c-orange)', 'var(--c-teal)', 'var(--c-purple)', 'var(--c-pink)', 'var(--c-sky)', 'var(--c-lime)'];
    $colorize = fn (array $rows) => array_map(fn ($r, $i) => $r + ['color' => $r['label'] === 'Бусад' ? 'var(--c-gray)' : $palette[$i % count($palette)]], $rows, array_keys($rows));

    $systemItems = $colorize($systems);
    $statusColors = ['new' => 'var(--c-blue)', 'assigned' => 'var(--c-purple)', 'postponed' => 'var(--c-orange)', 'closed' => 'var(--c-teal)'];
    $urgencyColors = ['normal' => 'var(--c-blue)', 'urgent' => 'var(--c-orange)', 'critical' => 'var(--c-red)'];
    $bflyMax = max(1, max(array_merge(array_column($urgencies, 'open'), array_column($urgencies, 'closed'))));
@endphp

<div class="dash">
    <div class="dash-head">
        <div>
            <h1>Хянах самбар</h1>
            <p>{{ $scopeText }} · <b class="period-label">{{ $period['label'] }}</b></p>
        </div>
        <div class="period-bar">
            <nav class="seg-tabs" aria-label="Хугацаа сонгох">
                @foreach (['all' => 'Бүгд', 'month' => 'Сар', 'week' => '7 хоног'] as $mode => $text)
                    <a href="{{ $periodUrl($mode) }}" class="{{ $period['mode'] === $mode ? 'active' : '' }}" @if ($period['mode'] === $mode) aria-current="true" @endif>{{ $text }}</a>
                @endforeach
            </nav>
            @if ($period['mode'] !== 'all')
                <div class="stepper">
                    <a href="{{ $periodUrl($period['mode'], $period['prev']) }}" class="step-btn" aria-label="Өмнөх {{ $period['mode'] === 'month' ? 'сар' : 'долоо хоног' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                    <span class="step-label">{{ $period['label'] }}</span>
                    @if ($period['next'])
                        <a href="{{ $periodUrl($period['mode'], $period['next']) }}" class="step-btn" aria-label="Дараах {{ $period['mode'] === 'month' ? 'сар' : 'долоо хоног' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @else
                        <span class="step-btn disabled" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
                    @endif
                    @unless ($period['is_current'])
                        <a href="{{ $periodUrl($period['mode']) }}" class="step-today">{{ $period['mode'] === 'month' ? 'Энэ сар' : 'Энэ долоо хоног' }}</a>
                    @endunless
                </div>
            @endif
        </div>
        <div class="form-actions" style="margin:0">
            <a href="{{ $list() }}" class="btn btn-ghost">{{ $isAdmin ? 'Бүх дуудлага' : ($isAssignee ? 'Ирсэн хүсэлт' : 'Миний дуудлага') }}</a>
            @if ($me->canSubmitTickets())
                <a href="{{ route('tickets.create') }}" class="btn">+ Хүсэлт илгээх</a>
            @endif
        </div>
    </div>

    {{-- 1-р мөр --}}
    <div class="row row-4">
        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Дуудлага ангиллаар<span class="tile-sub">Эхний 5</span></h3></div>
            <div class="tile-body">
                <x-dash.hbars :rows="array_map(fn ($c) => ['label' => $c['label'], 'value' => $c['total']], array_slice($categories, 0, 5))" label="Ангиллаар" />
            </div>
            <a href="{{ $list() }}" class="tile-link">Бүгдийг харах</a>
        </section>

        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Систем, төхөөрөмжөөр</h3></div>
            <div class="tile-body">
                @if ($total === 0)
                    <div class="empty-chart">Өгөгдөл алга.</div>
                @else
                    <div class="donut-row">
                        <x-dash.donut :items="$systemItems" :size="128" :stroke="18" :center="number_format($total)" sub="нийт" label="Систем, төхөөрөмжөөр" />
                        <div class="legend">
                            @foreach ($systemItems as $it)
                                <div class="lg with-pct"><i style="background: {{ $it['color'] }}"></i><span title="{{ $it['label'] }}">{{ $it['label'] }}</span><b>{{ number_format($it['value']) }}</b><em>{{ $it['percent'] }}%</em></div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Төрлийн бүтэц</h3></div>
            <div class="tile-body">
                <div class="donut-col">
                    <x-dash.donut :items="[
                        ['label' => $types['repair']['label'], 'value' => $types['repair']['total'], 'color' => 'var(--c-blue)'],
                        ['label' => $types['change']['label'], 'value' => $types['change']['total'], 'color' => 'var(--c-red)'],
                    ]" :size="120" :stroke="16" label="Төрлийн бүтэц">
                        <span class="type-icons" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--c-blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            <svg viewBox="0 0 24 24" fill="none" stroke="var(--c-red)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </span>
                    </x-dash.donut>
                    <div class="legend-pair">
                        @foreach (['repair' => 'var(--c-blue)', 'change' => 'var(--c-red)'] as $key => $color)
                            <div>
                                <div class="lp-name"><i style="background: {{ $color }}"></i>{{ $types[$key]['label'] }}</div>
                                <div class="lp-val">{{ number_format($types[$key]['total']) }}</div>
                                <div class="lp-pct" style="color: {{ $color }}">{{ round($types[$key]['percent']) }}%</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Яаралтай байдал × төлөв<span class="tile-sub">Идэвхтэй ба хаасан</span></h3></div>
            <div class="tile-body">
                <div class="bfly-head"><span>Идэвхтэй</span><span></span><span>Хаасан</span></div>
                <div class="bfly" role="list" aria-label="Яаралтай байдлаар идэвхтэй ба хаасан">
                    @foreach (array_reverse($urgencies, true) as $key => $u)
                        <a href="{{ $list(['urgency' => $key]) }}" class="bfly-row" role="listitem"
                           data-tip="{{ $u['label'] }}|Идэвхтэй {{ $u['open'] }} · Хаасан {{ $u['closed'] }}|Нийт {{ $u['total'] }} · {{ $u['percent'] }}%">
                            <span class="bfly-side left"><span class="bfly-bar" style="width: {{ $u['open'] / $bflyMax * 82 }}%; background: var(--c-blue)"></span><small>{{ $u['open'] }}</small></span>
                            <span class="bfly-mid">{{ $u['label'] }}</span>
                            <span class="bfly-side right"><span class="bfly-bar" style="width: {{ $u['closed'] / $bflyMax * 82 }}%; background: var(--c-teal)"></span><small>{{ $u['closed'] }}</small></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    {{-- 2-р мөр --}}
    <div class="row row-6">
        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Гүйцэтгэгчээр</h3></div>
            <div class="tile-body"><x-dash.hbars :rows="$assignees" label="Гүйцэтгэгчээр" /></div>
        </section>

        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Шийдвэрлэсэн хугацаа<span class="tile-sub">Хоногоор</span></h3></div>
            <div class="tile-body"><x-dash.columns :rows="$resolution" :highlight="0" :height="130" label="Шийдвэрлэсэн хугацаа" /></div>
        </section>

        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Төлөвийн бүтэц</h3></div>
            <div class="tile-body">
                @php $statusItems = array_map(fn ($s) => $s + ['color' => $statusColors[$s['key']]], $statuses); @endphp
                <div class="donut-row">
                    <x-dash.donut :items="$statusItems" :size="112" :stroke="16" label="Төлөвийн бүтэц" />
                    <div class="legend">
                        @foreach ($statusItems as $it)
                            <div class="lg"><i style="background: {{ $it['color'] }}"></i><span>{{ $it['label'] }}</span><b>{{ $it['value'] }}</b></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Яаралтай байдлаар</h3></div>
            <div class="tile-body">
                @php $urgencyItems = array_map(fn ($k) => ['label' => $urgencies[$k]['label'], 'value' => $urgencies[$k]['total'], 'color' => $urgencyColors[$k]], array_keys($urgencies)); @endphp
                <div class="donut-row">
                    <x-dash.donut :items="$urgencyItems" :size="112" :stroke="16" label="Яаралтай байдлаар" />
                    <div class="legend">
                        @foreach ($urgencyItems as $it)
                            <div class="lg"><i style="background: {{ $it['color'] }}"></i><span>{{ $it['label'] }}</span><b>{{ $it['value'] }}</b></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Алба, хэлтсээр</h3></div>
            <div class="tile-body"><x-dash.hbars :rows="$departments" :thin="true" label="Алба, хэлтсээр" /></div>
        </section>

        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Нээлттэй хоног<span class="tile-sub">Идэвхтэй дуудлага</span></h3></div>
            <div class="tile-body"><x-dash.columns :rows="$daysOpen" :height="130" label="Нээлттэй хоног" /></div>
        </section>
    </div>

    {{-- 3-р мөр --}}
    <div class="row row-2">
        <section class="tile">
            <div class="tile-head"><h3 class="tile-title">Хугацааны эрсдэл</h3></div>
            <div class="risk">
                <div class="stat-boxes">
                    <a href="{{ $openList() }}" class="sbox"><span class="sbox-label">Хугацаа хэтэрсэн</span><span class="sbox-value {{ $risk['overdue'] ? 'warn' : '' }}">{{ number_format($risk['overdue']) }}</span></a>
                    <a href="{{ $openList() }}" class="sbox"><span class="sbox-label">24 цагт дуусах</span><span class="sbox-value">{{ number_format($risk['due_today']) }}</span></a>
                    <a href="{{ $openList(['status' => 'postponed']) }}" class="sbox"><span class="sbox-label">Хойшлуулсан</span><span class="sbox-value">{{ number_format($risk['postponed']) }}</span></a>
                    <a href="{{ $openList() }}" class="sbox"><span class="sbox-label">Хуваарилаагүй</span><span class="sbox-value">{{ number_format($stats['unassigned']) }}</span></a>
                </div>
                <div>
                    <p class="mini-title">Сараар ирсэн хүсэлт</p>
                    <x-dash.columns :rows="$monthly" color="var(--c-orange)" :height="170" label="Сараар ирсэн хүсэлт" />
                </div>
            </div>
        </section>

        <section class="tile">
            <div class="tile-head">
                <h3 class="tile-title">Дуудлагын хөдөлгөөн<span class="tile-sub">{{ $curLabel }} · {{ mb_strtolower($prevLabel) }}</span></h3>
                <div class="cmp-legend" aria-hidden="true"><span><i></i>{{ $curLabel }}</span><span><i class="dot"></i>{{ $prevLabel }}</span></div>
            </div>
            <div class="stat-boxes four">
                <a href="{{ $list() }}" class="sbox"><span class="sbox-label">Ирсэн</span><span class="sbox-value">{{ number_format($movement['arrived']) }}</span></a>
                <a href="{{ $openList(['status' => 'closed']) }}" class="sbox"><span class="sbox-label">Хаасан</span><span class="sbox-value">{{ number_format($movement['closed']) }}</span></a>
                <div class="sbox" title="Ирсэн хасах хаасан"><span class="sbox-label">Цэвэр өөрчлөлт</span><span class="sbox-value {{ $movement['net'] > 0 ? 'bad' : 'good' }}">{{ $movement['net'] > 0 ? '+' : '' }}{{ number_format($movement['net']) }}</span></div>
                <div class="sbox"><span class="sbox-label">Шийдвэрлэлт</span><span class="sbox-value">{{ $movement['rate'] }}%</span></div>
            </div>
            <div class="cmp-grid">
                @foreach ($compare as $chart)
                    @php $max = max(array_merge([1], array_column($chart['rows'], 'value'), array_column($chart['rows'], 'prev'))); @endphp
                    <div>
                        <p class="mini-title" style="text-align:left">{{ $chart['title'] }}</p>
                        @if (empty($chart['rows']))
                            <div class="empty-chart">Энэ хоёр хугацаанд дуудлага алга.</div>
                        @else
                            <div class="hbs" role="list" aria-label="{{ $chart['title'] }}">
                                @foreach ($chart['rows'] as $row)
                                    @php $w = $row['value'] / $max * 100; $diff = $row['value'] - $row['prev']; @endphp
                                    <div class="hb" role="listitem" tabindex="0"
                                         data-tip="{{ $row['label'] }}|{{ $curLabel }}: {{ $row['value'] }}|{{ $prevLabel }}: {{ $row['prev'] }} ({{ $diff >= 0 ? '+' : '' }}{{ $diff }})">
                                        <span class="hb-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                        <span class="hb-track">
                                            <span class="hb-bar" style="width: {{ $w }}%"></span>
                                            <span class="hb-num" style="left: {{ $w }}%">{{ $row['value'] }}</span>
                                            <span class="hb-mark" style="left: {{ $row['prev'] / $max * 100 }}%" aria-hidden="true"></span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Сүүлийн дуудлагууд --}}
    <section class="tile table-tile">
        <div class="tile-head">
            <h3 class="tile-title">Сүүлийн дуудлагууд</h3>
            <a href="{{ $list() }}" class="tile-link" style="margin:0;padding:0">Бүгдийг харах →</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
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
                    @forelse ($recent as $ticket)
                        <tr data-href="{{ route('tickets.show', $ticket) }}">
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="num">№{{ $ticket->number }}</a>
                                <span class="sub">{{ $ticket->typeLabel() }}</span>
                            </td>
                            <td>{{ $ticket->repairType?->name ?? '—' }}@if ($ticket->repairCategory) <span class="sub">{{ $ticket->repairCategory->name }}</span>@endif</td>
                            <td><span class="badge badge-{{ $ticket->urgency }}">{{ $ticket->urgencyLabel() }}</span></td>
                            @if ($showSender) <td>{{ $ticket->user?->name ?? '—' }}</td> @endif
                            @if ($showAssignee)
                                <td>
                                    @if ($ticket->assignee)
                                        {{ $ticket->assignee->name }}
                                    @else
                                        <span class="sub" style="margin:0">Хуваарилаагүй</span>
                                    @endif
                                </td>
                            @endif
                            <td><span class="badge badge-{{ $ticket->status }}">{{ $ticket->statusLabel() }}</span></td>
                            <td style="white-space:nowrap">{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 5 + (int) $showSender + (int) $showAssignee }}" class="empty">
                            @if ($me->canSubmitTickets())
                                Та дуудлага илгээгээгүй байна. <a href="{{ route('tickets.create') }}">Хүсэлт илгээх</a>
                            @else
                                Дуудлага бүртгэгдээгүй байна.
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="tip" id="tip" role="tooltip"></div>

<script>
    (function () {
        // data-tip="Гарчиг|мөр|мөр"
        const tip = document.getElementById('tip');
        function show(el, x, y) {
            const [title, ...lines] = el.dataset.tip.split('|');
            tip.replaceChildren();
            const strong = document.createElement('strong');
            strong.textContent = title;
            tip.appendChild(strong);
            lines.forEach((l) => {
                const span = document.createElement('span');
                span.textContent = l;
                tip.appendChild(span);
            });
            tip.style.left = Math.min(x + 14, window.innerWidth - tip.offsetWidth - 8) + 'px';
            tip.style.top = Math.max(8, y - tip.offsetHeight - 12) + 'px';
            tip.classList.add('show');
        }
        const hide = () => tip.classList.remove('show');

        document.querySelectorAll('[data-tip]').forEach((el) => {
            el.addEventListener('mousemove', (e) => show(el, e.clientX, e.clientY));
            el.addEventListener('mouseleave', hide);
            el.addEventListener('focus', () => { const r = el.getBoundingClientRect(); show(el, r.left + r.width / 2, r.top); });
            el.addEventListener('blur', hide);
        });

        document.querySelectorAll('tbody tr[data-href]').forEach((row) => {
            row.addEventListener('click', (e) => {
                if (!e.target.closest('a, button')) window.location = row.dataset.href;
            });
        });
    })();
</script>
@endsection
