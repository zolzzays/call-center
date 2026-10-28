<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Хамтран гүйцэтгэгч: үндсэн гүйцэтгэгчтэй ижил эрхтэйгээр хүсэлтийг харж, хааж, хойшлуулна
        Schema::table('repair_categories', function (Blueprint $table) {
            $table->foreignId('co_assignee_id')->nullable()->after('assignee_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('co_assignee_id')->nullable()->after('assignee_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('co_assignee_id');
        });

        Schema::table('repair_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('co_assignee_id');
        });
    }
};
