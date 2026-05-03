<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->unsignedSmallInteger('port')->default(80);
            $table->string('username');
            $table->text('password');
            $table->string('model')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('status')->default('unknown');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
