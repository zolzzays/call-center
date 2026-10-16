@extends('layouts.app')

@section('title', $labels['title'])

@section('page')
@php $listFields = array_filter($fields, fn ($f) => $f['list'] ?? false); @endphp

<div class="panel">
    <div class="panel-head">
        <div>
            <h1>{{ $labels['title'] }}</h1>
            <p>Нийт {{ $items->total() }} бичлэг</p>
        </div>
        <div class="toolbar">
            <form method="GET" action="{{ route($routePrefix.'.index') }}">
                <input type="search" name="q" value="{{ $search }}" class="input" placeholder="Нэрээр хайх...">
            </form>
            <a href="{{ route($routePrefix.'.create') }}" class="btn">+ Нэмэх</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    @foreach ($listFields as $field)
                        <th>{{ $field['label'] }}</th>
                    @endforeach
                    @foreach ($extraColumns as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        @foreach ($listFields as $name => $field)
                            <td>
                                @if ($field['type'] === 'checkbox')
                                    <span class="badge {{ $item->$name ? 'badge-on' : 'badge-off' }}">{{ $item->$name ? 'Тийм' : 'Үгүй' }}</span>
                                @elseif ($field['type'] === 'select')
                                    {{ data_get($item, $field['display']) ?? '—' }}
                                @elseif ($field['type'] === 'textarea')
                                    {{ \Illuminate\Support\Str::limit($item->$name, 80) ?: '—' }}
                                @else
                                    {{ $item->$name }}
                                @endif
                            </td>
                        @endforeach
                        @foreach ($extraColumns as $column => $label)
                            <td>{{ $item->$column }}</td>
                        @endforeach
                        <td class="actions">
                            <a href="{{ route($routePrefix.'.edit', $item) }}" class="link-btn">Засах</a>
                            <form method="POST" action="{{ route($routePrefix.'.destroy', $item) }}" data-confirm="«{{ $item->name }}»-г устгах уу?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-btn danger">Устгах</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($listFields) + count($extraColumns) + 2 }}" class="empty">Бүртгэл байхгүй байна.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($items->hasPages())
        <div class="pagination">{{ $items->links('pagination::simple-default') }}</div>
    @endif
</div>
@endsection
