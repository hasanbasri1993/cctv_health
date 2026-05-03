<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_storages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('storage_id');
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('capacity')->nullable();
            $table->unsignedBigInteger('used_space')->nullable();
            $table->string('health_status')->default('unknown');
            $table->tinyInteger('temperature')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'health_status']);
            $table->index('health_status');
            $table->unique(['device_id', 'storage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_storages');
    }
};
