@extends('layouts.base')

@section('styles')
    .shell { display: flex; min-height: 100vh; }
    .sidebar {
        width: 248px; flex-shrink: 0; background: #fff; border-right: 1px solid #dfe6ef;
        display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh;
    }
    .sidebar .brand { padding: 0 20px; height: 64px; border-bottom: 1px solid #e8edf3; }
    .nav { padding: 16px 12px; display: flex; flex-direction: column; gap: 4px; flex: 1; overflow-y: auto; }
    .nav-label { font-size: 11px; font-weight: 700; color: #8a94a3; text-transform: uppercase; letter-spacing: .06em; padding: 8px 12px; }
    .nav-item {
        display: flex; align-items: center; gap: 12px; width: 100%; padding: 10px 12px; border-radius: 6px;
        font: inherit; font-size: 14px; font-weight: 500; color: var(--text); text-decoration: none; text-align: left;
        background: transparent; border: 0; cursor: pointer; transition: background .15s, color .15s;
    }
    .nav-item svg { flex-shrink: 0; color: #7a8698; }
    .nav-item:hover { background: #f1f5fa; }
    .nav-item.active { background: #e8f0fe; color: var(--primary); font-weight: 600; }
    .nav-item.active svg { color: var(--primary); }
    .nav-item.parent-active { color: var(--primary); font-weight: 600; }
    .nav-item.parent-active svg { color: var(--primary); }
    .nav-item .chevron { margin-left: auto; transition: transform .2s; }
    .nav-group.open .chevron { transform: rotate(180deg); }
    .submenu { display: none; flex-direction: column; gap: 2px; margin: 2px 0 4px 21px; padding-left: 12px; border-left: 1px solid #e2e8f0; }
    .nav-group.open .submenu { display: flex; }
    .submenu a {
        padding: 8px 12px; border-radius: 6px; font-size: 14px; color: var(--muted); text-decoration: none;
        transition: background .15s, color .15s;
    }
    .submenu a:hover { background: #f1f5fa; color: var(--text); }
    .submenu a.active { background: #e8f0fe; color: var(--primary); font-weight: 600; }

    .sidebar-foot { padding: 16px 12px; border-top: 1px solid #e8edf3; }
    .sidebar-foot .who {
        display: block; padding: 8px 12px; margin-bottom: 8px; border-radius: 6px;
        font-size: 13px; text-decoration: none; color: var(--text);
    }
    .sidebar-foot .who:hover, .sidebar-foot .who.active { background: #f1f5fa; }
    .sidebar-foot .who strong { display: block; color: var(--navy); margin-bottom: 4px; }
    .sidebar-foot .btn { width: 100%; height: 36px; }

    .main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .topbar {
        height: 64px; background: #fff; border-bottom: 1px solid #dfe6ef;
        display: flex; align-items: center; gap: 12px; padding: 0 24px;
    }
    .topbar h2 { margin: 0; font-size: 17px; font-weight: 800; color: var(--navy); }
    .menu-toggle { display: none; border: 0; background: transparent; cursor: pointer; padding: 6px; color: var(--navy); }
    .backdrop { display: none; }

    .badge {
        display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px;
        text-transform: uppercase; letter-spacing: .04em;
    }
    .badge-admin { background: #e8f0fe; color: var(--primary); }
    .badge-user { background: #eef1f4; color: var(--muted); }
    .badge-on { background: #e7f5ec; color: var(--excel); }
    .badge-off { background: #fdecea; color: var(--danger); }
    /* Төлөв, яаралтай байдлын тэмдэг: цагаан дэвсгэр, өнгөт хүрээ */
    .badge-normal, .badge-urgent, .badge-critical, .badge-new, .badge-assigned, .badge-postponed, .badge-closed {
        background: #fff; border: 1px solid currentColor; text-transform: none; letter-spacing: 0; font-size: 11px; padding: 2px 8px;
    }
    .badge-normal { color: #2563eb; }
    .badge-urgent { color: #c2650a; }
    .badge-critical { color: #d93a3f; }
    .badge-new { color: #2f6fb0; }
    .badge-assigned { color: #6d5bd0; }
    .badge-postponed { color: #b7791f; }
    .badge-closed { color: var(--excel); }

    .container { width: 100%; max-width: 1100px; padding: 32px 24px; }
    /* Олон баганатай хүснэгттэй хуудсанд: @section('container', 'wide') */
    .container.wide { max-width: 1600px; }
    .panel { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--card-radius); padding: 20px; box-shadow: var(--card-shadow); }
    .panel + .panel { margin-top: 20px; }
    .panel h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: var(--navy); }
    .panel h3 { margin: 0 0 6px; font-size: 16px; font-weight: 700; color: var(--card-title); }
    .panel p { margin: 0; color: var(--muted); font-size: 13px; }
    .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .toolbar { display: flex; gap: 8px; flex-wrap: wrap; }
    .toolbar .input { width: 240px; }

    .table-wrap { overflow-x: auto; margin-top: 20px; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e8edf3; }
    td { vertical-align: middle; }
    th { color: var(--table-head); font-weight: 700; font-size: 12px; white-space: nowrap; background: var(--th-bg); border-bottom-color: #e2e9eb; }
    th:first-child { border-top-left-radius: 8px; }
    th:last-child { border-top-right-radius: 8px; }
    tbody tr { transition: background .12s; }
    tbody tr:hover { background: #f7faff; }
    td.actions { text-align: right; white-space: nowrap; }
    td.actions form { display: inline; }
    .empty { text-align: center; color: var(--muted); padding: 32px 12px; }
    .link-btn { background: none; border: 0; padding: 4px 8px; font: inherit; font-size: 13px; font-weight: 600; color: var(--primary); cursor: pointer; text-decoration: none; }
    .link-btn.danger { color: var(--danger); }
    .link-btn:hover { text-decoration: underline; }

    .form { margin-top: 20px; max-width: 520px; }
    .form.form-wide { max-width: 760px; }
    .form-section { border: 1px solid var(--card-border); border-radius: var(--card-radius); padding: 20px 20px 4px; margin-bottom: 18px; background: #fff; box-shadow: var(--card-shadow); }
    .panel .form-section-title { margin: 0 0 16px; font-size: 13px; font-weight: 800; color: var(--card-title); text-transform: uppercase; letter-spacing: .02em; }
    /* Хянах самбарын картын гарчиг: том үсэг, тод, доор нь цэнхэр тайлбар */
    .tile-title { margin: 0; font-size: 13px; font-weight: 800; color: var(--card-title); text-transform: uppercase; letter-spacing: .02em; line-height: 1.3; }
    .tile-sub { display: block; margin-top: 3px; font-size: 11px; font-weight: 600; color: var(--primary); text-transform: none; letter-spacing: 0; }
    /* Хүрээтэй тоон хайрцаг */
    .sbox { display: block; border: 1px solid var(--card-border); border-radius: 12px; padding: 10px 14px; background: #fff; text-decoration: none; color: inherit; text-align: center; transition: border-color .15s, background .15s; }
    a.sbox:hover { border-color: #c9d6e6; background: #fafcff; }
    .sbox.active { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .sbox-label { display: block; font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sbox-value { display: block; font-size: 20px; font-weight: 800; color: var(--navy); margin-top: 2px; font-variant-numeric: tabular-nums; }
    .req { color: var(--danger); font-weight: 700; }
    .field { margin-bottom: 18px; }
    .field > label { display: block; font-size: 13px; font-weight: 600; color: var(--label); margin-bottom: 8px; }
    .input {
        width: 100%; height: 40px; padding: 0 12px; font: inherit; font-size: 14px; color: var(--text);
        background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; outline: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .input:hover:not(:disabled):not([readonly]) { border-color: #a9bdd6; }
    select.input {
        appearance: none; -webkit-appearance: none; padding-right: 36px; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%235b6676' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center;
    }
    input[type=file].input { height: auto; padding: 8px; cursor: pointer; }
    input[type=file].input::file-selector-button { font: inherit; font-size: 13px; font-weight: 600; margin-right: 12px; padding: 6px 12px; border: 1px solid var(--border); border-radius: 6px; background: #fff; color: var(--primary); cursor: pointer; }
    textarea.input { height: auto; min-height: 90px; padding: 10px 12px; resize: vertical; }
    .input:focus { border-color: var(--primary); background-color: #fff; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
    .input[readonly], .input:disabled { color: var(--muted); background-color: #eceff3; cursor: not-allowed; }
    .switch { display: inline-flex; align-items: center; gap: 10px; height: 42px; font-size: 14px; cursor: pointer; user-select: none; }
    .switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .switch-track { position: relative; width: 40px; height: 22px; border-radius: 999px; background: #c5cfdb; transition: background .15s; flex-shrink: 0; }
    .switch-track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .2); transition: transform .15s; }
    .switch input:checked + .switch-track { background: var(--primary); }
    .switch input:checked + .switch-track::after { transform: translateX(18px); }
    .switch input:focus-visible + .switch-track { box-shadow: 0 0 0 3px rgba(37, 99, 235, .25); }
    .switch input:disabled + .switch-track { opacity: .5; cursor: not-allowed; }
    .field-hint { font-size: 12px; color: var(--muted); margin-top: 6px; }
    .error { color: var(--danger); font-size: 12px; margin-top: 6px; }
    .check { display: inline-flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; }
    .check input { width: 15px; height: 15px; margin: 0; accent-color: var(--primary); }
    .form-actions { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }

    .flash { font-size: 14px; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid; }
    .flash-success { background: #e7f5ec; color: var(--excel); border-color: #bfe3cd; }
    .flash-error { background: var(--danger-bg); color: var(--danger); border-color: #f3c4bd; }
    .pagination { margin-top: 16px; font-size: 14px; }
    .pagination ul { display: flex; gap: 8px; list-style: none; margin: 0; padding: 0; justify-content: flex-end; }
    .pagination li > a, .pagination li > span {
        display: inline-flex; align-items: center; height: 36px; padding: 0 14px; border-radius: 8px;
        border: 1px solid var(--border); background: #fff; color: var(--primary); font-weight: 600; text-decoration: none;
    }
    .pagination li > a:hover { background: #eef4ff; }
    .pagination li.disabled > span { color: #9aa4b2; background: #f5f7fa; cursor: not-allowed; }
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

    @media (max-width: 900px) {
        .sidebar { position: fixed; left: 0; top: 0; z-index: 20; transform: translateX(-100%); transition: transform .2s; }
        .shell.nav-open .sidebar { transform: none; }
        .shell.nav-open .backdrop { display: block; position: fixed; inset: 0; background: rgba(15, 30, 50, .35); z-index: 10; }
        .menu-toggle { display: inline-flex; }
        .container { padding: 20px 16px; }
        .toolbar .input { width: 100%; }
        .grid-2 { grid-template-columns: 1fr; }
        .panel { padding: 20px 16px; }
        .form-section { padding: 16px 14px 2px; }
    }
    @yield('page-styles')
@endsection

@php
    $user = auth()->user();
    $dashboardRoute = $user->isAdmin() ? 'admin.dashboard' : 'dashboard';
    $settingsMenu = [
        'settings.users' => 'Хэрэглэгч',
        'settings.roles' => 'Эрхийн түвшин',
        'settings.positions' => 'Албан тушаал',
        'settings.departments' => 'Алба, хэлтэс',
        'settings.repair-types' => 'Засвар төрөл',
        'settings.repair-categories' => 'Засвар ангилал',
    ];
    $settingsOpen = request()->routeIs('settings.*');
@endphp

@section('content')
<div class="shell" id="shell">
    <aside class="sidebar">
        <a href="{{ url('/') }}" class="brand">
            @include('partials.brand-logo')
            <span>
                <div class="brand-name">Диспетчерийн үндэсний төв</div>
            </span>
        </a>

        <nav class="nav">
            <div class="nav-label">Цэс</div>
            <a href="{{ route($dashboardRoute) }}" class="nav-item {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                Хянах самбар
            </a>
            @if ($user->canSubmitTickets())
                <a href="{{ route('tickets.create') }}" class="nav-item {{ request()->routeIs('tickets.create') ? 'active' : '' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Хүсэлт илгээх
                </a>
            @endif
            <a href="{{ route('tickets.index') }}" class="nav-item {{ request()->routeIs('tickets.index', 'tickets.show') ? 'active' : '' }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                {{ $user->isAdmin() ? 'Хүсэлт илгээх' : ($user->isAssignee() ? 'Ирсэн хүсэлт' : 'Миний дуудлага') }}
            </a>

            @if ($user->isAdmin())
                <div class="nav-group {{ $settingsOpen ? 'open' : '' }}" id="settingsGroup">
                    <button type="button" class="nav-item {{ $settingsOpen ? 'parent-active' : '' }}" id="settingsToggle" aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}" aria-controls="settingsMenu">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Тохиргоо
                        <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="submenu" id="settingsMenu">
                        @foreach ($settingsMenu as $route => $label)
                            <a href="{{ route($route.'.index') }}" class="{{ request()->routeIs($route.'.*') ? 'active' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </nav>

        <div class="sidebar-foot">
            <a href="{{ route('profile') }}" class="who {{ request()->routeIs('profile*') ? 'active' : '' }}" title="Миний профайл">
                <strong>{{ $user->name }}</strong>
                <span class="badge {{ $user->isAdmin() ? 'badge-admin' : 'badge-user' }}">{{ $user->role?->name ?? '—' }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost">Гарах</button>
            </form>
        </div>
    </aside>
    <div class="backdrop" id="backdrop"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Цэс нээх">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
            <h2>@yield('title')</h2>
        </header>

        <div class="container @yield('container')">
            @if (session('status'))
                <div class="flash flash-success" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="flash flash-error" role="alert">{{ session('error') }}</div>
            @endif

            @yield('page')
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        const shell = document.getElementById('shell');
        document.getElementById('menuToggle').addEventListener('click', () => shell.classList.add('nav-open'));
        document.getElementById('backdrop').addEventListener('click', () => shell.classList.remove('nav-open'));

        const group = document.getElementById('settingsGroup');
        const toggle = document.getElementById('settingsToggle');
        if (group && toggle) {
            toggle.addEventListener('click', () => {
                const open = group.classList.toggle('open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (e) => {
                if (!confirm(form.dataset.confirm)) e.preventDefault();
            });
        });
    })();
</script>
@endsection
