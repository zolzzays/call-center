<?php

use App\Models\AccessRequest;
use App\Notifications\AccessReviewDigest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * МАБ: тогтмол хяналт (өдөр бүр)
 *  - дуусах огноо өнгөрсөн эрхийг цуцлах
 *  - 6 сар тутам нууц үг солих сануулга
 *  - 60 хоног хянагдаагүй идэвхтэй эрхийн жагсаалтыг МАБ инженерт илгээх
 */
Artisan::command('access:monitor', function () {
    $expired = AccessRequest::where('status', 'granted')->whereDate('ends_on', '<', today())->get();
    $expired->each(fn (AccessRequest $r) => $r->revoke(null, 'Эрхийн хугацаа '.$r->ends_on->format('Y-m-d').'-нд дууссан', 'expired'));

    $cutoff = now()->subMonths(AccessRequest::PASSWORD_MONTHS);
    $reminders = AccessRequest::where('status', 'granted')
        ->where('granted_at', '<=', $cutoff)
        ->where(fn ($q) => $q->whereNull('password_reminded_at')->orWhere('password_reminded_at', '<=', $cutoff))
        ->get();
    $reminders->each->remindPassword();

    $stale = AccessRequest::where('status', 'granted')->with('user')->get()->filter->needsReview()->values();
    if ($stale->isNotEmpty()) {
        foreach (AccessRequest::staff('security') as $engineer) {
            if (filled($engineer->email)) {
                rescue(fn () => $engineer->notify(new AccessReviewDigest($stale)));
            }
        }
    }

    $this->info("Хугацаа дууссан: {$expired->count()}, нууц үгийн сануулга: {$reminders->count()}, 60+ хоног хянагдаагүй: {$stale->count()}");
})->purpose('МАБ-ын хандах эрхийн тогтмол хяналт');

Schedule::command('access:monitor')->dailyAt('08:00');
