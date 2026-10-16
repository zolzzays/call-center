<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->restrictOnDelete();
            $table->foreignId('position_id')->nullable()->after('role_id')->constrained('positions')->nullOnDelete();
        });

        // Хуучин role (enum) баганын утгыг roles хүснэгт рүү шилжүүлнэ
        foreach (DB::table('roles')->pluck('id', 'slug') as $slug => $id) {
            DB::table('users')->where('role', $slug)->update(['role_id' => $id]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'user'])->default('user')->after('email');
        });

        foreach (DB::table('roles')->whereIn('slug', ['admin', 'user'])->pluck('id', 'slug') as $slug => $id) {
            DB::table('users')->where('role_id', $id)->update(['role' => $slug]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
