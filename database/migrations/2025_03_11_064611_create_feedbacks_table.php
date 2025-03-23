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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            
            // Sender and Receiver relationships
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');

            // Feedback content
            $table->text('thank_you_note')->nullable();
            $table->integer('rating')->default(5)->unsigned();
            $table->text('message')->nullable();
            $table->enum('type', ['request_fb', 'donation_request', 'donation'])->default('request_fb');

            // References to feedback subjects
            $table->foreignId('request_fb_id')->nullable()->constrained('requestsfbs')->onDelete('cascade');
            $table->foreignId('donation_request_id')->nullable()->constrained('donation_requests')->onDelete('cascade');
            $table->foreignId('donation_id')->nullable()->constrained('donations')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
