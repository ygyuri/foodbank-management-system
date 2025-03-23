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
        Schema::table('feedbacks', function (Blueprint $table) {
            // Drop the old reference columns
            $table->dropForeign(['request_fb_id']);
            $table->dropColumn('request_fb_id');

            $table->dropForeign(['donation_request_id']);
            $table->dropColumn('donation_request_id');

            $table->dropForeign(['donation_id']);
            $table->dropColumn('donation_id');

            // Add the new writable reference field
            $table->string('reference')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            // Re-add the dropped columns
            $table->foreignId('request_fb_id')->nullable()->constrained('requestsfbs')->onDelete('cascade');
            $table->foreignId('donation_request_id')->nullable()->constrained('donation_requests')->onDelete('cascade');
            $table->foreignId('donation_id')->nullable()->constrained('donations')->onDelete('cascade');

            // Drop the reference field
            $table->dropColumn('reference');
        });
    }
};
