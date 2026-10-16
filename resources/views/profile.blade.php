@extends('layouts.app')

@section('title', 'Миний профайл')

@section('page')
<div class="panel">
    <h3>Хувийн мэдээлэл</h3>
    <p>Таны нэр системд харагдана.</p>

    <form method="POST" action="{{ route('profile.update') }}" class="form">
        @csrf
        @method('PUT')
        <div class="field">
            <label>Нэвтрэх нэр</label>
            <input type="text" class="input" value="{{ auth()->user()->username }}" readonly>
        </div>
        <div class="field">
            <label for="name">Нэр</label>
            <input id="name" name="name" type="text" class="input" value="{{ old('name', auth()->user()->name) }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn">Хадгалах</button>
    </form>
</div>

<div class="panel">
    <h3>Нууц үг солих</h3>
    <p>Шинэ нууц үг хамгийн багадаа 8 тэмдэгт байна.</p>

    <form method="POST" action="{{ route('profile.password') }}" class="form">
        @csrf
        @method('PUT')
        <div class="field">
            <label for="current_password">Одоогийн нууц үг</label>
            <input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password" required>
            @error('current_password', 'password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="password">Шинэ нууц үг</label>
            <input id="password" name="password" type="password" class="input" autocomplete="new-password" required>
            @error('password', 'password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Шинэ нууц үг давтах</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn">Нууц үг солих</button>
    </form>
</div>
@endsection
