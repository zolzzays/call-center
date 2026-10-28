<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** dis.energy.mn системийн модулиуд: бүлэг => модулиуд */
    private const DIS_MODULES = [
        'Хянах самбар' => ['Түлшний мэдээ'],
        'Шуурхай ажиллагаа' => [
            'Эх үүсвэр', 'Орон нутгийн мэдээ', 'Захиалгын журнал', 'Тасралтын мэдээ', 'Захиалгат ажил',
            'Тооцооны журнал', 'Ачааллын график', 'Дулааны мэдээ', 'СМС илгээх', 'Скада', 'Скада их ачаалал',
            'Тооцооны журнал БХ', 'Тооцооны журнал импорт', 'Телефон мэдээ', 'Өндөр давтамж',
        ],
        'Горим төлөвлөлт' => [
            'Цахилгааны горим', 'Дулааны горим', 'Насос станц', 'Дулааны мэдээ', 'УДДТ параметр',
            'Импорт, экспорт', 'СЭХ-ны горим, гүйцэтгэл', 'ДЦС-ын горим, гүйцэтгэл',
        ],
        'Тайлан' => ['Скада бусад'],
    ];

    public function up(): void
    {
        // Програм хангамжийн системийн (засварын ангилал) модулиуд; бүлэг нь өөрөө модуль (parent_id = null)
        Schema::create('system_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('system_modules')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['repair_category_id', 'parent_id', 'name']);
        });

        $systemId = DB::table('repair_categories')->where('name', 'dis.energy.mn')->value('id');
        if (! $systemId) {
            return;
        }

        foreach (self::DIS_MODULES as $group => $modules) {
            $groupId = DB::table('system_modules')->insertGetId([
                'repair_category_id' => $systemId, 'name' => $group, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($modules as $module) {
                DB::table('system_modules')->insert([
                    'repair_category_id' => $systemId, 'parent_id' => $groupId, 'name' => $module,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_modules');
    }
};
