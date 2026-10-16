<?php

namespace App\Http\Controllers\Settings;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class DepartmentController extends LookupController
{
    protected function model(): string
    {
        return Department::class;
    }

    protected function routePrefix(): string
    {
        return 'settings.departments';
    }

    protected function labels(): array
    {
        return ['title' => 'Алба, хэлтэс', 'singular' => 'Алба, хэлтэс'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Нэр', 'type' => 'text', 'list' => true],
            'description' => ['label' => 'Тайлбар', 'type' => 'textarea', 'list' => true],
            'is_active' => ['label' => 'Идэвхтэй', 'type' => 'checkbox', 'list' => true],
        ];
    }

    protected function rules(?Model $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($item)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function query(): Builder
    {
        return Department::withCount(['users', 'positions']);
    }

    protected function extraColumns(): array
    {
        return ['positions_count' => 'Албан тушаал', 'users_count' => 'Хэрэглэгч'];
    }

    protected function deleteBlockedReason(Model $item): ?string
    {
        return $item->positions()->exists()
            ? 'Энэ алба, хэлтэст албан тушаал бүртгэлтэй тул устгах боломжгүй.'
            : null;
    }
}
