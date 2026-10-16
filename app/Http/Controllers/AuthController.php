<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 15 * 60;

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Нэвтрэх нэрээ оруулна уу.',
            'password.required' => 'Нууц үгээ оруулна уу.',
        ]);

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            throw ValidationException::withMessages([
                'username' => "Олон удаа буруу оруулсан тул түгжигдсэн. {$minutes} минутын дараа дахин оролдоно уу.",
            ]);
        }

        $attempt = $credentials + ['is_active' => true];

        if (! Auth::attempt($attempt, $request->boolean('remember'))) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);
            $left = RateLimiter::remaining($key, self::MAX_ATTEMPTS);

            throw ValidationException::withMessages([
                'username' => $left > 0
                    ? "Нэвтрэх нэр эсвэл нууц үг буруу байна. Үлдсэн оролдлого: {$left}."
                    : 'Олон удаа буруу оруулсан тул 15 минут түгжигдлээ.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(
            $user->isAdmin() ? route('admin.dashboard') : route('dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function throttleKey(Request $request): string
    {
        return 'login|'.Str::lower((string) $request->input('username')).'|'.$request->ip();
    }
}
