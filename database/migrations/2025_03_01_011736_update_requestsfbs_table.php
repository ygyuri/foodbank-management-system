<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::table('requestsfbs', function (Blueprint $table) {
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('cascade'); // Add recipient_id
            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled'])->default('pending'); // Add status column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() {
        Schema::table('requestsfbs', function (Blueprint $table) {
            $table->dropForeign(['recipient_id']);
            $table->dropColumn(['recipient_id', 'status']);
        });
    }
};
