<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Хүсэлтийн систем (Тохиргоо → Засвар ангилал). system баганад нэрийг нь хадгалсаар байна.
        Schema::table('access_requests', function (Blueprint $table) {
            $table->foreignId('repair_category_id')->nullable()->after('request_type_id')->constrained()->nullOnDelete();
        });

        // Хандах эрх хүссэн модулиуд
        Schema::create('access_request_module', function (Blueprint $table) {
            $table->foreignId('access_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('system_module_id')->constrained()->restrictOnDelete();
            $table->primary(['access_request_id', 'system_module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_request_module');

        Schema::table('access_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('repair_category_id');
        });
    }
};
