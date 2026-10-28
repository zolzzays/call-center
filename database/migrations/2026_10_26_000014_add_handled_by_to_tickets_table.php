<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Хүсэлтийг сүүлд хаасан, хойшлуулсан эсвэл буцаасан гүйцэтгэгч
            $table->foreignId('handled_by_id')->nullable()->after('co_assignee_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by_id');
        });
    }
};
