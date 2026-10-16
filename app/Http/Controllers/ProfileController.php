<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Нэрээ оруулна уу.',
        ]);

        $request->user()->update($data);

        return back()->with('status', 'Хувийн мэдээлэл хадгалагдлаа.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Одоогийн нууц үгээ оруулна уу.',
            'current_password.current_password' => 'Одоогийн нууц үг буруу байна.',
            'password.required' => 'Шинэ нууц үгээ оруулна уу.',
            'password.confirmed' => 'Шинэ нууц үг таарахгүй байна.',
            'password.min' => 'Шинэ нууц үг хамгийн багадаа 8 тэмдэгт байна.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Нууц үг амжилттай солигдлоо.');
    }
}
