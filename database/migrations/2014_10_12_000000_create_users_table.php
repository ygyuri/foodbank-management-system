<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 20)->unique();
            $table->string('nickname', 20)->default('');
            $table->string('avatar', 160)->default('');
            $table->string('email', 50)->unique();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending'); // Updated status field
            $table->tinyInteger('sex')->default(0)->comment('0: Male, 1: Female');
            $table->timestamp('birthday')->nullable();
            $table->string('description')->default('');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Newly added fields from the model
            $table->string('phone')->nullable();
            $table->enum('role', ['admin', 'foodbank', 'donor', 'recipient'])->index(); // Indexed for query performance
            $table->string('profile_picture')->nullable();
            $table->string('location')->nullable(); // For foodbank and donor location
            $table->string('address')->nullable(); // To store addresses for recipients
            $table->string('organization_name')->nullable(); // For recipients who are organizations
            $table->enum('recipient_type', ['individual', 'organization'])->nullable(); // Differentiate recipient types
            $table->string('donor_type')->nullable(); // Categorize donor types (corporate, individual)
            $table->text('notes')->nullable(); // Extra notes, particularly for foodbanks and recipients

            // Indexes for faster queries
            $table->index(['location', 'organization_name', 'recipient_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
