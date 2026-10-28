@extends('layouts.app')

@section('title', $r->number)

@section('page-styles')
    .grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start; }
    .grid > div > .panel + .panel, .grid > aside > .panel + .panel { margin-top: 20px; }
    .back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; text-decoration: none; margin-bottom: 14px; }
    .head-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
    .eyebrow { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
    .st { display: inline-block; font-size: 13px; font-weight: 700; padding: 5px 12px; border-radius: 999px; background: #eef2f7; color: var(--navy); }
    .st-pending_head, .st-pending_security, .st-pending_chief { background: #fff7e6; color: #b45309; }
    .st-approved { background: #e8f0fe; color: var(--primary); }
    .st-granted { background: #e7f5ec; color: var(--excel); }
    .st-rejected, .st-revoked, .st-expired { background: var(--danger-bg); color: var(--danger); }
    dl.info { display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 10px 16px; margin: 16px 0 0; font-size: 14px; }
    dl.info dt { color: var(--muted); }
    dl.info dd { margin: 0; color: var(--text); }
    .reason { white-space: pre-wrap; word-break: break-word; line-height: 1.6; }
    .card-title { margin: 0 0 12px; font-size: 13px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: .03em; }
    .action-card { border-color: #d6e1ef; background: linear-gradient(180deg, #f7faff, #fff 60%); }
    .action-card .form-actions { margin-top: 8px; }
    .btn-success { background: var(--excel); }
    .btn-success:hover { background: #0c6434; }
    .btn-danger { background: var(--danger); }
    .btn-danger:hover { background: #a02f23; }

    .steps { list-style: none; margin: 0; padding: 0; }
    .steps li { position: relative; padding: 0 0 16px 28px; font-size: 13px; color: var(--muted); }
    .steps li:last-child { padding-bottom: 0; }
    .steps li::before { content: ''; position: absolute; left: 0; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: var(--dot, var(--primary)); box-shadow: inset 0 0 0 3px #fff; border: 2px solid var(--dot, var(--primary)); box-sizing: border-box; }
    .steps li.a-approved, .steps li.a-granted, .steps li.a-reviewed { --dot: var(--excel); }
    .steps li.a-rejected, .steps li.a-revoked, .steps li.a-expired { --dot: var(--danger); }
    .steps li.a-reminded { --dot: #d97706; }
    .steps li.a-skipped { --dot: #a3adb6; }
    .steps li.pending { --dot: #cfd8e3; }
    .steps li.pending::before { background: #fff; }
    .steps strong { display: block; color: var(--navy); font-size: 14px; }
    .steps .note { margin-top: 4px; padding: 8px 10px; background: #f6f8fb; border-radius: 6px; color: var(--text); white-space: pre-wrap; }

    @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } dl.info { grid-template-columns: 1fr; gap: 2px 0; } dl.info dd { margin-bottom: 8px; } }
@endsection

@section('page')
@php $me = auth()->user(); @endphp

<a href="{{ route('access-requests.index') }}" class="back">← МАБ хүсэлтүүд</a>

<div class="grid">
    <div>
        <div class="panel">
            <div class="head-row">
                <div>
                    <div class="eyebrow">Хандах эрхийн хүсэлт</div>
                    <h1 style="margin: 4px 0 0">{{ $r->number }}</h1>
                </div>
                <span class="st st-{{ $r->status }}">{{ $r->statusLabel() }}</span>
            </div>
            <dl class="info">
                <dt>Хүсэгч</dt>
                <dd>{{ $r->user?->name }}<span class="sub" style="display:block;color:var(--muted);font-size:12px">{{ $r->user?->department?->name ?? 'Алба, хэлтэс заагаагүй' }}{{ $r->user?->position ? ' · '.$r->user->position->name : '' }}</span></dd>
                <dt>Хүсэлтийн төрөл</dt>
                <dd><strong>{{ $r->requestTypeLabel() }}</strong></dd>
                <dt>Системийн нэр</dt>
                <dd><strong>{{ $r->system }}</strong></dd>
                @if ($r->modules->isNotEmpty())
                    <dt>Модулиуд</dt>
                    <dd>
                        <ul style="margin: 0; padding-left: 18px; line-height: 1.7">
                            @foreach ($r->moduleNames() as $name) <li>{{ $name }}</li> @endforeach
                        </ul>
                    </dd>
                @endif
                <dt>Эрхийн төрөл</dt>
                <dd>{{ $r->accessTypeLabel() }}</dd>
                <dt>Хугацаа</dt>
                <dd>{{ $r->periodLabel() }}</dd>
                <dt>Шалтгаан</dt>
                <dd class="reason">{{ $r->reason }}</dd>
            </dl>
        </div>

        @if ($r->canBeApprovedBy($me))
            <div class="panel action-card">
                <h3 class="card-title">{{ \App\Models\AccessRequest::STAGES[$r->stage()] }} · шийдвэрлэх</h3>
                <form method="POST" action="{{ route('access-requests.approve', $r) }}">
                    @csrf
                    @method('PUT')
                    <div class="field">
                        <label for="note">Тайлбар (заавал биш)</label>
                        <textarea id="note" name="note" class="input" rows="2" maxlength="2000">{{ old('note') }}</textarea>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn btn-success">Зөвшөөрөх</button></div>
                </form>
                <form method="POST" action="{{ route('access-requests.reject', $r) }}" style="margin-top: 16px" data-confirm="Хүсэлтийг татгалзах уу?">
                    @csrf
                    @method('PUT')
                    <div class="field">
                        <label for="reject_reason">Татгалзах шалтгаан</label>
                        <textarea id="reject_reason" name="reject_reason" class="input" rows="2" maxlength="2000">{{ old('reject_reason') }}</textarea>
                        @error('reject_reason') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-actions"><button type="submit" class="btn btn-danger">Татгалзах</button></div>
                </form>
            </div>
        @elseif ($r->canBeGrantedBy($me))
            <form method="POST" action="{{ route('access-requests.grant', $r) }}" class="panel action-card">
                @csrf
                @method('PUT')
                <h3 class="card-title">5–6. Эрх үүсгэж хэрэглэгчид олгох</h3>
                <p style="margin: -4px 0 12px">Бүх шатанд батлагдсан. Системд эрх үүсгээд ID, түр нууц үг, зааврыг хэрэглэгчид илгээнэ үү.</p>
                <div class="grid-2">
                    <div class="field">
                        <label for="account_id">Хэрэглэгчийн нэр / ID <span style="color:var(--danger)">*</span></label>
                        <input id="account_id" name="account_id" class="input" value="{{ old('account_id') }}" maxlength="255" required>
                        @error('account_id') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="temporary_password">Түр нууц үг</label>
                        <input id="temporary_password" name="temporary_password" class="input" maxlength="255" autocomplete="off">
                        <div class="field-hint">Зөвхөн хэрэглэгчид мэйлээр очно, системд хадгалагдахгүй.</div>
                    </div>
                </div>
                <div class="field">
                    <label for="instructions">Заавар</label>
                    <textarea id="instructions" name="instructions" class="input" rows="3" maxlength="5000" placeholder="Жишээ: VPN-ээр холбогдоод https://... хаягаар нэвтэрнэ. Анх нэвтрэхдээ нууц үгээ солино.">{{ old('instructions') }}</textarea>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-success">Эрх олгож илгээх</button></div>
            </form>
        @endif

        @if ($r->account_id && ($r->user_id === $me->id || $r->isVisibleTo($me)))
            <div class="panel">
                <h3 class="card-title">Олгосон эрх</h3>
                <dl class="info" style="margin-top: 0">
                    <dt>Хэрэглэгчийн нэр / ID</dt>
                    <dd><strong>{{ $r->account_id }}</strong></dd>
                    <dt>Олгосон</dt>
                    <dd>{{ $r->granted_at?->format('Y-m-d H:i') }}</dd>
                    @if ($r->instructions)
                        <dt>Заавар</dt>
                        <dd class="reason">{{ $r->instructions }}</dd>
                    @endif
                    @if ($r->revoked_at)
                        <dt>{{ $r->status === 'expired' ? 'Хугацаа дууссан' : 'Цуцалсан' }}</dt>
                        <dd>{{ $r->revoked_at->format('Y-m-d H:i') }} · {{ $r->revoke_reason }}</dd>
                    @endif
                </dl>
            </div>
        @endif

        @if ($r->canBeAuditedBy($me))
            <div class="panel action-card">
                <h3 class="card-title">7. Тогтмол хяналт</h3>
                <p style="margin: -4px 0 12px">
                    Сүүлд хянасан: {{ ($r->last_reviewed_at ?? $r->granted_at)?->format('Y-m-d') }}
                    @if ($r->needsReview()) · <strong style="color:var(--danger)">{{ \App\Models\AccessRequest::REVIEW_DAYS }}+ хоног хянагдаагүй</strong> @endif
                </p>
                <form method="POST" action="{{ route('access-requests.review', $r) }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-ghost">Идэвхтэй ашиглаж байгааг шалгасан</button>
                </form>
                <form method="POST" action="{{ route('access-requests.revoke', $r) }}" style="margin-top: 16px" data-confirm="Эрхийг цуцлах уу? Системийн инженерт хаахаар мэдэгдэнэ.">
                    @csrf
                    @method('PUT')
                    <div class="grid-2">
                        <div class="field">
                            <label for="revoke_reason">Цуцлах шалтгаан</label>
                            <select id="revoke_reason" name="revoke_reason" class="input" required>
                                <option value="">— Сонгох —</option>
                                @foreach (\App\Models\AccessRequest::REVOKE_REASONS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('revoke_reason') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('revoke_reason') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="revoke_note">Тайлбар</label>
                            <input id="revoke_note" name="revoke_note" class="input" maxlength="2000" value="{{ old('revoke_note') }}">
                        </div>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn btn-danger">Эрх цуцлах</button></div>
                </form>
            </div>
        @endif
    </div>

    <aside>
        <div class="panel">
            <h3 class="card-title">Явц</h3>
            <ol class="steps">
                @foreach ($r->steps as $step)
                    <li class="a-{{ $step->action }}">
                        <strong>{{ $step->label() }}</strong>
                        {{ $step->created_at?->format('Y-m-d H:i') }}@if ($step->user) · {{ $step->user->name }}@endif
                        @if ($step->note) <div class="note">{{ $step->note }}</div> @endif
                    </li>
                @endforeach
                @if ($r->stage())
                    <li class="pending">
                        <strong>{{ \App\Models\AccessRequest::STAGES[$r->stage()] }} · хүлээгдэж байна</strong>
                        {{ $approvers->isEmpty() ? 'Энэ шатны батлагч бүртгэлгүй — админ шийдвэрлэнэ' : $approvers->pluck('name')->implode(', ') }}
                    </li>
                @elseif ($r->status === 'approved')
                    <li class="pending"><strong>Системийн инженер · эрх үүсгэх</strong>{{ \App\Models\AccessRequest::staff('system')->pluck('name')->implode(', ') ?: 'Системийн инженер бүртгэлгүй — админ олгоно' }}</li>
                @elseif ($r->status === 'granted')
                    <li class="pending"><strong>МАБ инженер · тогтмол хяналт</strong>{{ \App\Models\AccessRequest::REVIEW_DAYS }} хоног тутам хянаж, {{ \App\Models\AccessRequest::PASSWORD_MONTHS }} сар тутам нууц үг солих сануулга очно</li>
                @endif
            </ol>
        </div>
    </aside>
</div>
@endsection
