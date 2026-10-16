<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Гүйцэтгэгч эрх кодоор шалгагддаг тул системийн эрх болгож түгжинэ
        $exists = DB::table('roles')->where('slug', 'assignee')->exists();

        if ($exists) {
            DB::table('roles')->where('slug', 'assignee')->update(['is_system' => true, 'updated_at' => now()]);
        } else {
            DB::table('roles')->insert([
                'name' => 'Гүйцэтгэгч', 'slug' => 'assignee', 'description' => 'Өөрт ирсэн дуудлагыг гүйцэтгэнэ',
                'is_system' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('assignee_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable()->after('assignee_id');
            $table->index(['assignee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['assignee_id', 'created_at']);
            $table->dropConstrainedForeignId('assignee_id');
            $table->dropColumn('assigned_at');
        });
    }
};
