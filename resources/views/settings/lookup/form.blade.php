@extends('layouts.app')

@section('title', $labels['title'])

@section('page')
@php $editing = $item->exists; @endphp

<div class="panel">
    <h1>{{ $editing ? $labels['singular'].' засах' : 'Шинэ '.mb_strtolower($labels['singular']) }}</h1>

    <form method="POST" action="{{ $editing ? route($routePrefix.'.update', $item) : route($routePrefix.'.store') }}" class="form">
        @csrf
        @if ($editing) @method('PUT') @endif

        @foreach ($fields as $name => $field)
            @php $isLocked = in_array($name, $locked, true); @endphp
            <div class="field">
                @if ($field['type'] === 'checkbox')
                    <label class="switch">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $editing ? $item->$name : true))>
                        <span class="switch-track"></span>
                        <span>{{ $field['label'] }}</span>
                    </label>
                @else
                    <label for="{{ $name }}">{{ $field['label'] }}</label>
                    @if ($field['type'] === 'select')
                        <select id="{{ $name }}" name="{{ $name }}" class="input" @required($field['required'] ?? true)>
                            <option value="">{{ $field['placeholder'] ?? '— Сонгох —' }}</option>
                            @foreach ($options[$name] as $value => $text)
                                <option value="{{ $value }}" @selected(old($name, $item->$name) == $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                    @elseif ($field['type'] === 'textarea')
                        <textarea id="{{ $name }}" name="{{ $name }}" class="input" rows="3">{{ old($name, $item->$name) }}</textarea>
                    @else
                        <input id="{{ $name }}" name="{{ $name }}" type="text" class="input"
                               value="{{ old($name, $item->$name) }}" @readonly($isLocked)>
                    @endif
                @endif

                @if ($isLocked)
                    <div class="field-hint">Системийн утга тул өөрчлөх боломжгүй.</div>
                @elseif (! empty($field['hint']))
                    <div class="field-hint">{{ $field['hint'] }}</div>
                @endif
                @error($name) <div class="error">{{ $message }}</div> @enderror
            </div>
        @endforeach

        <div class="form-actions">
            <button type="submit" class="btn">Хадгалах</button>
            <a href="{{ route($routePrefix.'.index') }}" class="btn btn-ghost">Буцах</a>
        </div>
    </form>
</div>
@endsection
