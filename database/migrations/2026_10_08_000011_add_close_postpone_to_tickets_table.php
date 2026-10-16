<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dateTime('postponed_until')->nullable()->after('due_at');
            $table->text('resolution')->nullable()->after('status');
            $table->dateTime('closed_at')->nullable()->after('resolution');
            $table->index('status');
        });

        // Дуудлагын явцын түүх: хуваарилсан, хойшлуулсан, хаасан
        Schema::create('ticket_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->text('note')->nullable();
            $table->dateTime('postponed_until')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_activities');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['postponed_until', 'resolution', 'closed_at']);
        });
    }
};
