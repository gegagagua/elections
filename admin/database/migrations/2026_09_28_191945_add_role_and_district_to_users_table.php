<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('password');
            $table->foreignId('district_id')->nullable()->after('role')->constrained('districts')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->after('district_id')->constrained('users')->nullOnDelete();
        });

        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['created_by_id']);
            $table->dropColumn(['role', 'district_id', 'created_by_id']);
        });
    }
};
