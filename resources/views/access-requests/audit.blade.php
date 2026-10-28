@extends('layouts.app')

@section('title', 'МАБ · Хяналт, аудит')

@section('page-styles')
    .table-wrap { overflow-x: auto; margin-top: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e8edf3; vertical-align: top; }
    th { color: var(--table-head); font-weight: 700; font-size: 12px; white-space: nowrap; background: var(--th-bg); }
    .num { font-weight: 700; color: var(--primary); text-decoration: none; white-space: nowrap; }
    .sub { display: block; font-size: 12px; color: var(--muted); margin-top: 2px; }
    .nowrap { white-space: nowrap; }
    .flag { display: inline-block; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: var(--danger-bg); color: var(--danger); white-space: nowrap; }
    .ok { color: var(--excel); font-weight: 600; }
    tr.stale td { background: #fffaf5; }
    .rules { margin: 12px 0 0; padding-left: 18px; font-size: 13px; color: var(--muted); line-height: 1.7; }
    .empty { text-align: center; padding: 32px 16px; color: var(--muted); }
    .inline-form { display: inline; }
    .btn-sm { height: 32px; padding: 0 12px; font-size: 13px; }
@endsection

@section('page')
@use('App\Models\AccessRequest')

<div class="panel">
    <div class="panel-head">
        <div>
            <h1>7. Тогтмол хяналт & аудит</h1>
            <p>Идэвхтэй {{ $active->count() }} эрх@if ($needsReview) · <strong style="color:var(--danger)">{{ $needsReview }} нь {{ AccessRequest::REVIEW_DAYS }}+ хоног хянагдаагүй</strong>@endif</p>
        </div>
        <a href="{{ route('access-requests.index') }}" class="btn btn-ghost">← МАБ хүсэлтүүд</a>
    </div>
    <ul class="rules">
        <li>{{ AccessRequest::REVIEW_DAYS }} хоног хянагдаагүй эрхийг шалгаж, идэвхгүй бол цуцална. Өдөр бүр МАБ инженерт жагсаалт мэйлээр очно.</li>
        <li>{{ AccessRequest::PASSWORD_MONTHS }} сар тутам хэрэглэгчид нууц үг солих сануулга автоматаар очно.</li>
        <li>Хэрэглэгч ажлаас гарсан (идэвхгүй болгосон) эсвэл өөр алба, хэлтэс рүү шилжсэн үед эрх шууд автоматаар цуцлагдана.</li>
        <li>Дуусах огноо өнгөрсөн эрх автоматаар цуцлагдана. Цуцлах бүрт системийн инженерт системээс хаахаар мэйл очно.</li>
    </ul>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Дугаар</th>
                    <th>Хэрэглэгч</th>
                    <th>Систем · ID</th>
                    <th>Эрх, хугацаа</th>
                    <th>Олгосон</th>
                    <th>Сүүлд хянасан</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($active as $r)
                    <tr class="{{ $r->needsReview() ? 'stale' : '' }}">
                        <td><a href="{{ route('access-requests.show', $r) }}" class="num">{{ $r->number }}</a></td>
                        <td>{{ $r->user?->name }}<span class="sub">{{ $r->user?->department?->name }}</span></td>
                        <td>{{ $r->system }}<span class="sub">{{ $r->requestTypeLabel() }} · ID: {{ $r->account_id ?? '—' }}</span></td>
                        <td class="nowrap">{{ $r->accessTypeLabel() }}<span class="sub">{{ $r->periodLabel() }}</span></td>
                        <td class="nowrap">{{ $r->granted_at?->format('Y-m-d') }}</td>
                        <td class="nowrap">
                            {{ ($r->last_reviewed_at ?? $r->granted_at)?->format('Y-m-d') }}
                            @if ($r->needsReview()) <span class="flag">{{ AccessRequest::REVIEW_DAYS }}+ хоног</span> @else <span class="sub ok">Хэвийн</span> @endif
                        </td>
                        <td class="nowrap">
                            <form method="POST" action="{{ route('access-requests.review', $r) }}" class="inline-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-ghost btn-sm">Хянасан</button>
                            </form>
                            <a href="{{ route('access-requests.show', $r) }}#revoke_reason" class="btn btn-ghost btn-sm">Цуцлах…</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Идэвхтэй эрх алга.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h3 style="margin: 0 0 4px">Аудит лог</h3>
    <p>МАБ-ын хүсэлтийн бүх үйлдэл, хэн хэзээ хийсэн.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Огноо</th><th>Хүсэлт</th><th>Үйлдэл</th><th>Хэн</th><th>Тайлбар</th></tr>
            </thead>
            <tbody>
                @forelse ($log as $step)
                    <tr>
                        <td class="nowrap">{{ $step->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="nowrap">
                            @if ($step->accessRequest)
                                <a href="{{ route('access-requests.show', $step->accessRequest) }}" class="num">{{ $step->accessRequest->number }}</a>
                                <span class="sub">{{ $step->accessRequest->user?->name }}</span>
                            @endif
                        </td>
                        <td>{{ $step->label() }}</td>
                        <td>{{ $step->user?->name ?? 'Систем (автоматаар)' }}</td>
                        <td>{{ $step->note }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Лог алга.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $log->links('pagination::simple-default') }}</div>
</div>
@endsection
