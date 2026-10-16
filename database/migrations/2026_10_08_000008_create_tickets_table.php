<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 20);
            $table->foreignId('repair_type_id')->constrained('repair_types')->restrictOnDelete();
            $table->foreignId('repair_category_id')->nullable()->constrained('repair_categories')->restrictOnDelete();
            $table->text('description');
            $table->string('urgency', 20)->default('normal');
            $table->dateTime('due_at')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('tickets');
    }
};
