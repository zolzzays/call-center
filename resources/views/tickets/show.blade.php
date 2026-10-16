@extends('layouts.app')

@section('title', '№'.$ticket->number)

@section('page-styles')
    .u-normal { --u: #2563eb; --u-bg: #eef4ff; }
    .u-urgent { --u: #d97706; --u-bg: #fdf3e2; }
    .u-critical { --u: #c0392b; --u-bg: #fdecea; }

    .back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; text-decoration: none; margin-bottom: 14px; }
    .back:hover { text-decoration: underline; }

    .ticket-head { position: relative; overflow: hidden; padding-top: 30px; }
    .ticket-head::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: var(--u); }
    .head-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .eyebrow { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
    .panel .ticket-no { margin: 4px 0 6px; font-size: 26px; font-variant-numeric: tabular-nums; }
    .head-meta { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 13px; color: var(--muted); }
    .head-meta span { display: inline-flex; align-items: center; gap: 6px; }
    .pills { display: flex; gap: 8px; flex-wrap: wrap; }
    .pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; white-space: nowrap; background: #fff; border: 1px solid currentColor; }
    .pill::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .pill-normal { color: #2563eb; }
    .pill-urgent { color: #c2650a; }
    .pill-critical { color: #d93a3f; }
    .pill-new { color: #2f6fb0; }
    .pill-assigned { color: #6d5bd0; }
    .pill-postponed { color: #b7791f; }
    .pill-closed { color: var(--excel); }

    .ticket-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start; margin-top: 20px; }
    .ticket-grid > .side { display: grid; gap: 20px; }
    .ticket-grid > .side > .panel { margin-top: 0; }
    .side .panel { padding: 22px; }
    .panel .card-title { display: flex; align-items: center; gap: 8px; margin: 0 0 14px; font-size: 13px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: .05em; }
    .card-title .count { font-size: 11px; background: #e8f0fe; color: var(--primary); padding: 2px 8px; border-radius: 999px; letter-spacing: 0; }

    .description { white-space: pre-wrap; word-break: break-word; font-size: 15px; line-height: 1.7; color: var(--text); }

    .attachments { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
    .attachment {
        display: block; border: 1px solid #e3e9f1; border-radius: 10px; overflow: hidden; color: inherit; text-decoration: none; background: #fff;
        transition: border-color .15s, box-shadow .15s, transform .15s;
    }
    .attachment:hover { border-color: #a9bdd6; box-shadow: 0 6px 16px rgba(16, 38, 66, .08); transform: translateY(-1px); }
    .attachment img { display: block; width: 100%; height: 110px; object-fit: cover; background: var(--input-bg); }
    .attachment .file-icon {
        height: 110px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
        background: #f5f8fc; color: var(--primary); font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: .04em;
    }
    .attachment .meta { padding: 8px 10px; font-size: 12px; color: var(--muted); border-top: 1px solid #eef2f7; }
    .attachment .meta strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: var(--navy); margin-bottom: 1px; }

    .facts { margin: 0; display: grid; gap: 14px; }
    .facts div { display: grid; gap: 3px; }
    .facts dt { font-size: 12px; color: var(--muted); }
    .facts dd { margin: 0; font-size: 14px; font-weight: 600; color: var(--navy); }
    .facts .sub { display: block; font-size: 12px; font-weight: 400; color: var(--muted); margin-top: 1px; }
    .facts hr { border: 0; border-top: 1px solid #eef2f7; margin: 0; }
    .due-late { color: var(--danger); }
    .due-soon { color: #b45309; }

    .person { display: flex; align-items: center; gap: 10px; }
    .avatar { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0; }
    .avatar.empty { background: #e6ebf1; color: #8a94a3; }
    .muted { color: var(--muted); font-weight: 400; }

    .steps { list-style: none; margin: 0; padding: 0; }
    .steps li { position: relative; padding: 0 0 18px 30px; font-size: 13px; color: var(--muted); }
    .steps li:last-child { padding-bottom: 0; }
    .steps li::before {
        content: ''; position: absolute; left: 0; top: 1px; width: 16px; height: 16px; border-radius: 50%;
        border: 2px solid #cfd8e3; background: #fff; box-sizing: border-box;
    }
    .steps li:not(:last-child)::after { content: ''; position: absolute; left: 7px; top: 19px; bottom: 1px; width: 2px; background: #e3e9f1; }
    .steps li.done::before { border-color: var(--primary); background: var(--primary); box-shadow: inset 0 0 0 3px #fff; }
    .steps li.done:not(:last-child)::after { background: var(--primary); }
    .steps strong { display: block; color: var(--navy); font-size: 14px; }
    .steps li:not(.done) strong { color: #8a94a3; font-weight: 600; }

    .assign-card { border-color: #d6e1ef; background: linear-gradient(180deg, #f7faff, #fff 60%); }
    .assign-card p { font-size: 13px; margin: -6px 0 14px; }
    .assign-card .input { margin-bottom: 10px; }
    .assign-card .btn { width: 100%; }

    .banner { display: flex; gap: 14px; align-items: flex-start; border-radius: 12px; padding: 18px 20px; border: 1px solid; margin-bottom: 20px; }
    .banner svg { flex-shrink: 0; margin-top: 2px; }
    .banner strong { display: block; font-size: 15px; }
    .banner .banner-meta { font-size: 13px; margin-top: 2px; opacity: .85; }
    .banner .banner-text { white-space: pre-wrap; word-break: break-word; margin-top: 10px; font-size: 14px; line-height: 1.6; color: var(--text); }
    .banner-closed { background: #f0faf4; border-color: #bfe3cd; color: var(--excel); }
    .banner-postponed { background: #fffaeb; border-color: #f5dfa0; color: #a16207; }

    .work-card { border-color: #d6e1ef; margin-bottom: 20px; }
    .work-card > p { font-size: 13px; margin: -6px 0 16px; }
    .seg { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; padding: 4px; background: #eef2f7; border-radius: 10px; margin-bottom: 16px; }
    .seg button {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 40px; border: 0; border-radius: 8px;
        background: transparent; font: inherit; font-size: 14px; font-weight: 700; color: var(--muted); cursor: pointer;
    }
    .seg button[aria-selected=true] { background: #fff; color: var(--navy); box-shadow: 0 1px 3px rgba(16, 38, 66, .12); }
    .seg button[data-tab=close][aria-selected=true] { color: var(--excel); }
    .seg button[data-tab=postpone][aria-selected=true] { color: #a16207; }
    .work-pane[hidden] { display: none; }
    .work-pane .form-actions { margin-top: 4px; }
    .btn-success { background: var(--excel); }
    .btn-success:hover { background: #0c6434; }
    .btn-warn { background: #b45309; }
    .btn-warn:hover { background: #92400e; }

    .timeline { list-style: none; margin: 0; padding: 0; }
    .timeline li { position: relative; padding: 0 0 18px 30px; font-size: 13px; color: var(--muted); }
    .timeline li:last-child { padding-bottom: 0; }
    .timeline li::before {
        content: ''; position: absolute; left: 0; top: 1px; width: 16px; height: 16px; border-radius: 50%; box-sizing: border-box;
        border: 2px solid var(--dot, var(--primary)); background: var(--dot, var(--primary)); box-shadow: inset 0 0 0 3px #fff;
    }
    .timeline li:not(:last-child)::after { content: ''; position: absolute; left: 7px; top: 19px; bottom: 1px; width: 2px; background: #e3e9f1; }
    .timeline li.pending::before { border-color: #cfd8e3; background: #fff; }
    .timeline li.t-postponed { --dot: #d97706; }
    .timeline li.t-closed { --dot: var(--excel); }
    .timeline strong { display: block; color: var(--navy); font-size: 14px; }
    .timeline li.pending strong { color: #8a94a3; font-weight: 600; }
    .timeline .t-note { margin-top: 6px; padding: 8px 10px; background: #f5f7fa; border-radius: 8px; color: var(--text); white-space: pre-wrap; word-break: break-word; }

    @media (max-width: 1000px) {
        .ticket-grid { grid-template-columns: 1fr; }
        .ticket-grid > .side { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
    }
    @media (max-width: 640px) {
        .panel .ticket-no { font-size: 22px; }
        .pills { width: 100%; }
    }
@endsection

@section('page')
@php
    $me = auth()->user();
    $palette = ['#2563eb', '#0f766e', '#7c3aed', '#c2410c', '#be185d', '#0369a1', '#4d7c0f', '#b45309'];
    $initials = function (?string $name): string {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        return mb_strtoupper(implode('', array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2))) ?: '?');
    };
    $color = fn (?int $id) => $palette[($id ?? 0) % count($palette)];
    $due = $ticket->due_at;
    $dueClass = $due && $due->isPast() ? 'due-late' : ($due && $due->lt(now()->addDay()) ? 'due-soon' : '');
@endphp

<a href="{{ route('tickets.index') }}" class="back">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    Жагсаалт руу буцах
</a>

<div class="panel ticket-head u-{{ $ticket->urgency }}">
    <div class="head-row">
        <div>
            <div class="eyebrow">{{ $ticket->typeLabel() }}</div>
            <h1 class="ticket-no">№{{ $ticket->number }}</h1>
            <div class="head-meta">
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><path d="M7 7h.01"/></svg>
                    {{ $ticket->repairType?->name ?? '—' }}@if ($ticket->repairCategory) / {{ $ticket->repairCategory->name }}@endif
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    {{ $ticket->created_at->format('Y-m-d H:i') }}
                </span>
            </div>
        </div>
        <div class="pills">
            <span class="pill pill-{{ $ticket->urgency }}">{{ $ticket->urgencyLabel() }}</span>
            <span class="pill pill-{{ $ticket->status }}">{{ $ticket->statusLabel() }}</span>
        </div>
    </div>
</div>

<div class="ticket-grid">
    <div class="main-col">
        @php $lastPostpone = $ticket->activities->where('action', 'postponed')->last(); @endphp
        @if ($ticket->isClosed())
            @php $closer = $ticket->activities->where('action', 'closed')->last()?->user; @endphp
            <div class="banner banner-closed" role="status">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>
                <div>
                    <strong>Шийдвэрлэгдэж хаагдсан</strong>
                    <div class="banner-meta">{{ $ticket->closed_at?->format('Y-m-d H:i') }}@if ($closer) · {{ $closer->name }}@endif</div>
                    <div class="banner-text">{{ $ticket->resolution }}</div>
                </div>
            </div>
        @elseif ($ticket->status === 'postponed' && $ticket->postponed_until)
            <div class="banner banner-postponed" role="status">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <div>
                    <strong>{{ $ticket->postponed_until->format('Y-m-d H:i') }} хүртэл хойшлуулсан</strong>
                    <div class="banner-meta">
                        {{ $ticket->postponed_until->isPast() ? 'Хойшлуулсан хугацаа дууссан' : str_replace('одоогоос ', '', $ticket->postponed_until->locale('mn')->diffForHumans(['parts' => 1])) }}
                        @if ($lastPostpone?->user) · {{ $lastPostpone->user->name }}@endif
                    </div>
                    @if ($lastPostpone?->note) <div class="banner-text">{{ $lastPostpone->note }}</div> @endif
                </div>
            </div>
        @endif

        @if ($ticket->canBeWorkedBy($me))
            @php
                $openTab = $errors->hasAny(['postponed_until', 'reason']) ? 'postpone' : 'close';
            @endphp
            <div class="panel work-card">
                <h3 class="card-title">Дуудлагыг гүйцэтгэх</h3>
                <p>Ажлаа дуусгасан бол хааж, одоо хийх боломжгүй бол шинэ хугацаатай хойшлуулна уу. Илгээгчид мэйлээр мэдэгдэнэ.</p>
                <div class="seg" role="tablist">
                    <button type="button" role="tab" data-tab="close" aria-selected="{{ $openTab === 'close' ? 'true' : 'false' }}" aria-controls="pane-close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        Хаах
                    </button>
                    <button type="button" role="tab" data-tab="postpone" aria-selected="{{ $openTab === 'postpone' ? 'true' : 'false' }}" aria-controls="pane-postpone">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        Хойшлуулах
                    </button>
                </div>

                <form method="POST" action="{{ route('tickets.close', $ticket) }}" class="work-pane" id="pane-close" role="tabpanel" data-confirm="Дуудлагыг хаах уу? Хаасны дараа дахин өөрчлөх боломжгүй." @if ($openTab !== 'close') hidden @endif>
                    @csrf
                    @method('PUT')
                    <div class="field">
                        <label for="resolution">Шийдвэрлэсэн байдал <span class="req">*</span></label>
                        <textarea id="resolution" name="resolution" class="input" rows="4" maxlength="5000" required
                                  placeholder="Жишээ: Серверийн диск дүүрсэн байсныг цэвэрлэж, үйлчилгээг дахин ачааллав.">{{ old('resolution') }}</textarea>
                        @error('resolution') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">Шийдвэрлэж хаах</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('tickets.postpone', $ticket) }}" class="work-pane" id="pane-postpone" role="tabpanel" @if ($openTab !== 'postpone') hidden @endif>
                    @csrf
                    @method('PUT')
                    <div class="grid-2">
                        <div class="field">
                            <label for="postponed_until">Хэзээ хүртэл <span class="req">*</span></label>
                            <input id="postponed_until" name="postponed_until" type="datetime-local" class="input" required
                                   value="{{ old('postponed_until') }}" min="{{ now()->format('Y-m-d\TH:i') }}">
                            @error('postponed_until') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="reason">Хойшлуулах шалтгаан <span class="req">*</span></label>
                        <textarea id="reason" name="reason" class="input" rows="3" maxlength="2000" required
                                  placeholder="Жишээ: Сэлбэг захиалсан, 3 хоногийн дараа ирнэ.">{{ old('reason') }}</textarea>
                        @error('reason') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-warn">Хойшлуулах</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="panel">
            <h3 class="card-title">Гэмтэл, хүсэлтийн тайлбар</h3>
            <div class="description">{{ $ticket->description }}</div>
        </div>

        <div class="panel">
            <h3 class="card-title">Хавсралт <span class="count">{{ $ticket->attachments->count() }}</span></h3>
            @if ($ticket->attachments->isEmpty())
                <p>Хавсралт байхгүй.</p>
            @else
                <div class="attachments">
                    @foreach ($ticket->attachments as $file)
                        @php $url = route('tickets.attachments.show', [$ticket, $file]); @endphp
                        <a href="{{ $file->isImage() ? $url.'?inline=1' : $url }}" class="attachment" @if ($file->isImage()) target="_blank" rel="noopener" @endif>
                            @if ($file->isImage())
                                <img src="{{ $url }}?inline=1" alt="{{ $file->original_name }}" loading="lazy">
                            @else
                                <div class="file-icon">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                    {{ pathinfo($file->original_name, PATHINFO_EXTENSION) ?: 'файл' }}
                                </div>
                            @endif
                            <div class="meta">
                                <strong title="{{ $file->original_name }}">{{ $file->original_name }}</strong>
                                {{ $file->humanSize() }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <aside class="side">
        <div class="panel">
            <h3 class="card-title">Дэлгэрэнгүй</h3>
            <dl class="facts">
                <div>
                    <dt>Ангилал</dt>
                    <dd>{{ $ticket->repairType?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Систем, төхөөрөмж</dt>
                    <dd>{{ $ticket->repairCategory?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Хүссэн хугацаа</dt>
                    <dd>
                        @if ($due)
                            <span class="{{ $dueClass }}">{{ $due->format('Y-m-d H:i') }}</span>
                            <span class="sub">{{ $due->isPast() ? 'Хугацаа хэтэрсэн' : str_replace('одоогоос ', '', $due->locale('mn')->diffForHumans(['parts' => 1])) }}</span>
                        @else
                            <span class="muted">Заагаагүй</span>
                        @endif
                    </dd>
                </div>
                <hr>
                <div>
                    <dt>Илгээгч</dt>
                    <dd class="person">
                        <span class="avatar" style="background: {{ $color($ticket->user_id) }}" aria-hidden="true">{{ $initials($ticket->user?->name) }}</span>
                        <span>
                            {{ $ticket->user?->name ?? '—' }}
                            <span class="sub">{{ $ticket->user?->department?->name ?? 'Алба, хэлтэс заагаагүй' }}</span>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt>Гүйцэтгэгч</dt>
                    <dd class="person">
                        @if ($ticket->assignee)
                            <span class="avatar" style="background: {{ $color($ticket->assignee_id) }}" aria-hidden="true">{{ $initials($ticket->assignee->name) }}</span>
                            <span>
                                {{ $ticket->assignee->name }}
                                <span class="sub">{{ $ticket->assigned_at?->format('Y-m-d H:i') }}-д хуваарилсан</span>
                            </span>
                        @else
                            <span class="avatar empty" aria-hidden="true">?</span>
                            <span class="muted">Хуваарилаагүй</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        @if ($me->isAdmin() && ! $ticket->isClosed())
            <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="panel assign-card">
                @csrf
                @method('PUT')
                <h3 class="card-title">{{ $ticket->assignee ? 'Гүйцэтгэгч солих' : 'Гүйцэтгэгчид хуваарилах' }}</h3>
                <p>Сонгосон гүйцэтгэгч энэ дуудлагыг «Ирсэн хүсэлт» хэсгээсээ харна.</p>
                @if ($assignees->isEmpty())
                    <div class="field-hint">Идэвхтэй гүйцэтгэгч алга. «Тохиргоо → Хэрэглэгч» хэсэгт «Гүйцэтгэгч» эрхтэй хэрэглэгч нэмнэ үү.</div>
                @else
                    <select name="assignee_id" class="input" required aria-label="Гүйцэтгэгч">
                        <option value="">— Гүйцэтгэгч сонгох —</option>
                        @foreach ($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected(old('assignee_id', $ticket->assignee_id) == $assignee->id)>
                                {{ $assignee->name }}{{ $assignee->department ? ' — '.$assignee->department->name : '' }}{{ $assignee->is_active ? '' : ' (идэвхгүй)' }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn">{{ $ticket->assignee ? 'Солих' : 'Хуваарилах' }}</button>
                @endif
                @error('assignee_id') <div class="error">{{ $message }}</div> @enderror
            </form>
        @endif

        <div class="panel">
            <h3 class="card-title">Явц</h3>
            <ol class="timeline">
                <li>
                    <strong>Илгээсэн</strong>
                    {{ $ticket->created_at->format('Y-m-d H:i') }} · {{ $ticket->user?->name }}
                </li>
                @foreach ($ticket->activities as $activity)
                    <li class="t-{{ $activity->action }}">
                        <strong>
                            {{ $activity->label() }}@if ($activity->action === 'postponed' && $activity->postponed_until) — {{ $activity->postponed_until->format('Y-m-d H:i') }} хүртэл@endif
                        </strong>
                        {{ $activity->created_at?->format('Y-m-d H:i') }}
                        · {{ $activity->user?->name ?? 'Автоматаар' }}
                        @if ($activity->action === 'assigned' && $activity->assignee) → {{ $activity->assignee->name }} @endif
                        @if ($activity->note) <div class="t-note">{{ $activity->note }}</div> @endif
                    </li>
                @endforeach
                @if ($ticket->activities->isEmpty() && $ticket->assigned_at)
                    {{-- Түүх хадгалж эхлэхээс өмнө хуваарилагдсан дуудлага --}}
                    <li><strong>Гүйцэтгэгчид хуваарилсан</strong>{{ $ticket->assigned_at->format('Y-m-d H:i') }}</li>
                @endif
                @unless ($ticket->isClosed())
                    <li class="pending">
                        <strong>{{ $ticket->assignee_id ? 'Шийдвэрлэх' : 'Гүйцэтгэгчид хуваарилах' }}</strong>
                        Хүлээгдэж байна
                    </li>
                @endunless
            </ol>
        </div>

        @if ($me->canSubmitTickets())
            <a href="{{ route('tickets.create') }}" class="btn btn-ghost">+ Шинэ хүсэлт илгээх</a>
        @endif
    </aside>
</div>

<script>
    (function () {
        const tabs = document.querySelectorAll('.seg [data-tab]');
        tabs.forEach((tab) => tab.addEventListener('click', () => {
            tabs.forEach((t) => {
                const active = t === tab;
                t.setAttribute('aria-selected', active ? 'true' : 'false');
                document.getElementById('pane-' + t.dataset.tab).hidden = !active;
            });
        }));
    })();
</script>
@endsection
