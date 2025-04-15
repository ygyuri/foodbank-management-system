<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('foodbank_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foodbank_id')->constrained('users')->onDelete('cascade');
            $table->string('activity_type'); // e.g., received_donation, fulfilled_request, distributed_donation
            $table->unsignedBigInteger('related_id'); // Links to donations or donation_requests
            $table->integer('quantity');
            $table->text('description')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('foodbank_activities');
    }
};
