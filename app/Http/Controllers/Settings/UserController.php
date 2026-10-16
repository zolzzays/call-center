<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $users = User::with(['role', 'position', 'department'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
        ];

        return view('settings.users.index', compact('users', 'search', 'stats'));
    }

    public function create(): View
    {
        return view('settings.users.form', $this->formData(new User(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        User::create($data);

        return redirect()->route('settings.users.index')->with('status', 'Хэрэглэгч нэмэгдлээ.');
    }

    public function edit(User $user): View
    {
        return view('settings.users.form', $this->formData($user));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Өөрийгөө идэвхгүй болгох, админ эрхээ хасахаас сэргийлнэ
        if ($user->is($request->user())) {
            unset($data['is_active'], $data['role_id']);
        }

        $user->update($data);

        return redirect()->route('settings.users.index')->with('status', 'Хэрэглэгчийн мэдээлэл шинэчлэгдлээ.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Өөрийн бүртгэлийг устгах боломжгүй.');
        }

        $user->delete();

        return redirect()->route('settings.users.index')->with('status', 'Хэрэглэгч устгагдлаа.');
    }

    private function validated(Request $request, ?User $user): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role_id' => ['required', 'exists:roles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            // Албан тушаал нь сонгосон алба, хэлтэст харьяалагдах ёстой
            'position_id' => [
                'nullable',
                Rule::exists('positions', 'id')->where('department_id', $request->integer('department_id')),
            ],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'Нэрийг оруулна уу.',
            'username.required' => 'Нэвтрэх нэрийг оруулна уу.',
            'username.unique' => 'Ийм нэвтрэх нэр бүртгэлтэй байна.',
            'username.regex' => 'Нэвтрэх нэр зөвхөн латин үсэг, тоо, _ . - агуулна.',
            'email.email' => 'И-мэйл хаяг буруу байна.',
            'email.unique' => 'Ийм и-мэйл бүртгэлтэй байна.',
            'role_id.required' => 'Эрхийн түвшинг сонгоно уу.',
            'position_id.exists' => 'Албан тушаал сонгосон алба, хэлтэст хамаарахгүй байна.',
            'password.required' => 'Нууц үгийг оруулна уу.',
            'password.confirmed' => 'Нууц үг таарахгүй байна.',
            'password.min' => 'Нууц үг хамгийн багадаа 8 тэмдэгт байна.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function formData(User $user): array
    {
        return [
            'user' => $user,
            'roles' => Role::orderBy('id')->get(),
            'positions' => Position::where('is_active', true)
                ->orWhere('id', $user->position_id)
                ->orderBy('name')
                ->get(['id', 'department_id', 'name']),
            'departments' => Department::where('is_active', true)
                ->orWhere('id', $user->department_id)
                ->orderBy('name')
                ->get(),
        ];
    }
}
