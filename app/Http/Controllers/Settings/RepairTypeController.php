<?php

namespace App\Http\Controllers\Settings;

use App\Models\RepairType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class RepairTypeController extends LookupController
{
    protected function model(): string
    {
        return RepairType::class;
    }

    protected function routePrefix(): string
    {
        return 'settings.repair-types';
    }

    protected function labels(): array
    {
        return ['title' => 'Засвар төрөл', 'singular' => 'Засварын төрөл'];
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
            'name' => ['required', 'string', 'max:255', Rule::unique('repair_types', 'name')->ignore($item)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function query(): Builder
    {
        return RepairType::withCount('categories');
    }

    protected function extraColumns(): array
    {
        return ['categories_count' => 'Ангилал'];
    }

    protected function deleteBlockedReason(Model $item): ?string
    {
        return $item->categories()->exists()
            ? 'Энэ засварын төрөлд ангилал бүртгэлтэй тул устгах боломжгүй.'
            : null;
    }
}
