<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('id')->constrained('departments')->restrictOnDelete();
            $table->dropUnique(['name']);
            // Нэг албан тушаалын нэр өөр өөр алба, хэлтэст давтагдаж болно
            $table->unique(['department_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropUnique(['department_id', 'name']);
            $table->dropConstrainedForeignId('department_id');
            $table->unique('name');
        });
    }
};
