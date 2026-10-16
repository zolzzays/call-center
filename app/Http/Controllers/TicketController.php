<?php

namespace App\Http\Controllers;

use App\Models\RepairCategory;
use App\Models\RepairType;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public const MAX_FILES = 5;

    public const MAX_FILE_KB = 10240;

    public const FILE_TYPES = 'jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z';

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q'));
        $urgency = (string) $request->query('urgency');
        $urgency = array_key_exists($urgency, Ticket::URGENCIES) ? $urgency : null;
        // open = шийдвэрлэгдээгүй (анхдагч), all = бүгд, эсвэл тодорхой төлөв
        $status = (string) $request->query('status', 'open');
        $status = in_array($status, ['open', 'all', 'postponed', 'closed'], true) ? $status : 'open';

        // Хянах самбараас ирэх хугацааны шүүлт (илгээсэн огноо, Y-m-d)
        $from = $this->dateParam($request, 'from')?->startOfDay();
        $to = $this->dateParam($request, 'to')?->endOfDay();

        $visible = fn () => Ticket::visibleTo($user)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
        $byStatus = fn ($q) => match ($status) {
            'open' => $q->whereIn('status', Ticket::OPEN_STATUSES),
            'all' => $q,
            default => $q->where('status', $status),
        };

        $tickets = $visible()
            ->with(['user', 'assignee', 'repairType', 'repairCategory'])
            ->withCount('attachments')
            ->tap($byStatus)
            ->when($urgency, fn ($q) => $q->where('urgency', $urgency))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = $visible()->tap($byStatus)->selectRaw('urgency, count(*) as total')->groupBy('urgency')->pluck('total', 'urgency');
        $statusCounts = $visible()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('tickets.index', [
            'tickets' => $tickets,
            'search' => $search,
            'urgency' => $urgency,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'counts' => $counts,
            'statusCounts' => [
                'open' => collect(Ticket::OPEN_STATUSES)->sum(fn ($s) => $statusCounts[$s] ?? 0),
                'postponed' => $statusCounts['postponed'] ?? 0,
                'closed' => $statusCounts['closed'] ?? 0,
                'all' => $statusCounts->sum(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureCanSubmit($request->user());

        return view('tickets.create', [
            'repairTypes' => RepairType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => RepairCategory::where('is_active', true)
                ->with('assignee.department')
                ->orderBy('name')
                ->get()
                ->map(function (RepairCategory $c) {
                    $assignee = $this->autoAssignee($c);

                    return [
                        'id' => $c->id,
                        'repair_type_id' => $c->repair_type_id,
                        'name' => $c->name,
                        'assignee' => $assignee
                            ? ['name' => $assignee->name, 'department' => $assignee->department?->name]
                            : null,
                    ];
                }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanSubmit($request->user());

        $typeId = $request->integer('repair_type_id');

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Ticket::TYPES))],
            'repair_type_id' => ['required', 'integer', Rule::exists('repair_types', 'id')->where('is_active', true)],
            'repair_category_id' => [
                // Сонгосон ангилалд систем, төхөөрөмж бүртгэлтэй бол заавал сонгоно
                Rule::requiredIf(fn () => RepairCategory::where('repair_type_id', $typeId)->where('is_active', true)->exists()),
                'nullable', 'integer',
                Rule::exists('repair_categories', 'id')->where('repair_type_id', $typeId)->where('is_active', true),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'urgency' => ['required', Rule::in(array_keys(Ticket::URGENCIES))],
            'due_at' => ['nullable', 'date', 'after:now'],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'attachments.*' => ['file', 'max:'.self::MAX_FILE_KB, 'mimes:'.self::FILE_TYPES],
        ], [
            'required' => ':attribute талбарыг бөглөнө үү.',
            'repair_category_id.required' => ':attribute сонгоно уу.',
            'in' => ':attribute буруу утгатай байна.',
            'exists' => 'Сонгосон :attribute олдсонгүй.',
            'description.max' => ':attribute 5000 тэмдэгтээс хэтрэхгүй байна.',
            'due_at.after' => ':attribute одоогоос хойш байх ёстой.',
            'date' => ':attribute буруу огноо байна.',
            'attachments.max' => 'Хамгийн ихдээ '.self::MAX_FILES.' файл хавсаргана.',
            'attachments.*.max' => 'Файл бүр '.(self::MAX_FILE_KB / 1024).' MB-аас хэтрэхгүй байна.',
            'attachments.*.mimes' => 'Зөвшөөрөгдөөгүй файлын төрөл байна.',
            'attachments.*.file' => 'Файл амжилттай хуулагдсангүй.',
            'attachments.*.uploaded' => 'Файл амжилттай хуулагдсангүй.',
        ], [
            'type' => 'Төрөл',
            'repair_type_id' => 'Ангилал',
            'repair_category_id' => 'Систем, төхөөрөмж',
            'description' => 'Тайлбар',
            'urgency' => 'Яаралтай байдал',
            'due_at' => 'Хүссэн хугацаа',
        ]);

        $stored = [];

        try {
            $ticket = DB::transaction(function () use ($request, $data, &$stored) {
                $ticket = Ticket::createWithNumber(
                    collect($data)->except('attachments')->all() + ['user_id' => $request->user()->id]
                );

                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store('tickets/'.$ticket->id, 'local');
                    $stored[] = $path;

                    $ticket->attachments()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }

                return $ticket;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($stored);
            throw $e;
        }

        $message = "{$ticket->typeLabel()} №{$ticket->number} дугаартай бүртгэгдлээ.";

        // Засвар ангилалд гүйцэтгэгч оноосон бол автоматаар хуваарилж мэйлээр мэдэгдэнэ
        if ($assignee = $this->autoAssignee($ticket->repairCategory)) {
            $ticket->assignTo($assignee);
            $message .= " Гүйцэтгэгч: {$assignee->name}.";
        }

        return redirect()->route('tickets.show', $ticket)->with('status', $message);
    }

    public function show(Request $request, Ticket $ticket): View
    {
        abort_unless($ticket->isVisibleTo($request->user()), 403);

        $ticket->load(['user.department', 'assignee', 'repairType', 'repairCategory', 'attachments', 'activities.user', 'activities.assignee']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'assignees' => $request->user()->isAdmin() ? $this->assignees($ticket->assignee_id) : collect(),
        ]);
    }

    /** Админ дуудлагыг гүйцэтгэгчид хуваарилна */
    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->isClosed()) {
            return back()->with('error', 'Хаагдсан дуудлагыг хуваарилах боломжгүй.');
        }

        $data = $request->validate([
            'assignee_id' => ['required', 'integer', Rule::in($this->assignees()->modelKeys())],
        ], [
            'assignee_id.required' => 'Гүйцэтгэгч сонгоно уу.',
            'assignee_id.in' => 'Сонгосон хэрэглэгч идэвхтэй гүйцэтгэгч биш байна.',
        ]);

        $assignee = User::findOrFail($data['assignee_id']);
        $mailed = $ticket->assignTo($assignee, $request->user());

        return back()->with('status', "№{$ticket->number} дуудлагыг {$assignee->name}-д хуваариллаа."
            .($mailed ? ' Мэйлээр мэдэгдлээ.' : ' (Мэйл илгээгдсэнгүй — гүйцэтгэгчийн и-мэйл бүртгэлгүй эсвэл мэйлийн тохиргоо буруу.)'));
    }

    /** Гүйцэтгэгч шийдвэрлэсэн тайлбартай хаана */
    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->canBeWorkedBy($request->user()), 403);

        $data = $request->validate([
            'resolution' => ['required', 'string', 'max:5000'],
        ], [
            'resolution.required' => 'Юу хийж шийдвэрлэснээ бичнэ үү.',
            'resolution.max' => 'Тайлбар 5000 тэмдэгтээс хэтрэхгүй байна.',
        ]);

        $ticket->close($request->user(), $data['resolution']);

        return redirect()->route('tickets.show', $ticket)->with('status', "№{$ticket->number} дуудлага хаагдлаа.");
    }

    /** Гүйцэтгэгч шалтгаан, шинэ хугацаатай хойшлуулна */
    public function postpone(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->canBeWorkedBy($request->user()), 403);

        $data = $request->validate([
            'postponed_until' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'max:2000'],
        ], [
            'postponed_until.required' => 'Хэзээ хүртэл хойшлуулахаа сонгоно уу.',
            'postponed_until.date' => 'Огноо буруу байна.',
            'postponed_until.after' => 'Хугацаа одоогоос хойш байх ёстой.',
            'reason.required' => 'Хойшлуулах шалтгаанаа бичнэ үү.',
            'reason.max' => 'Шалтгаан 2000 тэмдэгтээс хэтрэхгүй байна.',
        ]);

        $until = Carbon::parse($data['postponed_until']);
        $ticket->postpone($request->user(), $until, $data['reason']);

        return redirect()->route('tickets.show', $ticket)
            ->with('status', "№{$ticket->number} дуудлагыг {$until->format('Y-m-d H:i')} хүртэл хойшлууллаа.");
    }

    /** Засвар ангилалд оноосон гүйцэтгэгч идэвхтэй бол буцаана */
    private function autoAssignee(?RepairCategory $category): ?User
    {
        $assignee = $category?->assignee;

        return $assignee && $assignee->is_active && $assignee->isAssignee() ? $assignee : null;
    }

    private function dateParam(Request $request, string $key): ?Carbon
    {
        $value = (string) $request->query($key);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function ensureCanSubmit(User $user): void
    {
        abort_unless($user->canSubmitTickets(), 403, 'Таны эрхээр дуудлага илгээх боломжгүй.');
    }

    /** Идэвхтэй гүйцэтгэгчид. Одоо томилогдсон нь идэвхгүй болсон ч жагсаалтад үлдэнэ. */
    private function assignees(?int $currentId = null): Collection
    {
        return User::assignees($currentId)
            ->with('department')
            ->orderBy('name')
            ->get();
    }

    public function attachment(Request $request, Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        abort_unless($ticket->isVisibleTo($request->user()) && $attachment->ticket_id === $ticket->id, 403);

        $disposition = $attachment->isImage() && $request->boolean('inline') ? 'inline' : 'attachment';

        return Storage::disk('local')->response($attachment->path, $attachment->original_name, [], $disposition);
    }
}
