<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('class_name', 50)->nullable()->after('role'); // Contoh: "10 RPL 1"
        });

        Schema::table('flowchart_submissions', function (Blueprint $table) {
            $table->string('class_name', 50)->nullable()->after('student_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flowchart_submissions', function (Blueprint $table) {
            $table->dropColumn('class_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('class_name');
        });
    }
};

