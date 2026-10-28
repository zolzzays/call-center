<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Энгийн лавлах хүснэгтүүдийн (эрхийн түвшин, албан тушаал, засвар төрөл)
 * нийтлэг CRUD. Дэд класс нь model, гарчиг, талбар, шалгуурыг тодорхойлно.
 */
abstract class LookupController extends Controller
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** Route нэрийн угтвар, жишээ нь "settings.positions" */
    abstract protected function routePrefix(): string;

    /** @return array{title: string, singular: string} */
    abstract protected function labels(): array;

    /**
     * Маягтын талбарууд: name => ['label' => ..., 'type' => text|textarea|checkbox|select, 'list' => bool]
     * select төрөлд 'options' => fn (?Model $item) => [id => нэр], жагсаалтад харуулах
     * утгыг 'display' => 'relation.column' гэж зааж өгнө.
     *
     * @return array<string, array{label: string, type: string, list?: bool, options?: \Closure, display?: string}>
     */
    abstract protected function fields(): array;

    abstract protected function rules(?Model $item): array;

    /** Жагсаалтын query-г өргөтгөх (withCount гэх мэт) */
    protected function query(): Builder
    {
        return $this->model()::query();
    }

    /** Устгах боломжгүй бол шалтгааныг буцаана */
    protected function deleteBlockedReason(Model $item): ?string
    {
        return null;
    }

    /** Засах үед түгжигдсэн талбарууд */
    protected function lockedFields(?Model $item): array
    {
        return [];
    }

    protected function extraColumns(): array
    {
        return [];
    }

    /** Шинэ бичлэгийн анхдагч утга (жишээ нь сонгосон шүүлтээс) */
    protected function newItem(): Model
    {
        return new ($this->model());
    }

    /** Хадгалсан, устгасны дараа буцах жагсаалтын параметр (жишээ нь сонгосон шүүлт) */
    protected function indexParams(?Model $item): array
    {
        return [];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $items = $this->query()
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('settings.lookup.index', $this->viewData() + [
            'items' => $items,
            'search' => $search,
            'extraColumns' => $this->extraColumns(),
        ]);
    }

    public function create(): View
    {
        $item = $this->newItem();

        return view('settings.lookup.form', $this->viewData() + [
            'indexParams' => $this->indexParams($item),
            'item' => $item,
            'locked' => [],
            'options' => $this->selectOptions($item),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $item = $this->model()::create($data);

        return redirect()->route($this->routePrefix().'.index', $this->indexParams($item))
            ->with('status', $this->labels()['singular'].' нэмэгдлээ.');
    }

    public function edit(string $id): View
    {
        $item = $this->model()::findOrFail($id);

        return view('settings.lookup.form', $this->viewData() + [
            'indexParams' => $this->indexParams($item),
            'item' => $item,
            'locked' => $this->lockedFields($item),
            'options' => $this->selectOptions($item),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = $this->model()::findOrFail($id);
        $data = collect($this->validated($request, $item))
            ->except($this->lockedFields($item))
            ->all();

        $item->update($data);

        return redirect()->route($this->routePrefix().'.index', $this->indexParams($item))
            ->with('status', $this->labels()['singular'].' шинэчлэгдлээ.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = $this->model()::findOrFail($id);

        if ($reason = $this->deleteBlockedReason($item)) {
            return back()->with('error', $reason);
        }

        $item->delete();

        return redirect()->route($this->routePrefix().'.index', $this->indexParams($item))
            ->with('status', $this->labels()['singular'].' устгагдлаа.');
    }

    private function validated(Request $request, ?Model $item): array
    {
        $data = $request->validate(
            $this->rules($item),
            $this->messages(),
            array_map(fn (array $field) => $field['label'], $this->fields()),
        );

        foreach ($this->fields() as $name => $field) {
            if ($field['type'] === 'checkbox') {
                $data[$name] = $request->boolean($name);
            }
        }

        return $data;
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute талбарыг бөглөнө үү.',
            'unique' => 'Ийм :attribute бүртгэлтэй байна.',
            'max' => ':attribute хэт урт байна.',
            'regex' => ':attribute буруу форматтай байна.',
            'exists' => 'Сонгосон :attribute олдсонгүй.',
            'in' => 'Сонгосон :attribute буруу байна.',
        ];
    }

    protected function selectOptions(Model $item): array
    {
        $options = [];

        foreach ($this->fields() as $name => $field) {
            if ($field['type'] === 'select') {
                $options[$name] = $field['options']($item->exists ? $item : null);
            }
        }

        return $options;
    }

    protected function viewData(): array
    {
        return [
            'labels' => $this->labels(),
            'fields' => $this->fields(),
            'routePrefix' => $this->routePrefix(),
        ];
    }
}
