<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Өмнө нь програмын цагийн бүс UTC байсан тул систем автоматаар бүртгэсэн цаг 8 цагаар хоцорч хадгалагдсан.
 * Цагийн бүсийг Asia/Ulaanbaatar болгосны дараа хуучин бичлэгүүдийг нэг удаа +8 цагаар засна.
 *
 * Хэрэглэгчийн өөрөө оруулсан хугацааг (tickets.due_at, postponed_until) өөрчлөхгүй:
 * тэдгээр нь маягтаас орон нутгийн цагаар орж ирсэн.
 */
return new class extends Migration
{
    private const HOURS = 8;

    /** @var array<string, list<string>> хүснэгт => систем бүртгэсэн цагийн баганууд */
    private const COLUMNS = [
        'users' => ['created_at', 'updated_at', 'last_login_at', 'email_verified_at'],
        'roles' => ['created_at', 'updated_at'],
        'positions' => ['created_at', 'updated_at'],
        'departments' => ['created_at', 'updated_at'],
        'repair_types' => ['created_at', 'updated_at'],
        'repair_categories' => ['created_at', 'updated_at'],
        'tickets' => ['created_at', 'updated_at', 'assigned_at', 'closed_at'],
        'ticket_attachments' => ['created_at', 'updated_at'],
        'ticket_activities' => ['created_at'],
    ];

    public function up(): void
    {
        $this->shift('+');
    }

    public function down(): void
    {
        $this->shift('-');
    }

    private function shift(string $sign): void
    {
        // INTERVAL нь MySQL/MariaDB-ийн синтакс; тестийн SQLite өгөгдлийн сан хоосон тул алгасна
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::COLUMNS as $table => $columns) {
            $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            if ($columns === []) {
                continue;
            }

            $set = implode(', ', array_map(fn ($c) => "`{$c}` = `{$c}` {$sign} INTERVAL ".self::HOURS.' HOUR', $columns));
            DB::statement("UPDATE `{$table}` SET {$set}");
        }
    }
};
