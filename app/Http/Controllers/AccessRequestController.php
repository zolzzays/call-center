<?php

namespace App\Http\Controllers;

use App\Models\AccessRequest;
use App\Models\AccessRequestStep;
use App\Models\AccessRequestType;
use App\Models\AccessType;
use App\Models\RepairCategory;
use App\Models\SystemModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** МАБ-ын хандах эрхийн хүсэлт: илгээх, шатлан батлах, эрх олгох, тогтмол хяналт & аудит */
class AccessRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $awaitingCount = AccessRequest::awaiting($user)->count();

        // Батлах ёстой зүйлтэй бол «Батлах» таб, эс бөгөөс өөрийн хүсэлтүүд анхдагч
        $tab = (string) $request->query('tab', $awaitingCount > 0 ? 'awaiting' : 'mine');
        $tab = in_array($tab, ['mine', 'awaiting', 'all'], true) ? $tab : 'mine';

        $query = match ($tab) {
            'awaiting' => AccessRequest::awaiting($user),
            'all' => AccessRequest::visibleTo($user),
            default => AccessRequest::where('user_id', $user->id),
        };

        return view('access-requests.index', [
            'requests' => $query->with(['user.department', 'accessType', 'requestType'])->withCount('modules')->latest('id')->paginate(20)->withQueryString(),
            'tab' => $tab,
            'awaitingCount' => $awaitingCount,
            'mineCount' => AccessRequest::where('user_id', $user->id)->count(),
            'allCount' => AccessRequest::visibleTo($user)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $systems = RepairCategory::where('is_active', true)
            ->with(['repairType', 'modules' => fn ($q) => $q->where('is_active', true)->orderBy('id')])
            ->orderBy('name')
            ->get();

        return view('access-requests.create', [
            // Засварын төрлөөр бүлэглэсэн системүүд
            'systems' => $systems->groupBy(fn (RepairCategory $c) => $c->repairType?->name ?? 'Бусад'),
            // Систем бүрийн модулиуд: бүлэг → модулиуд (маягтын JS ашиглана)
            'modules' => $systems->mapWithKeys(fn (RepairCategory $c) => [$c->id => $c->modules
                ->whereNull('parent_id')
                ->map(fn (SystemModule $g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'children' => $c->modules->where('parent_id', $g->id)->map(fn (SystemModule $m) => ['id' => $m->id, 'name' => $m->name])->values(),
                ])->values()]),
            'accessTypes' => AccessType::where('is_active', true)->orderBy('id')->pluck('name', 'id'),
            'requestTypes' => AccessRequestType::where('is_active', true)->orderBy('id')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $systemId = $request->integer('repair_category_id');
        $hasModules = SystemModule::where('repair_category_id', $systemId)->where('is_active', true)->exists();

        $data = $request->validate([
            'request_type_id' => ['required', 'integer', Rule::exists('access_request_types', 'id')->where('is_active', true)],
            'repair_category_id' => ['required', 'integer', Rule::exists('repair_categories', 'id')->where('is_active', true)],
            // Модуль бүртгэлтэй системд дор хаяж нэг модуль сонгоно
            'modules' => [Rule::requiredIf($hasModules), 'array'],
            'modules.*' => ['integer', Rule::exists('system_modules', 'id')->where('repair_category_id', $systemId)->where('is_active', true)],
            'access_type_id' => ['required', 'integer', Rule::exists('access_types', 'id')->where('is_active', true)],
            'starts_on' => ['required', 'date', 'after_or_equal:today'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'reason' => ['required', 'string', 'max:5000'],
        ], [
            'required' => ':attribute талбарыг бөглөнө үү.',
            'in' => ':attribute буруу утгатай байна.',
            'exists' => 'Сонгосон :attribute олдсонгүй.',
            'date' => ':attribute буруу огноо байна.',
            'starts_on.after_or_equal' => 'Эхлэх огноо өнөөдрөөс хойш байна.',
            'ends_on.after_or_equal' => 'Дуусах огноо эхлэх огнооноос хойш байна.',
            'max' => ':attribute хэт урт байна.',
            'modules.required' => 'Хандах эрх хүсэх модулиа сонгоно уу.',
            'modules.*.exists' => 'Сонгосон модуль энэ системийнх биш байна.',
        ], [
            'repair_category_id' => 'Системийн нэр',
            'request_type_id' => 'Хүсэлтийн төрөл',
            'access_type_id' => 'Эрхийн төрөл',
            'starts_on' => 'Эхлэх огноо',
            'ends_on' => 'Дуусах огноо',
            'reason' => 'Шалтгаан',
        ]);

        $moduleIds = array_map('intval', $data['modules'] ?? []);
        unset($data['modules']);
        $data['system'] = RepairCategory::find($data['repair_category_id'])->name;

        $accessRequest = AccessRequest::submit($request->user(), $data, $moduleIds);

        return redirect()->route('access-requests.show', $accessRequest)
            ->with('status', "Хандах эрхийн хүсэлт {$accessRequest->number} дугаартай илгээгдлээ. Одоо: {$accessRequest->statusLabel()}.");
    }

    public function show(Request $request, AccessRequest $accessRequest): View
    {
        abort_unless($accessRequest->isVisibleTo($request->user()), 403);

        $accessRequest->load(['user.department', 'user.position', 'steps.user', 'accessType', 'requestType', 'modules.parent']);

        return view('access-requests.show', [
            'r' => $accessRequest,
            'approvers' => $accessRequest->approvers(),
        ]);
    }

    public function approve(Request $request, AccessRequest $accessRequest): RedirectResponse
    {
        abort_unless($accessRequest->canBeApprovedBy($request->user()), 403);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $stage = AccessRequest::STAGES[$accessRequest->stage()];
        $accessRequest->approve($request->user(), $data['note'] ?? null);

        return redirect()->route('access-requests.show', $accessRequest)
            ->with('status', "{$stage}-ын шатанд зөвшөөрлөө. Одоо: {$accessRequest->statusLabel()}.");
    }

    public function reject(Request $request, AccessRequest $accessRequest): RedirectResponse
    {
        abort_unless($accessRequest->canBeApprovedBy($request->user()), 403);

        $data = $request->validate(
            ['reject_reason' => ['required', 'string', 'max:2000']],
            ['reject_reason.required' => 'Татгалзах шалтгаанаа бичнэ үү.'],
        );
        $accessRequest->reject($request->user(), $data['reject_reason']);

        return redirect()->route('access-requests.show', $accessRequest)->with('status', 'Хүсэлтийг татгалзлаа. Хүсэгчид мэдэгдлээ.');
    }

    /** 5–6. Системийн инженер эрх үүсгэж ID олгоод хэрэглэгчид түр нууц үг, заавар илгээнэ */
    public function grant(Request $request, AccessRequest $accessRequest): RedirectResponse
    {
        abort_unless($accessRequest->canBeGrantedBy($request->user()), 403);

        $data = $request->validate([
            'account_id' => ['required', 'string', 'max:255'],
            'temporary_password' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ], [
            'account_id.required' => 'Үүсгэсэн хэрэглэгчийн нэр / ID-г бичнэ үү.',
        ]);

        $accessRequest->grant($request->user(), $data['account_id'], $data['instructions'] ?? null, $data['temporary_password'] ?? null);

        return redirect()->route('access-requests.show', $accessRequest)
            ->with('status', 'Эрх олгосныг тэмдэглэж, хэрэглэгчид ID'.(filled($data['temporary_password'] ?? null) ? ', түр нууц үг' : '').', зааврыг мэйлээр илгээлээ.');
    }

    /** 7. МАБ инженер эрх идэвхтэй ашиглагдаж байгааг шалгасан гэж тэмдэглэнэ */
    public function review(Request $request, AccessRequest $accessRequest): RedirectResponse
    {
        abort_unless($accessRequest->canBeAuditedBy($request->user()), 403);

        $accessRequest->markReviewed($request->user());

        return back()->with('status', "{$accessRequest->number} эрхийг хянасан гэж тэмдэглэлээ.");
    }

    /** 7. МАБ инженер эрхийг цуцална; системийн инженерт хаахаар мэдэгдэнэ */
    public function revoke(Request $request, AccessRequest $accessRequest): RedirectResponse
    {
        abort_unless($accessRequest->canBeAuditedBy($request->user()), 403);

        $data = $request->validate([
            'revoke_reason' => ['required', Rule::in(array_keys(AccessRequest::REVOKE_REASONS))],
            'revoke_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'revoke_reason.required' => 'Цуцлах шалтгааныг сонгоно уу.',
        ]);

        $reason = AccessRequest::REVOKE_REASONS[$data['revoke_reason']]
            .(filled($data['revoke_note'] ?? null) ? ': '.$data['revoke_note'] : '');
        $accessRequest->revoke($request->user(), $reason);

        return back()->with('status', "{$accessRequest->number} эрхийг цуцаллаа. Хэрэглэгч, системийн инженерт мэдэгдлээ.");
    }

    /** 7. Тогтмол хяналт & аудит: идэвхтэй эрхүүд, аудит лог */
    public function audit(Request $request): View
    {
        abort_unless(AccessRequest::isAuditor($request->user()), 403);

        $active = AccessRequest::where('status', 'granted')->with(['user.department', 'accessType', 'requestType'])->orderBy('granted_at')->get();

        return view('access-requests.audit', [
            'active' => $active,
            'needsReview' => $active->filter->needsReview()->count(),
            'log' => AccessRequestStep::with(['user', 'accessRequest.user'])->latest('id')->paginate(30),
        ]);
    }
}
