<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            // 5–6. Системийн инженер эрх үүсгэж хэрэглэгчид олгосон мэдээлэл (түр нууц үгийг хадгалахгүй)
            $table->string('account_id')->nullable()->after('reason');
            $table->text('instructions')->nullable()->after('account_id');
            $table->timestamp('granted_at')->nullable()->after('status');
            // 7. Тогтмол хяналт: сүүлд хянасан, нууц үг солих сануулга, цуцалсан
            $table->timestamp('last_reviewed_at')->nullable()->after('granted_at');
            $table->timestamp('password_reminded_at')->nullable()->after('last_reviewed_at');
            $table->timestamp('revoked_at')->nullable()->after('password_reminded_at');
            $table->string('revoke_reason')->nullable()->after('revoked_at');
        });

        // 5. Системийн инженер: эрх үүсгэж ID олгоно
        DB::table('positions')->where('name', 'Системийн админ')->whereNull('access_role')->update(['access_role' => 'system']);
    }

    public function down(): void
    {
        DB::table('positions')->where('access_role', 'system')->update(['access_role' => null]);

        Schema::table('access_requests', function (Blueprint $table) {
            $table->dropColumn(['account_id', 'instructions', 'granted_at', 'last_reviewed_at', 'password_reminded_at', 'revoked_at', 'revoke_reason']);
        });
    }
};
