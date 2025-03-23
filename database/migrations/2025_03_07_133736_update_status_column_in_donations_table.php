<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE donations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'assigned', 'completed', 'delivered') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("ALTER TABLE donations MODIFY COLUMN status ENUM('pending', 'assigned', 'delivered') DEFAULT 'pending'");
    }
};
