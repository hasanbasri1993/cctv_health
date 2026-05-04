<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('device_health_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('temperature')->nullable()->after('response_time_ms');
        });
    }

    public function down(): void
    {
        Schema::table('device_health_logs', function (Blueprint $table) {
            $table->dropColumn('temperature');
        });
    }
};
