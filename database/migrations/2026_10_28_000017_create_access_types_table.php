<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Өмнө кодонд бичигдсэн эрхийн төрлүүд */
    private const DEFAULTS = [
        'read' => 'Унших',
        'write' => 'Унших, засах',
        'admin' => 'Админ (бүрэн эрх)',
    ];

    public function up(): void
    {
        // МАБ-ын хандах эрхийн хүсэлтийн эрхийн төрөл (Тохиргоо → Эрхийн төрөл)
        Schema::create('access_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $ids = [];
        foreach (self::DEFAULTS as $key => $name) {
            $ids[$key] = DB::table('access_types')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        Schema::table('access_requests', function (Blueprint $table) {
            $table->foreignId('access_type_id')->nullable()->after('system')->constrained()->restrictOnDelete();
        });

        foreach ($ids as $key => $id) {
            DB::table('access_requests')->where('access_type', $key)->update(['access_type_id' => $id]);
        }

        Schema::table('access_requests', function (Blueprint $table) {
            $table->dropColumn('access_type');
        });
    }

    public function down(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            $table->string('access_type', 20)->nullable()->after('system');
        });

        foreach (DB::table('access_types')->pluck('name', 'id') as $id => $name) {
            $key = array_search($name, self::DEFAULTS, true);
            DB::table('access_requests')->where('access_type_id', $id)->update(['access_type' => $key ?: 'read']);
        }

        Schema::table('access_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('access_type_id');
        });

        Schema::dropIfExists('access_types');
    }
};
