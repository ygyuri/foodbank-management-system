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
        Schema::create('donation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foodbank_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
            
            // Enum for request types
            $table->enum('type', ['food', 'supplies', 'monetary'])->default('food');
            
            $table->integer('quantity')->default(1);
            
            // Enum for status with default value
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_requests');
    }
};
