<?php

namespace App\Http\Controllers\Settings;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class RoleController extends LookupController
{
    protected function model(): string
    {
        return Role::class;
    }

    protected function routePrefix(): string
    {
        return 'settings.roles';
    }

    protected function labels(): array
    {
        return ['title' => 'Эрхийн түвшин', 'singular' => 'Эрхийн түвшин'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Нэр', 'type' => 'text', 'list' => true],
            'slug' => ['label' => 'Код', 'type' => 'text', 'list' => true, 'hint' => 'Латин жижиг үсэг, тоо, доогуур зураас. Жишээ: dispatcher'],
            'description' => ['label' => 'Тайлбар', 'type' => 'textarea', 'list' => true],
        ];
    }

    protected function rules(?Model $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                $item?->is_system ? 'nullable' : 'required',
                'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'slug')->ignore($item),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function query(): Builder
    {
        return Role::withCount('users');
    }

    protected function extraColumns(): array
    {
        return ['users_count' => 'Хэрэглэгч'];
    }

    /** Системийн (admin, user) эрхийн кодыг өөрчлөхгүй */
    protected function lockedFields(?Model $item): array
    {
        return $item?->is_system ? ['slug'] : [];
    }

    protected function deleteBlockedReason(Model $item): ?string
    {
        if ($item->is_system) {
            return 'Системийн эрхийн түвшинг устгах боломжгүй.';
        }

        if ($item->users()->exists()) {
            return 'Энэ эрхийн түвшинд хэрэглэгч бүртгэлтэй тул устгах боломжгүй.';
        }

        return null;
    }
}
