<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', ['student', 'instructor'])
            ->update(['role' => 'user']);

        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'admin') NOT NULL DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('student', 'instructor', 'admin') NOT NULL DEFAULT 'student'");
    }
};
