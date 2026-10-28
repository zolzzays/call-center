<?php

namespace App\Http\Controllers\Settings;

use App\Models\AccessRequestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** МАБ-ын хандах эрхийн хүсэлтэд сонгох хүсэлтийн төрөл */
class AccessRequestTypeController extends LookupController
{
    protected function model(): string
    {
        return AccessRequestType::class;
    }

    protected function routePrefix(): string
    {
        return 'settings.access-request-types';
    }

    protected function labels(): array
    {
        return ['title' => 'Хүсэлтийн төрөл', 'singular' => 'Хүсэлтийн төрөл'];
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
            'name' => ['required', 'string', 'max:255', Rule::unique('access_request_types', 'name')->ignore($item)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function query(): Builder
    {
        return AccessRequestType::withCount('accessRequests');
    }

    protected function extraColumns(): array
    {
        return ['access_requests_count' => 'Хүсэлт'];
    }

    protected function deleteBlockedReason(Model $item): ?string
    {
        return $item->accessRequests()->exists()
            ? 'Энэ төрлөөр хүсэлт бүртгэлтэй тул устгах боломжгүй. Идэвхгүй болгож болно.'
            : null;
    }
}
