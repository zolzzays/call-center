@extends('layouts.app')

@section('container', 'wide')

@section('title', 'Хүсэлт илгээх')

@section('page-styles')
    .ticket-form { margin-top: 0; max-width: none; }

    .step { border: 1px solid var(--card-border); border-radius: var(--card-radius); padding: 16px 16px 2px; margin-bottom: 14px; background: var(--card-bg); box-shadow: var(--card-shadow); }
    .step-head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .step-num {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0; display: grid; place-items: center;
        background: var(--primary); color: #fff; font-size: 13px; font-weight: 700;
    }
    .panel .step-title { margin: 0; font-size: 13px; font-weight: 800; color: var(--card-title); text-transform: uppercase; letter-spacing: .02em; }
    .step-desc { font-size: 11px; font-weight: 600; color: var(--primary); margin-top: 3px; }

    .choice-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
    .choice {
        position: relative; display: flex; align-items: flex-start; gap: 12px; padding: 14px;
        border: 1.5px solid var(--border); border-radius: 10px; background: #fff; cursor: pointer;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .choice:hover { border-color: #a9bdd6; }
    .choice input { position: absolute; opacity: 0; pointer-events: none; }
    .choice-icon {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; display: grid; place-items: center;
        background: #eef4ff; color: var(--primary); transition: background .15s, color .15s;
    }
    .choice-body strong { display: block; font-size: 14px; color: var(--navy); }
    .choice-body small { display: block; font-size: 12px; color: var(--muted); margin-top: 3px; line-height: 1.4; }
    .choice-check {
        position: absolute; top: 10px; right: 10px; width: 18px; height: 18px; border-radius: 50%;
        border: 1.5px solid var(--border); background: #fff; transition: all .15s;
    }
    .choice:has(input:checked) { border-color: var(--primary); background: #f5f8ff; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
    .choice:has(input:checked) .choice-icon { background: var(--primary); color: #fff; }
    .choice:has(input:checked) .choice-check { border-color: var(--primary); background: var(--primary); box-shadow: inset 0 0 0 3px #fff; }
    .choice:has(input:focus-visible) { box-shadow: 0 0 0 3px rgba(37, 99, 235, .3); }

    .urgency-group { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .urgency .choice-icon { width: 12px; height: 12px; border-radius: 50%; margin-top: 4px; background: var(--u); }
    .urgency:has(input:checked) { border-color: var(--u); background: var(--u-bg); box-shadow: 0 0 0 3px var(--u-ring); }
    .urgency:has(input:checked) .choice-icon { background: var(--u); }
    .urgency:has(input:checked) .choice-check { border-color: var(--u); background: var(--u); }
    .u-normal { --u: #2563eb; --u-bg: #f3f7ff; --u-ring: rgba(37, 99, 235, .15); }
    .u-urgent { --u: #d97706; --u-bg: #fffaf0; --u-ring: rgba(217, 119, 6, .15); }
    .u-critical { --u: #c0392b; --u-bg: #fef6f5; --u-ring: rgba(192, 57, 43, .15); }

    .counter { font-size: 12px; color: var(--muted); text-align: right; margin-top: 6px; }

    .assignee-box {
        display: flex; align-items: center; gap: 12px; margin: -4px 0 18px; padding: 12px 14px;
        border: 1px solid #cfe0d6; border-radius: 10px; background: #f2faf5;
    }
    .assignee-box[hidden] { display: none; }
    .assignee-avatar {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; display: grid; place-items: center;
        background: var(--excel); color: #fff; font-size: 13px; font-weight: 700;
    }
    .assignee-label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
    .assignee-box strong { display: block; font-size: 14px; color: var(--navy); }
    .assignee-note { display: block; font-size: 12px; color: var(--muted); margin-top: 1px; }
    .assignee-box.none { border-color: #e3e9f1; background: #f7f9fc; }
    .assignee-box.none .assignee-avatar { background: #e6ebf1; color: #8a94a3; }
    .assignee-box.none strong { color: var(--muted); }
    .counter.warn { color: #b45309; }
    textarea.input { min-height: 140px; line-height: 1.55; }

    .dropzone {
        position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
        padding: 26px 16px; border: 2px dashed var(--border); border-radius: 12px; background: #fff; text-align: center;
        cursor: pointer; transition: border-color .15s, background .15s;
    }
    .dropzone:hover, .dropzone.drag { border-color: var(--primary); background: #f5f8ff; }
    .dropzone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .dropzone svg { color: var(--primary); }
    .dropzone strong { font-size: 14px; color: var(--navy); }
    .dropzone strong span { color: var(--primary); text-decoration: underline; }
    .dropzone small { font-size: 12px; color: var(--muted); }
    .files { list-style: none; margin: 10px 0 0; padding: 0; display: grid; gap: 8px; }
    .files li {
        display: flex; align-items: center; gap: 10px; padding: 8px 10px; border: 1px solid #e3e9f1;
        border-radius: 8px; background: #fff; font-size: 13px;
    }
    .files .thumb {
        width: 36px; height: 36px; border-radius: 6px; flex-shrink: 0; object-fit: cover; background: #eef4ff;
        display: grid; place-items: center; font-size: 10px; font-weight: 800; color: var(--primary); text-transform: uppercase;
    }
    .files .name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--navy); font-weight: 600; }
    .files .size { color: var(--muted); white-space: nowrap; }
    .files button {
        width: 28px; height: 28px; border: 0; border-radius: 6px; background: none; color: var(--muted);
        cursor: pointer; display: grid; place-items: center;
    }
    .files button:hover { background: var(--danger-bg); color: var(--danger); }

    .submit-bar {
        position: sticky; bottom: 0; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 12px;
        margin: 4px -20px -20px; padding: 14px 20px; background: rgba(255, 255, 255, .95); backdrop-filter: blur(6px);
        border-top: 1px solid #e3e9f1; border-radius: 0 0 12px 12px;
    }
    .submit-bar p { font-size: 13px; }
    .submit-bar .btn svg { margin-right: 8px; }


    @media (max-width: 900px) {
        .urgency-group { grid-template-columns: 1fr; }
        .step { padding: 16px 14px 2px; }
        .submit-bar { margin: 4px -16px -20px; padding: 14px 16px; flex-direction: column; align-items: stretch; }
        .submit-bar .btn { width: 100%; }
    }
@endsection

@section('page')
@php
    $typeMeta = [
        'repair' => ['Ажиллахгүй, гэмтсэн систем, төхөөрөмж', '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>'],
        'change' => ['Шинэ боломж, тохиргооны өөрчлөлт', '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>'],
    ];
    $urgencyMeta = [
        'normal' => 'Ажлын хэвийн горимд',
        'urgent' => 'Ажилд саад учруулж байна',
        'critical' => 'Ажил бүрэн зогссон',
    ];
@endphp

<div class="ticket-layout">
    <div class="panel">
        <div class="panel-head" style="margin-bottom:20px">
            <div>
                <h1>Хүсэлт илгээх</h1>
                <p>Мэдээллээ бөглөөд илгээхэд дугаар автоматаар олгогдоно.</p>
            </div>
        </div>

        @if ($repairTypes->isEmpty())
            <div class="flash flash-error" role="alert">Ангилал бүртгэгдээгүй байна. Админ «Тохиргоо → Засвар төрөл» хэсэгт ангилал нэмнэ үү.</div>
        @endif

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="form ticket-form" id="ticketForm" novalidate>
            @csrf

            <section class="step">
                <div class="step-head">
                    <span class="step-num">1</span>
                    <div>
                        <h3 class="step-title">Төрөл</h3>
                        <div class="step-desc">Засвар уу, эсвэл нэмэлт, өөрчлөлт үү?</div>
                    </div>
                </div>
                <div class="field">
                    <div class="choice-group" role="radiogroup" aria-label="Төрөл">
                        @foreach (\App\Models\Ticket::TYPES as $value => $label)
                            <label class="choice">
                                <input type="radio" name="type" value="{{ $value }}" @checked(old('type', 'repair') === $value) required>
                                <span class="choice-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $typeMeta[$value][1] !!}</svg>
                                </span>
                                <span class="choice-body">
                                    <strong>{{ $label }}</strong>
                                    <small>{{ $typeMeta[$value][0] }}</small>
                                </span>
                                <span class="choice-check" aria-hidden="true"></span>
                            </label>
                        @endforeach
                    </div>
                    @error('type') <div class="error">{{ $message }}</div> @enderror
                </div>
            </section>

            <section class="step">
                <div class="step-head">
                    <span class="step-num">2</span>
                    <div>
                        <h3 class="step-title">Ангилал</h3>
                        <div class="step-desc">Аль систем, төхөөрөмжтэй холбоотой вэ?</div>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label for="repair_type_id">Ангилал <span class="req">*</span></label>
                        <select id="repair_type_id" name="repair_type_id" class="input" required>
                            <option value="">— Сонгох —</option>
                            @foreach ($repairTypes as $repairType)
                                <option value="{{ $repairType->id }}" @selected(old('repair_type_id') == $repairType->id)>{{ $repairType->name }}</option>
                            @endforeach
                        </select>
                        @error('repair_type_id') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="repair_category_id">Систем, төхөөрөмжийн нэр <span class="req" id="categoryReq" hidden>*</span></label>
                        <select id="repair_category_id" name="repair_category_id" class="input" data-selected="{{ old('repair_category_id') }}">
                            <option value="">— Эхлээд ангилал сонгоно уу —</option>
                        </select>
                        @error('repair_category_id') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="assignee-box" id="assigneeBox" hidden>
                    <span class="assignee-avatar" id="assigneeAvatar" aria-hidden="true"></span>
                    <span>
                        <span class="assignee-label">Гүйцэтгэгч</span>
                        <strong id="assigneeName"></strong>
                        <span class="assignee-note" id="assigneeNote"></span>
                    </span>
                </div>
            </section>

            <section class="step">
                <div class="step-head">
                    <span class="step-num">3</span>
                    <div>
                        <h3 class="step-title">Тайлбар, хавсралт</h3>
                        <div class="step-desc">Асуудлыг ойлгомжтой тайлбарлаж, боломжтой бол зураг хавсаргана уу.</div>
                    </div>
                </div>
                <div class="field">
                    <label for="description">Гэмтэл, хүсэлтийн тайлбар <span class="req">*</span></label>
                    <textarea id="description" name="description" class="input" rows="6" maxlength="5000" required
                              placeholder="Жишээ: Өнөө өглөөнөөс dis.energy.mn руу нэвтрэхэд «500 Server Error» гарч байна. Хэд хэдэн компьютер дээр ижил байдалтай.">{{ old('description') }}</textarea>
                    <div class="counter" id="counter">0 / 5000</div>
                    @error('description') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="attachments">Зураг, файл хавсаргах</label>
                    <div class="dropzone" id="dropzone">
                        <input id="attachments" name="attachments[]" type="file" multiple
                               accept="{{ collect(explode(',', \App\Http\Controllers\TicketController::FILE_TYPES))->map(fn ($e) => '.'.$e)->implode(',') }}">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                        <strong>Файлаа энд чирж оруулах эсвэл <span>сонгох</span></strong>
                        <small>Хамгийн ихдээ {{ \App\Http\Controllers\TicketController::MAX_FILES }} файл, тус бүр {{ \App\Http\Controllers\TicketController::MAX_FILE_KB / 1024 }} MB · Зураг, PDF, Word, Excel, архив</small>
                    </div>
                    <ul class="files" id="files"></ul>
                    <div class="error" id="fileError" hidden></div>
                    @error('attachments') <div class="error">{{ $message }}</div> @enderror
                    @foreach ($errors->get('attachments.*') as $messages)
                        <div class="error">{{ $messages[0] }}</div>
                    @endforeach
                </div>
            </section>

            <section class="step">
                <div class="step-head">
                    <span class="step-num">4</span>
                    <div>
                        <h3 class="step-title">Яаралтай байдал, хугацаа</h3>
                        <div class="step-desc">Ажилд хэр нөлөөлж байгааг сонгоно уу.</div>
                    </div>
                </div>
                <div class="field">
                    <div class="choice-group urgency-group" role="radiogroup" aria-label="Яаралтай байдал">
                        @foreach (\App\Models\Ticket::URGENCIES as $value => $label)
                            <label class="choice urgency u-{{ $value }}">
                                <input type="radio" name="urgency" value="{{ $value }}" @checked(old('urgency', 'normal') === $value) required>
                                <span class="choice-icon" aria-hidden="true"></span>
                                <span class="choice-body">
                                    <strong>{{ $label }}</strong>
                                    <small>{{ $urgencyMeta[$value] }}</small>
                                </span>
                                <span class="choice-check" aria-hidden="true"></span>
                            </label>
                        @endforeach
                    </div>
                    @error('urgency') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label for="sent_at">Илгээх огноо</label>
                        {{-- Зөвхөн харуулна; хадгалахдаа серверийн цагийг авна --}}
                        <input id="sent_at" type="text" class="input" value="{{ now()->format('Y-m-d H:i') }}" readonly aria-readonly="true">
                        <div class="field-hint">Илгээх үеийн огноо, цаг автоматаар бүртгэгдэнэ.</div>
                    </div>
                </div>
            </section>

            <div class="submit-bar">
                <p><span class="req">*</span> тэмдэгтэй талбарыг заавал бөглөнө.</p>
                <div class="form-actions" style="margin:0">
                    <a href="{{ route('tickets.index') }}" class="btn btn-ghost">Болих</a>
                    <button type="submit" class="btn" id="submitBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        Илгээх
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>

<script>
    (function () {
        // Илгээх огноог хуудас нээлттэй байх хооронд минут тутам шинэчилнэ
        const sentAt = document.getElementById('sent_at');
        const pad = (n) => String(n).padStart(2, '0');
        setInterval(() => {
            const d = new Date();
            sentAt.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }, 30000);

        // Ангилал → систем, төхөөрөмжийн шүүлт
        const categories = @json($categories, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        const typeSelect = document.getElementById('repair_type_id');
        const catSelect = document.getElementById('repair_category_id');
        const catReq = document.getElementById('categoryReq');
        let selected = catSelect.dataset.selected;

        function fillCategories() {
            const typeId = Number(typeSelect.value);
            const items = categories.filter((c) => c.repair_type_id === typeId);

            catSelect.innerHTML = '';
            const placeholder = new Option(!typeId ? '— Эхлээд ангилал сонгоно уу —' : items.length ? '— Сонгох —' : '— Бүртгэлгүй —', '');
            catSelect.add(placeholder);
            items.forEach((c) => catSelect.add(new Option(c.name, c.id, false, String(c.id) === selected)));

            catSelect.disabled = items.length === 0;
            catSelect.required = items.length > 0;
            catReq.hidden = items.length === 0;
            selected = '';
            showAssignee();
        }

        // Сонгосон систем, төхөөрөмжийн гүйцэтгэгчийг харуулна
        const box = document.getElementById('assigneeBox');
        const boxAvatar = document.getElementById('assigneeAvatar');
        const boxName = document.getElementById('assigneeName');
        const boxNote = document.getElementById('assigneeNote');

        function showAssignee() {
            const category = categories.find((c) => String(c.id) === catSelect.value);
            box.hidden = !typeSelect.value || (catSelect.options.length > 1 && !category);
            if (box.hidden) return;

            const assignee = category && category.assignee;
            box.classList.toggle('none', !assignee);
            if (assignee) {
                boxAvatar.textContent = assignee.name.split(/\s+/).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
                boxName.textContent = assignee.name + (assignee.department ? ' · ' + assignee.department : '');
                boxNote.textContent = 'Дуудлага шууд хуваарилагдаж, гүйцэтгэгчид мэйлээр мэдэгдэнэ.';
            } else {
                boxAvatar.textContent = '?';
                boxName.textContent = 'Оноогоогүй';
                boxNote.textContent = 'Админ дуудлагыг хүлээн аваад гүйцэтгэгчид хуваарилна.';
            }
        }

        typeSelect.addEventListener('change', fillCategories);
        catSelect.addEventListener('change', showAssignee);
        fillCategories();

        // Тэмдэгт тоологч
        const description = document.getElementById('description');
        const counter = document.getElementById('counter');
        function count() {
            const n = description.value.length;
            counter.textContent = `${n} / 5000`;
            counter.classList.toggle('warn', n > 4500);
        }
        description.addEventListener('input', count);
        count();

        // Файл: чирж оруулах, жагсаалт, устгах
        const MAX_FILES = {{ \App\Http\Controllers\TicketController::MAX_FILES }};
        const MAX_BYTES = {{ \App\Http\Controllers\TicketController::MAX_FILE_KB }} * 1024;
        const input = document.getElementById('attachments');
        const dropzone = document.getElementById('dropzone');
        const list = document.getElementById('files');
        const fileError = document.getElementById('fileError');
        let files = [];

        const human = (b) => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';

        function sync() {
            const dt = new DataTransfer();
            files.forEach((f) => dt.items.add(f));
            input.files = dt.files;

            list.innerHTML = '';
            files.forEach((f, i) => {
                const li = document.createElement('li');
                let thumb;
                if (f.type.startsWith('image/')) {
                    thumb = document.createElement('img');
                    thumb.src = URL.createObjectURL(f);
                    thumb.onload = () => URL.revokeObjectURL(thumb.src);
                    thumb.alt = '';
                } else {
                    thumb = document.createElement('span');
                    thumb.textContent = (f.name.split('.').pop() || 'файл').slice(0, 4);
                }
                thumb.className = 'thumb';

                const name = document.createElement('span');
                name.className = 'name';
                name.textContent = f.name;
                name.title = f.name;

                const size = document.createElement('span');
                size.className = 'size';
                size.textContent = human(f.size);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', f.name + ' хасах');
                remove.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
                remove.addEventListener('click', () => { files.splice(i, 1); sync(); });

                li.append(thumb, name, size, remove);
                list.appendChild(li);
            });
        }

        function addFiles(incoming) {
            const problems = [];
            Array.from(incoming).forEach((f) => {
                if (files.length >= MAX_FILES) { problems.push(`Хамгийн ихдээ ${MAX_FILES} файл хавсаргана.`); return; }
                if (f.size > MAX_BYTES) { problems.push(`«${f.name}» ${MAX_BYTES / 1048576} MB-аас их байна.`); return; }
                if (files.some((x) => x.name === f.name && x.size === f.size)) return;
                files.push(f);
            });
            fileError.hidden = problems.length === 0;
            fileError.textContent = [...new Set(problems)].join(' ');
            sync();
        }

        input.addEventListener('change', () => addFiles(input.files));
        ['dragenter', 'dragover'].forEach((e) => dropzone.addEventListener(e, (ev) => { ev.preventDefault(); dropzone.classList.add('drag'); }));
        ['dragleave', 'drop'].forEach((e) => dropzone.addEventListener(e, () => dropzone.classList.remove('drag')));
        dropzone.addEventListener('drop', (ev) => { ev.preventDefault(); addFiles(ev.dataTransfer.files); });

        // Давхар илгээхээс сэргийлэх
        document.getElementById('ticketForm').addEventListener('submit', () => {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.lastChild.textContent = ' Илгээж байна...';
        });
    })();
</script>
@endsection
