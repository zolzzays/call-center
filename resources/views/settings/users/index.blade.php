@extends('layouts.app')

@section('title', 'Хэрэглэгч')

@section('page-styles')
    .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 16px; }
    .stat { border: 1px solid var(--card-border); border-radius: 12px; padding: 10px 14px; background: #fff; text-align: center; }
    .stat-label { font-size: 11px; color: var(--muted); }
    .stat-value { font-size: 20px; font-weight: 800; color: var(--navy); margin-top: 2px; line-height: 1.2; font-variant-numeric: tabular-nums; }
    .stat-on .stat-value { color: var(--excel); }
    .stat-off .stat-value { color: var(--danger); }

    .search { position: relative; }
    .search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
    .toolbar .search .input { padding-left: 36px; width: 280px; }

    .user-cell { min-width: 200px; }
    .user-cell strong { display: block; color: var(--navy); font-weight: 600; }
    .sub { display: block; font-size: 12px; color: var(--muted); margin-top: 2px; }
    .you { font-size: 10px; font-weight: 700; color: var(--primary); background: #e8f0fe; padding: 1px 6px; border-radius: 999px; margin-left: 6px; vertical-align: 1px; text-transform: uppercase; }
    .muted { color: var(--muted); }
    .nowrap { white-space: nowrap; }

    .status { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; white-space: nowrap; }
    .status::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .status-on { color: var(--excel); }
    .status-off { color: #8a94a3; }

    .icon-btn {
        display: inline-grid; place-items: center; width: 34px; height: 34px; border-radius: 8px;
        border: 1px solid transparent; background: none; color: var(--muted); cursor: pointer; text-decoration: none;
        transition: background .12s, color .12s, border-color .12s;
    }
    .icon-btn:hover { background: #eef4ff; color: var(--primary); border-color: #d6e1ef; }
    .icon-btn.danger:hover { background: var(--danger-bg); color: var(--danger); border-color: #f3c4bd; }
    tr.inactive .user-cell strong, tr.inactive td { color: #7b8594; }

    @media (max-width: 900px) {
        .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .stat { padding: 10px 12px; }
        .stat-value { font-size: 20px; }
        .toolbar, .toolbar .search, .toolbar .search .input { width: 100%; }
    }
@endsection

@section('page')
<div class="panel">
    <div class="panel-head">
        <div>
            <h1>Хэрэглэгч</h1>
            <p>{{ $search !== '' ? '«'.$search.'» хайлтаар '.$users->total().' хэрэглэгч олдлоо' : 'Системийн хэрэглэгчдийг удирдах' }}</p>
        </div>
        <div class="toolbar">
            <form method="GET" action="{{ route('settings.users.index') }}" class="search" role="search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $search }}" class="input" placeholder="Нэр, нэвтрэх нэр, и-мэйл..." aria-label="Хэрэглэгч хайх">
            </form>
            <a href="{{ route('settings.users.create') }}" class="btn">+ Хэрэглэгч нэмэх</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="stat-label">Нийт</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
        </div>
        <div class="stat stat-on">
            <div class="stat-label">Идэвхтэй</div>
            <div class="stat-value">{{ $stats['active'] }}</div>
        </div>
        <div class="stat stat-off">
            <div class="stat-label">Идэвхгүй</div>
            <div class="stat-value">{{ $stats['total'] - $stats['active'] }}</div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Хэрэглэгч</th>
                    <th>Алба, хэлтэс / Албан тушаал</th>
                    <th>Эрхийн түвшин</th>
                    <th>Төлөв</th>
                    <th>Сүүлд нэвтэрсэн</th>
                    <th><span class="sr-only">Үйлдэл</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $row)
                    @php $isMe = $row->is(auth()->user()); @endphp
                    <tr class="{{ $row->is_active ? '' : 'inactive' }}">
                        <td>
                            <div class="user-cell">
                                <span>
                                    <strong>{{ $row->name }}@if ($isMe)<span class="you">Та</span>@endif</strong>
                                    <span class="sub">{{ $row->username }}@if ($row->email) · {{ $row->email }}@endif</span>
                                </span>
                            </div>
                        </td>
                        <td>
                            @if ($row->department || $row->position)
                                {{ $row->department?->name ?? '—' }}
                                <span class="sub">{{ $row->position?->name ?? 'Албан тушаалгүй' }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $row->isAdmin() ? 'badge-admin' : 'badge-user' }}">{{ $row->role?->name ?? '—' }}</span></td>
                        <td><span class="status {{ $row->is_active ? 'status-on' : 'status-off' }}">{{ $row->is_active ? 'Идэвхтэй' : 'Идэвхгүй' }}</span></td>
                        <td class="nowrap">
                            @if ($row->last_login_at)
                                {{ $row->last_login_at->format('Y-m-d') }}
                                <span class="sub">{{ $row->last_login_at->format('H:i') }}</span>
                            @else
                                <span class="muted">Нэвтрээгүй</span>
                            @endif
                        </td>
                        <td class="actions">
                            <a href="{{ route('settings.users.edit', $row) }}" class="icon-btn" title="Засах" aria-label="{{ $row->name }} засах">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            @unless ($isMe)
                                <form method="POST" action="{{ route('settings.users.destroy', $row) }}" data-confirm="«{{ $row->name }}» хэрэглэгчийг устгах уу?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-btn danger" title="Устгах" aria-label="{{ $row->name }} устгах">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">
                        {{ $search !== '' ? 'Хайлтад тохирох хэрэглэгч олдсонгүй.' : 'Хэрэглэгч бүртгэгдээгүй байна.' }}
                        @if ($search !== '')
                            <div style="margin-top:8px"><a href="{{ route('settings.users.index') }}">Хайлтыг цэвэрлэх</a></div>
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="pagination">{{ $users->links('pagination::simple-default') }}</div>
    @endif
</div>
@endsection
