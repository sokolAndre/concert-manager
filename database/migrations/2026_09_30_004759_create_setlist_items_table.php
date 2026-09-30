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
        Schema::create('setlist_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('setlist_set_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('song_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('position');

            $table->string('key')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['setlist_set_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setlist_items');
    }
};
