<?php

namespace App\Http\Controllers\Settings;

use App\Models\RepairCategory;
use App\Models\RepairType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class RepairCategoryController extends LookupController
{
    protected function model(): string
    {
        return RepairCategory::class;
    }

    protected function routePrefix(): string
    {
        return 'settings.repair-categories';
    }

    protected function labels(): array
    {
        return ['title' => 'Засвар ангилал', 'singular' => 'Засварын ангилал'];
    }

    protected function fields(): array
    {
        return [
            'repair_type_id' => [
                'label' => 'Засварын төрөл',
                'type' => 'select',
                'list' => true,
                'display' => 'repairType.name',
                'options' => fn (?Model $item) => RepairType::query()
                    ->where(fn (Builder $q) => $q->where('is_active', true)->orWhere('id', $item?->repair_type_id))
                    ->orderBy('name')
                    ->pluck('name', 'id'),
            ],
            'name' => ['label' => 'Нэр', 'type' => 'text', 'list' => true],
            'assignee_id' => [
                'label' => 'Гүйцэтгэгч',
                'type' => 'select',
                'list' => true,
                'required' => false,
                'placeholder' => '— Оноогоогүй —',
                'display' => 'assignee.name',
                'hint' => 'Энэ систем, төхөөрөмжийн дуудлага сонгосон гүйцэтгэгчид автоматаар хуваарилагдаж, мэйлээр мэдэгдэнэ.',
                'options' => fn (?Model $item) => $this->assigneeOptions($item?->assignee_id),
            ],
            'co_assignee_id' => [
                'label' => 'Хамтран гүйцэтгэгч',
                'type' => 'select',
                'list' => true,
                'required' => false,
                'placeholder' => '— Оноогоогүй —',
                'display' => 'coAssignee.name',
                'hint' => 'Сонговол дуудлага хоёр гүйцэтгэгчид хамт хуваарилагдаж, хоёуланд нь мэйл очно. Аль нь ч хааж, хойшлуулж болно.',
                'options' => fn (?Model $item) => $this->assigneeOptions($item?->co_assignee_id),
            ],
            'description' => ['label' => 'Тайлбар', 'type' => 'textarea', 'list' => true],
            'is_active' => ['label' => 'Идэвхтэй', 'type' => 'checkbox', 'list' => true],
        ];
    }

    protected function rules(?Model $item): array
    {
        return [
            'repair_type_id' => ['required', 'integer', Rule::exists('repair_types', 'id')],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('repair_categories', 'name')
                    ->where('repair_type_id', request()->integer('repair_type_id'))
                    ->ignore($item),
            ],
            'assignee_id' => [
                'nullable', 'integer',
                Rule::in(User::assignees($item?->assignee_id)->pluck('id')->all()),
            ],
            'co_assignee_id' => [
                'nullable', 'integer', 'different:assignee_id',
                Rule::in(User::assignees($item?->co_assignee_id)->pluck('id')->all()),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function query(): Builder
    {
        return RepairCategory::with(['repairType', 'assignee', 'coAssignee']);
    }

    protected function deleteBlockedReason(Model $item): ?string
    {
        return $item->modules()->exists()
            ? 'Энэ системд модуль бүртгэлтэй тул устгах боломжгүй. Идэвхгүй болгож болно.'
            : null;
    }

    protected function messages(): array
    {
        return parent::messages() + [
            'co_assignee_id.different' => 'Хамтран гүйцэтгэгч үндсэн гүйцэтгэгчээс өөр байна.',
        ];
    }

    private function assigneeOptions(?int $currentId): Collection
    {
        return User::assignees($currentId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active'])
            ->mapWithKeys(fn (User $u) => [$u->id => $u->name
                .($u->email ? '' : ' (и-мэйлгүй)')
                .($u->is_active ? '' : ' (идэвхгүй)')]);
    }
}
