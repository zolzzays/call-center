<?php

namespace App\Http\Controllers\Settings;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PositionController extends LookupController
{
    protected function model(): string
    {
        return Position::class;
    }

    protected function routePrefix(): string
    {
        return 'settings.positions';
    }

    protected function labels(): array
    {
        return ['title' => 'Албан тушаал', 'singular' => 'Албан тушаал'];
    }

    protected function fields(): array
    {
        return [
            'department_id' => [
                'label' => 'Алба, хэлтэс',
                'type' => 'select',
                'list' => true,
                'display' => 'department.name',
                'options' => fn (?Model $item) => Department::query()
                    ->where(fn (Builder $q) => $q->where('is_active', true)->orWhere('id', $item?->department_id))
                    ->orderBy('name')
                    ->pluck('name', 'id'),
            ],
            'name' => ['label' => 'Нэр', 'type' => 'text', 'list' => true],
            'access_role' => [
                'label' => 'МАБ батлах үүрэг',
                'type' => 'select',
                'list' => true,
                'required' => false,
                'placeholder' => '— Үүрэггүй —',
                'display' => 'access_role_label',
                'hint' => 'Энэ албан тушаалтай хүн МАБ-ын хандах эрхийн хүсэлтийг тухайн шатанд автоматаар хүлээн авч батална.',
                'options' => fn (?Model $item) => collect(Position::ACCESS_ROLES),
            ],
            'description' => ['label' => 'Тайлбар', 'type' => 'textarea', 'list' => true],
            'is_active' => ['label' => 'Идэвхтэй', 'type' => 'checkbox', 'list' => true],
        ];
    }

    protected function rules(?Model $item): array
    {
        return [
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('positions', 'name')
                    ->where('department_id', request()->integer('department_id'))
                    ->ignore($item),
            ],
            'access_role' => ['nullable', Rule::in(array_keys(Position::ACCESS_ROLES))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function query(): Builder
    {
        return Position::with('department')->withCount('users');
    }

    protected function extraColumns(): array
    {
        return ['users_count' => 'Хэрэглэгч'];
    }
}
