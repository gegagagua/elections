<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('admin_id')->constrained('districts')->cascadeOnDelete();
            $table->index('district_id');
        });

        $adminIds = DB::table('customers')->distinct()->pluck('admin_id');
        foreach ($adminIds as $adminId) {
            $districtId = DB::table('districts')->insertGetId([
                'admin_id' => $adminId,
                'name' => 'ძირითადი უბანი',
                'description' => 'ავტომატურად შექმნილი უბანი',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('customers')->where('admin_id', $adminId)->update(['district_id' => $districtId]);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropIndex(['district_id']);
            $table->dropColumn('district_id');
        });
    }
};
