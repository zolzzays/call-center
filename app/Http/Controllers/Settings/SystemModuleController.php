<?php

namespace App\Http\Controllers\Settings;

use App\Models\RepairCategory;
use App\Models\SystemModule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Програм хангамжийн системийн модулиуд (бүлэг → модуль) */
class SystemModuleController extends LookupController
{
    /** Модуль бүртгэх системүүд нь энэ засварын төрөлд хамаарна */
    private const SOFTWARE_TYPE = 'Програм хангамж';

    protected function model(): string
    {
        return SystemModule::class;
    }

    /** Системээ сонгоход зөвхөн тухайн системийн бүлэг, модулиуд харагдана */
    public function index(Request $request): View
    {
        $systems = $this->softwareSystems()->withCount('modules')->orderBy('name')->get();
        $system = $systems->firstWhere('id', $request->integer('system')) ?? $systems->first();

        $groups = $system
            ? SystemModule::where('repair_category_id', $system->id)
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->orderBy('id')])
                ->orderBy('id')
                ->get()
            : collect();

        return view('settings.system-modules.index', $this->viewData() + compact('systems', 'system', 'groups'));
    }

    protected function routePrefix(): string
    {
        return 'settings.system-modules';
    }

    protected function labels(): array
    {
        return ['title' => 'Системийн модуль', 'singular' => 'Модуль'];
    }

    protected function fields(): array
    {
        return [
            'repair_category_id' => [
                'label' => 'Систем',
                'type' => 'select',
                'list' => true,
                'display' => 'system.name',
                // Сонгосон системд л бүртгэнэ (жагсаалтаас ирсэн, эсвэл засаж буй модулийн систем)
                'options' => fn (?Model $item) => ($systemId = $this->selectedSystemId($item))
                    ? RepairCategory::whereKey($systemId)->pluck('name', 'id')
                    : $this->softwareSystems()->orderBy('name')->pluck('name', 'id'),
            ],
            'parent_id' => [
                'label' => 'Бүлэг',
                'type' => 'select',
                'list' => true,
                'required' => false,
                'placeholder' => '— Бүлэг биш (үндсэн модуль) —',
                'display' => 'parent.name',
                'hint' => 'Хоосон орхивол энэ нь бүлэг (жишээ нь «Шуурхай ажиллагаа») болно. Бүлэг нь сонгосон системийнх байна.',
                'options' => fn (?Model $item) => SystemModule::with('system')
                    ->whereNull('parent_id')
                    ->when($this->selectedSystemId($item), fn (Builder $q, $id) => $q->where('repair_category_id', $id))
                    ->when($item?->exists, fn (Builder $q) => $q->where('id', '!=', $item->id))
                    ->orderBy('repair_category_id')->orderBy('id')
                    ->get()
                    ->mapWithKeys(fn (SystemModule $m) => [$m->id => $this->selectedSystemId($item) ? $m->name : $m->system?->name.' › '.$m->name]),
            ],
            'name' => ['label' => 'Модулийн нэр', 'type' => 'text', 'list' => true],
            'description' => ['label' => 'Тайлбар', 'type' => 'textarea', 'list' => false],
            'is_active' => ['label' => 'Идэвхтэй', 'type' => 'checkbox', 'list' => true],
        ];
    }

    protected function rules(?Model $item): array
    {
        $systemId = request()->integer('repair_category_id');
        $parentId = request()->filled('parent_id') ? request()->integer('parent_id') : null;

        return [
            'repair_category_id' => ['required', 'integer', Rule::exists('repair_categories', 'id')],
            // Бүлэг нь тухайн системийн үндсэн модуль байна; бүлэгтэй модульд дэд модуль нэмэхгүй
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('system_modules', 'id')->where('repair_category_id', $systemId)->whereNull('parent_id'),
                Rule::notIn(array_filter([$item?->id])),
                function ($attribute, $value, $fail) use ($item) {
                    if ($value && $item?->children()->exists()) {
                        $fail('Энэ бүлэгт модуль бүртгэлтэй тул өөр бүлэгт оруулах боломжгүй.');
                    }
                },
            ],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('system_modules', 'name')
                    ->where('repair_category_id', $systemId)
                    ->where(fn ($q) => $parentId ? $q->where('parent_id', $parentId) : $q->whereNull('parent_id'))
                    ->ignore($item),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return parent::messages() + [
            'parent_id.exists' => 'Бүлэг нь сонгосон системийн бүлэг байх ёстой.',
            'parent_id.not_in' => 'Модуль өөрөө өөрийнхөө бүлэг болохгүй.',
            'name.unique' => 'Энэ систем, бүлэгт ийм нэртэй модуль бүртгэлтэй байна.',
        ];
    }

    /** Систем → бүлэг → түүний модулиуд дарааллаар */
    protected function query(): Builder
    {
        return SystemModule::with(['system', 'parent'])
            ->withCount('children')
            ->orderBy('repair_category_id')
            ->orderByRaw('COALESCE(parent_id, id)')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END');
    }

    protected function extraColumns(): array
    {
        return ['children_count' => 'Дэд модуль'];
    }

    /** Жагсаалтаас «Нэмэх» дарахад сонгосон систем, бүлэг урьдчилан бөглөгдөнө */
    protected function newItem(): Model
    {
        return new SystemModule([
            'repair_category_id' => request()->integer('system') ?: null,
            'parent_id' => request()->integer('parent') ?: null,
        ]);
    }

    /** Хадгалсан, устгасны дараа тухайн системийн жагсаалт руу буцна */
    protected function indexParams(?Model $item): array
    {
        return array_filter(['system' => $item?->repair_category_id ?? (request()->integer('system') ?: null)]);
    }

    private function selectedSystemId(?Model $item): ?int
    {
        return $item?->repair_category_id ?? (request()->integer('system') ?: null);
    }

    private function softwareSystems(): Builder
    {
        return RepairCategory::whereHas('repairType', fn (Builder $t) => $t->where('name', self::SOFTWARE_TYPE));
    }

    protected function deleteBlockedReason(Model $item): ?string
    {
        return $item->children()->exists()
            ? 'Энэ бүлэгт модуль бүртгэлтэй тул устгах боломжгүй. Эхлээд модулиудыг устгана уу.'
            : null;
    }
}
