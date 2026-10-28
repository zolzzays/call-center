<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // МАБ-ын хүсэлтийн төрөл (Тохиргоо → Хүсэлтийн төрөл)
        Schema::create('access_request_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach (['Шинэ хэрэглэгч үүсгэх', 'Хандах эрх өөрчлөх', 'Хандах эрх цуцлах'] as $name) {
            DB::table('access_request_types')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        Schema::table('access_requests', function (Blueprint $table) {
            $table->foreignId('request_type_id')->nullable()->after('user_id')
                ->constrained('access_request_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('request_type_id');
        });

        Schema::dropIfExists('access_request_types');
    }
};
