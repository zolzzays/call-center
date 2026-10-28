@extends('layouts.app')

@section('title', 'МАБ')

@section('page-styles')
    .tabs { display: flex; gap: 4px; border-bottom: 1px solid #e3e9f1; margin-top: 16px; overflow-x: auto; }
    .tab {
        display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; font-size: 14px; font-weight: 600;
        color: var(--muted); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap;
    }
    .tab:hover { color: var(--navy); }
    .tab.active { color: var(--primary); border-bottom-color: var(--primary); }
    .tab-count { font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 999px; background: #eef1f4; color: var(--muted); }
    .tab.active .tab-count { background: #e8f0fe; color: var(--primary); }
    .tab-count.alert { background: #fdecea; color: var(--danger); }

    .table-wrap { overflow-x: auto; margin-top: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e8edf3; vertical-align: top; }
    th { color: var(--table-head); font-weight: 700; font-size: 12px; white-space: nowrap; background: var(--th-bg); }
    tr[data-href] { cursor: pointer; }
    tr[data-href]:hover td { background: #f8fafc; }
    .num { font-weight: 700; color: var(--primary); text-decoration: none; white-space: nowrap; }
    .sub { display: block; font-size: 12px; color: var(--muted); margin-top: 2px; }
    .nowrap { white-space: nowrap; }
    .st { display: inline-block; font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #eef2f7; color: var(--navy); white-space: nowrap; }
    .st-pending_head, .st-pending_security, .st-pending_chief { background: #fff7e6; color: #b45309; }
    .st-approved { background: #e8f0fe; color: var(--primary); }
    .st-granted { background: #e7f5ec; color: var(--excel); }
    .st-rejected, .st-revoked, .st-expired { background: var(--danger-bg); color: var(--danger); }
    .empty { text-align: center; padding: 40px 16px; color: var(--muted); }
@endsection

@section('page')
@php
    $tabs = ['awaiting' => ['Батлах', $awaitingCount], 'mine' => ['Миний хүсэлт', $mineCount], 'all' => ['Бүгд', $allCount]];
    // Батлах үүрэггүй энгийн хэрэглэгчид «Батлах», «Бүгд» таб хэрэггүй
    if ($awaitingCount === 0 && $allCount === $mineCount) {
        $tabs = ['mine' => $tabs['mine']];
    }
@endphp

<div class="panel">
    <div class="panel-head">
        <div>
            <h1>МАБ · Хандах эрхийн хүсэлт</h1>
            <p>Систем, нөөцөд хандах эрх хүсэх. Нэгжийн удирдлага → МАБ инженер → Ерөнхий диспетчер батлаад системийн инженер эрх үүсгэж олгоно. МАБ инженер тогтмол хянана.</p>
        </div>
        <div class="toolbar">
            @if (\App\Models\AccessRequest::isAuditor(auth()->user()))
                <a href="{{ route('access-requests.audit') }}" class="btn btn-ghost">Хяналт, аудит</a>
            @endif
            <a href="{{ route('access-requests.create') }}" class="btn">+ Хандах эрх хүсэх</a>
        </div>
    </div>

    @if (count($tabs) > 1)
        <nav class="tabs" aria-label="Хүсэлт шүүх">
            @foreach ($tabs as $key => [$label, $count])
                <a href="{{ route('access-requests.index', ['tab' => $key]) }}" class="tab {{ $tab === $key ? 'active' : '' }}" @if ($tab === $key) aria-current="page" @endif>
                    {{ $label }} <span class="tab-count {{ $key === 'awaiting' && $count > 0 ? 'alert' : '' }}">{{ $count }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Дугаар</th>
                    <th>Хүсэгч</th>
                    <th>Хүсэлтийн төрөл</th>
                    <th>Системийн нэр</th>
                    <th>Эрх</th>
                    <th>Хугацаа</th>
                    <th>Төлөв</th>
                    <th>Илгээсэн</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $r)
                    <tr data-href="{{ route('access-requests.show', $r) }}">
                        <td><a href="{{ route('access-requests.show', $r) }}" class="num">{{ $r->number }}</a></td>
                        <td>{{ $r->user?->name ?? '—' }}<span class="sub">{{ $r->user?->department?->name }}</span></td>
                        <td>{{ $r->requestTypeLabel() }}</td>
                        <td>{{ $r->system }}@if ($r->modules_count) <span class="sub">{{ $r->modules_count }} модуль</span>@endif</td>
                        <td class="nowrap">{{ $r->accessTypeLabel() }}</td>
                        <td class="nowrap">{{ $r->periodLabel() }}</td>
                        <td><span class="st st-{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                        <td class="nowrap">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">
                        {{ $tab === 'awaiting' ? 'Танд батлах хүсэлт алга.' : 'Хүсэлт алга.' }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $requests->links('pagination::simple-default') }}</div>
</div>

<script>
    document.querySelectorAll('tr[data-href]').forEach((tr) => tr.addEventListener('click', (e) => {
        if (!e.target.closest('a')) location.href = tr.dataset.href;
    }));
</script>
@endsection
