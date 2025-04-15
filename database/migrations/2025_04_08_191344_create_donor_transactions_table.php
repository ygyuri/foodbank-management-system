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
        Schema::create('donor_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
            $table->string('transaction_type'); // e.g., donation_created, donation_distributed
            $table->unsignedBigInteger('related_id'); // Links to donations
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
         Schema::dropIfExists('donor_transactions');
     }
};
