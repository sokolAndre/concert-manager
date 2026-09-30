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
        Schema::table('setlists', function (Blueprint $table) {
            $table->foreignId('concert_details_id')
                ->nullable()
                ->unique()
                ->constrained('concert_details')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setlists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('concert_details_id');
        });
    }
};
