@extends('layouts.app')

@section('title', 'Хэрэглэгч')

@section('page')
@php
    $editing = $user->exists;
    $isSelf = $editing && $user->is(auth()->user());
@endphp

<div class="panel">
    <div class="panel-head">
        <div>
            <h1>{{ $editing ? 'Хэрэглэгч засах' : 'Шинэ хэрэглэгч' }}</h1>
            <p>{{ $editing ? $user->name.' · '.$user->username : 'Системд нэвтрэх шинэ хэрэглэгч бүртгэнэ.' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ $editing ? route('settings.users.update', $user) : route('settings.users.store') }}" class="form form-wide" autocomplete="off">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="form-section">
            <h3 class="form-section-title">Үндсэн мэдээлэл</h3>
            <div class="grid-2">
                <div class="field">
                    <label for="name">Нэр <span class="req">*</span></label>
                    <input id="name" name="name" type="text" class="input" value="{{ old('name', $user->name) }}" required>
                    @error('name') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="username">Нэвтрэх нэр <span class="req">*</span></label>
                    <input id="username" name="username" type="text" class="input" value="{{ old('username', $user->username) }}" required>
                    @error('username') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="field">
                <label for="email">И-мэйл</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email', $user->email) }}" placeholder="name@example.mn">
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>
        </section>

        <section class="form-section">
            <h3 class="form-section-title">Ажлын байр</h3>
            <div class="grid-2">
                <div class="field">
                    <label for="department_id">Алба, хэлтэс</label>
                    <select id="department_id" name="department_id" class="input">
                        <option value="">— Сонгоогүй —</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $user->department_id) == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="position_id">Албан тушаал</label>
                    <select id="position_id" name="position_id" class="input" data-selected="{{ old('position_id', $user->position_id) }}">
                        <option value="">— Эхлээд алба, хэлтэс сонгоно уу —</option>
                    </select>
                    <div class="field-hint" id="positionHint" hidden>Энэ алба, хэлтэст албан тушаал бүртгэгдээгүй байна.</div>
                    @error('position_id') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <h3 class="form-section-title">Эрх, нууц үг</h3>
            <div class="grid-2">
                <div class="field">
                    <label for="role_id">Эрхийн түвшин <span class="req">*</span></label>
                    <select id="role_id" name="role_id" class="input" @disabled($isSelf) required>
                        <option value="">— Сонгох —</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @if ($isSelf)
                        <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                        <div class="field-hint">Өөрийн эрхийг өөрчлөх боломжгүй.</div>
                    @endif
                    @error('role_id') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Төлөв</label>
                    <label class="switch">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editing ? $user->is_active : true)) @disabled($isSelf)>
                        <span class="switch-track"></span>
                        <span>Идэвхтэй</span>
                    </label>
                    @if ($isSelf)
                        <input type="hidden" name="is_active" value="1">
                    @endif
                </div>
            </div>
            <div class="grid-2">
                <div class="field">
                    <label for="password">Нууц үг @unless ($editing)<span class="req">*</span>@endunless</label>
                    <input id="password" name="password" type="password" class="input" autocomplete="new-password" @required(! $editing)>
                    <div class="field-hint">{{ $editing ? 'Солихгүй бол хоосон үлдээнэ үү.' : 'Хамгийн багадаа 8 тэмдэгт.' }}</div>
                    @error('password') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Нууц үг давтах</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password">
                </div>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn">Хадгалах</button>
            <a href="{{ route('settings.users.index') }}" class="btn btn-ghost">Буцах</a>
        </div>
    </form>
</div>

<script>
    (function () {
        const positions = @json($positions);
        const department = document.getElementById('department_id');
        const position = document.getElementById('position_id');
        const hint = document.getElementById('positionHint');
        let selected = position.dataset.selected;

        function fill() {
            const departmentId = Number(department.value);
            const items = departmentId ? positions.filter((p) => p.department_id === departmentId) : [];
            // Хэрэглэгч сонголтоо өөрчилсөн бол өмнөх албан тушаал таарахгүй тул цэвэрлэнэ
            const keep = items.some((p) => String(p.id) === selected) ? selected : '';

            position.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = !departmentId ? '— Эхлээд алба, хэлтэс сонгоно уу —'
                : items.length ? '— Сонгоогүй —' : '— Бүртгэлгүй —';
            position.appendChild(placeholder);

            items.forEach((p) => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name;
                opt.selected = String(p.id) === keep;
                position.appendChild(opt);
            });

            position.disabled = items.length === 0;
            hint.hidden = !(departmentId && items.length === 0);
            selected = keep;
        }

        department.addEventListener('change', () => { selected = ''; fill(); });
        fill();
    })();
</script>
@endsection
