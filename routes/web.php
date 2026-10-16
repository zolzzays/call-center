<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\PositionController;
use App\Http\Controllers\Settings\RepairCategoryController;
use App\Http\Controllers\Settings\RepairTypeController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Хаягийг шууд нээх, хуудсыг дахин ачаалах үед GET хүсэлт ирдэг тул алдаа өгөхгүй дахин чиглүүлнэ.
// Гаргах үйлдлийг зөвхөн POST-оор хийнэ.
Route::get('/logout', fn () => auth()->check() ? redirect('/') : redirect()->route('login'));

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('dashboard'));

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'attachment'])
        ->name('tickets.attachments.show');
    Route::put('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::put('/tickets/{ticket}/postpone', [TicketController::class, 'postpone'])->name('tickets.postpone');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', DashboardController::class)->name('admin.dashboard');

        Route::put('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::redirect('/', '/settings/users');
            Route::resource('users', UserController::class)->except('show');
            Route::resource('roles', RoleController::class)->except('show');
            Route::resource('positions', PositionController::class)->except('show');
            Route::resource('departments', DepartmentController::class)->except('show');
            Route::resource('repair-types', RepairTypeController::class)->except('show');
            Route::resource('repair-categories', RepairCategoryController::class)->except('show');
        });
    });
});
