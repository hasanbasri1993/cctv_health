<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('channel_number');
            $table->string('name')->nullable();
            $table->string('status')->default('unknown');
            $table->timestamp('last_status_change')->nullable();
            $table->tinyInteger('signal_quality')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index('status');
            $table->unique(['device_id', 'channel_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_channels');
    }
};
