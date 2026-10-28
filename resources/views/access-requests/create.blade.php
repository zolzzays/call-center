@extends('layouts.app')

@section('title', 'Хандах эрх хүсэх')

@section('page-styles')
    .form-card { max-width: 760px; }
    .req { color: var(--danger); font-weight: 700; }
    .flow { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: 4px 0 20px; font-size: 13px; color: var(--muted); }
    .flow span { background: #eef2f7; color: var(--navy); padding: 4px 10px; border-radius: 999px; font-weight: 600; }
    .modules { display: grid; gap: 12px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; background: #fbfcfe; max-height: 420px; overflow-y: auto; }
    .mod-group legend, .mod-group > label.group { display: flex; align-items: center; gap: 8px; font-weight: 700; color: var(--navy); font-size: 14px; cursor: pointer; }
    .mod-group { border: 0; margin: 0; padding: 0; }
    .mod-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 4px 16px; margin: 6px 0 0 24px; }
    .mod-list label { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; padding: 2px 0; }
    .modules input[type=checkbox] { width: 15px; height: 15px; margin: 0; accent-color: var(--primary); }
@endsection

@section('page')
<div class="panel form-card">
    <h1>Хандах эрх хүсэх</h1>
    <p>Мэдээллийн аюулгүй байдлын журмын дагуу систем, нөөцөд хандах эрх хүснэ.</p>
    <div class="flow" aria-label="Батлах дараалал">
        <span>1. Хүсэлт гаргах</span> → <span>2. Нэгжийн удирдлага</span> → <span>3. МАБ инженер</span> → <span>4. Ерөнхий диспетчер</span> → <span>5. Системийн инженер</span> → <span>6. Танд олгоно</span>
    </div>

    <form method="POST" action="{{ route('access-requests.store') }}" novalidate>
        @csrf

        <div class="field">
            <label for="request_type_id">Хүсэлтийн төрөл <span class="req">*</span></label>
            <select id="request_type_id" name="request_type_id" class="input" required>
                <option value="">— Сонгох —</option>
                @foreach ($requestTypes as $id => $name)
                    <option value="{{ $id }}" @selected((int) old('request_type_id') === $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('request_type_id') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="repair_category_id">Системийн нэр <span class="req">*</span></label>
            <select id="repair_category_id" name="repair_category_id" class="input" required>
                <option value="">— Систем сонгох —</option>
                @foreach ($systems as $type => $items)
                    <optgroup label="{{ $type }}">
                        @foreach ($items as $s)
                            <option value="{{ $s->id }}" @selected((int) old('repair_category_id') === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('repair_category_id') <div class="error">{{ $message }}</div> @enderror
        </div>

        {{-- Сонгосон системийн модулиуд (JS-ээр бөглөнө) --}}
        <div class="field" id="modulesField" hidden>
            <label>Модулиуд <span class="req">*</span></label>
            <div class="field-hint" style="margin: 0 0 8px">Хандах эрх хүсэж буй модулиудаа сонгоно уу. Бүлгийн нэр дээр дарж бүгдийг сонгоно.</div>
            <div class="modules" id="modulesBox"></div>
            @error('modules') <div class="error">{{ $message }}</div> @enderror
            @error('modules.*') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="access_type_id">Эрхийн төрөл <span class="req">*</span></label>
            <select id="access_type_id" name="access_type_id" class="input" required>
                <option value="">— Сонгох —</option>
                @foreach ($accessTypes as $id => $name)
                    <option value="{{ $id }}" @selected((int) old('access_type_id') === $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('access_type_id') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="starts_on">Эхлэх огноо <span class="req">*</span></label>
                <input id="starts_on" name="starts_on" type="date" class="input" value="{{ old('starts_on', now()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" required>
                @error('starts_on') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="ends_on">Дуусах огноо</label>
                <input id="ends_on" name="ends_on" type="date" class="input" value="{{ old('ends_on') }}" min="{{ now()->format('Y-m-d') }}">
                <div class="field-hint">Хоосон бол байнгын эрх.</div>
                @error('ends_on') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field">
            <label for="reason">Шалтгаан <span class="req">*</span></label>
            <textarea id="reason" name="reason" class="input" rows="4" maxlength="5000" required
                      placeholder="Ямар ажлын хүрээнд, яагаад энэ эрх хэрэгтэй байгааг бичнэ үү.">{{ old('reason') }}</textarea>
            @error('reason') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Илгээх</button>
            <a href="{{ route('access-requests.index') }}" class="btn btn-ghost">Болих</a>
        </div>
    </form>
</div>

<script>
    (function () {
        // Систем бүрийн модулиуд: [{id, name, children: [{id, name}]}]
        const modules = @json($modules, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        const oldSelected = new Set(@json(array_map('intval', (array) old('modules', []))));
        const select = document.getElementById('repair_category_id');
        const field = document.getElementById('modulesField');
        const box = document.getElementById('modulesBox');

        const checkbox = (id, name) => {
            const label = document.createElement('label');
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'modules[]';
            input.value = id;
            input.checked = oldSelected.has(id);
            label.append(input, document.createTextNode(name));
            return label;
        };

        function render() {
            const groups = modules[select.value] || [];
            box.replaceChildren();
            field.hidden = groups.length === 0;

            groups.forEach((group) => {
                const wrap = document.createElement('fieldset');
                wrap.className = 'mod-group';

                if (group.children.length === 0) {
                    // Дэд модульгүй бүлэг өөрөө сонгогдоно
                    wrap.append(checkbox(group.id, group.name));
                    wrap.firstChild.classList.add('group');
                    box.append(wrap);
                    return;
                }

                // Бүлгийн сонголт: дэд модулиудыг бүгдийг сонгох / болиулах
                const legend = document.createElement('legend');
                const all = document.createElement('input');
                all.type = 'checkbox';
                legend.append(all, document.createTextNode(group.name));

                const list = document.createElement('div');
                list.className = 'mod-list';
                group.children.forEach((m) => list.append(checkbox(m.id, m.name)));

                const items = () => [...list.querySelectorAll('input')];
                const sync = () => {
                    const checked = items().filter((i) => i.checked).length;
                    all.checked = checked === items().length;
                    all.indeterminate = checked > 0 && checked < items().length;
                };
                all.addEventListener('change', () => { items().forEach((i) => { i.checked = all.checked; }); sync(); });
                list.addEventListener('change', sync);
                legend.addEventListener('click', (e) => { if (e.target !== all) { all.click(); } });

                wrap.append(legend, list);
                box.append(wrap);
                sync();
            });
        }

        select.addEventListener('change', () => { oldSelected.clear(); render(); });
        render();
    })();
</script>
@endsection
