<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // МАБ-ын хандах эрхийн хүсэлтийг батлах үүрэг: head (албаны дарга), security (МАБ инженер), chief (Ерөнхий диспетчер)
        Schema::table('positions', function (Blueprint $table) {
            $table->string('access_role', 20)->nullable()->after('name');
        });

        DB::table('positions')->whereIn('name', ['Албаны дарга', 'Төвийн дарга'])->update(['access_role' => 'head']);
        DB::table('positions')->where('name', 'Мэдээллийн аюулгүй байдлын инженер')->update(['access_role' => 'security']);
        DB::table('positions')->where('name', 'Ерөнхий диспетчер')->update(['access_role' => 'chief']);

        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('system');
            $table->string('access_type', 20);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('reason');
            $table->string('status', 30)->index();
            $table->timestamps();
        });

        Schema::create('access_request_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage', 20)->nullable();
            $table->string('action', 20);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_request_steps');
        Schema::dropIfExists('access_requests');

        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('access_role');
        });
    }
};
