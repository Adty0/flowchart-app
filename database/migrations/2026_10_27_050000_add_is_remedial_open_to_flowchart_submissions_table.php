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
        if (!Schema::hasColumn('flowchart_submissions', 'is_remedial_open')) {
            Schema::table('flowchart_submissions', function (Blueprint $table) {
                $table->boolean('is_remedial_open')->default(false)->after('score');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('flowchart_submissions', 'is_remedial_open')) {
            Schema::table('flowchart_submissions', function (Blueprint $table) {
                $table->dropColumn('is_remedial_open');
            });
        }
    }
};

