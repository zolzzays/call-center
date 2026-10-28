@extends('layouts.app')

@section('title', $labels['title'])

@section('page-styles')
    .systems { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
    .sys {
        display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--card-border);
        background: #fff; color: var(--text); font-size: 14px; font-weight: 600; text-decoration: none;
    }
    .sys:hover { border-color: #c9d6e6; background: #f8fafc; }
    .sys.active { border-color: var(--primary); background: #e8f0fe; color: var(--primary); }
    .sys .count { font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: #eef1f4; color: var(--muted); }
    .sys.active .count { background: #fff; color: var(--primary); }

    .group-row td { background: var(--th-bg); font-weight: 700; color: var(--navy); }
    .module-row td.name { padding-left: 36px; }
    .module-row td.name::before { content: '└'; color: #b5c3d6; margin-right: 8px; }
    .add-row td { padding-top: 6px; padding-bottom: 12px; }
    .add-row td.name { padding-left: 36px; }
    .muted-cell { color: var(--muted); }
    .empty-state { padding: 40px 16px; text-align: center; color: var(--muted); }
    .empty-state strong { display: block; color: var(--navy); font-size: 15px; margin-bottom: 6px; }
@endsection

@section('page')
<div class="panel">
    <div class="panel-head">
        <div>
            <h1>{{ $labels['title'] }}</h1>
            <p>Програм хангамжийн системээ сонгоход тухайн системийн бүлэг, модулиуд харагдана.</p>
        </div>
        @if ($system)
            <div class="toolbar">
                <a href="{{ route($routePrefix.'.create', ['system' => $system->id]) }}" class="btn">+ Бүлэг нэмэх</a>
            </div>
        @endif
    </div>

    @if ($systems->isEmpty())
        <div class="empty-state">
            <strong>Програм хангамжийн систем бүртгэлгүй байна</strong>
            Эхлээд Тохиргоо → Засвар ангилал хэсэгт «Програм хангамж» төрлийн систем нэмнэ үү.
        </div>
    @else
        <nav class="systems" aria-label="Систем сонгох">
            @foreach ($systems as $s)
                <a href="{{ route($routePrefix.'.index', ['system' => $s->id]) }}" class="sys {{ $system?->is($s) ? 'active' : '' }}" @if ($system?->is($s)) aria-current="page" @endif>
                    {{ $s->name }} <span class="count">{{ $s->modules_count }}</span>
                </a>
            @endforeach
        </nav>

        @if ($groups->isEmpty())
            <div class="empty-state">
                <strong>{{ $system->name }} системд модуль бүртгэгдээгүй байна</strong>
                Эхлээд бүлэг (жишээ нь «Шуурхай ажиллагаа») нэмээд, түүнд модулиудаа бүртгэнэ үү.
                <div style="margin-top: 14px">
                    <a href="{{ route($routePrefix.'.create', ['system' => $system->id]) }}" class="btn">+ Бүлэг нэмэх</a>
                </div>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Бүлэг / модуль</th>
                            <th>Тайлбар</th>
                            <th>Идэвхтэй</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $group)
                            @foreach ([$group, ...$group->children] as $item)
                                <tr class="{{ $item->parent_id ? 'module-row' : 'group-row' }}">
                                    <td class="name">
                                        {{ $item->name }}
                                        @unless ($item->parent_id) <span class="muted-cell" style="font-weight: 400">· {{ $group->children->count() }} модуль</span> @endunless
                                    </td>
                                    <td class="muted-cell">{{ \Illuminate\Support\Str::limit($item->description, 80) ?: '—' }}</td>
                                    <td><span class="badge {{ $item->is_active ? 'badge-on' : 'badge-off' }}">{{ $item->is_active ? 'Тийм' : 'Үгүй' }}</span></td>
                                    <td class="actions">
                                        <a href="{{ route($routePrefix.'.edit', $item) }}" class="link-btn">Засах</a>
                                        <form method="POST" action="{{ route($routePrefix.'.destroy', $item) }}" data-confirm="«{{ $item->name }}»-г устгах уу?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-btn danger">Устгах</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="add-row">
                                <td class="name" colspan="4">
                                    <a href="{{ route($routePrefix.'.create', ['system' => $system->id, 'parent' => $group->id]) }}" class="link-btn">+ «{{ $group->name }}»-д модуль нэмэх</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
@endsection
