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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('telegram_destination_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('offset_minutes');

            $table->timestampTz('send_at');
            $table->timestampTz('sent_at')->nullable();

            $table->timestamps();

            $table->index(['send_at', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
