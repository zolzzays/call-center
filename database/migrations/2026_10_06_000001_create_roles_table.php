<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        DB::table('roles')->insert([
            ['name' => 'Админ', 'slug' => 'admin', 'description' => 'Системийн бүх тохиргоонд хандах эрхтэй', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Хэрэглэгч', 'slug' => 'user', 'description' => 'Энгийн хэрэглэгч', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
