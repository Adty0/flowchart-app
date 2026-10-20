<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('flowchart_submissions', 'case_key')) {
            Schema::table('flowchart_submissions', function (Blueprint $table) {
                $table->string('case_key', 100)->nullable()->default('luas_segitiga')->after('class_name');
            });
        }

        // Backfill existing submissions from diagram_data
        $submissions = DB::table('flowchart_submissions')->select('id', 'diagram_data')->get();
        foreach ($submissions as $sub) {
            $data = is_array($sub->diagram_data) ? $sub->diagram_data : json_decode($sub->diagram_data, true);
            $key = $data['case_key'] ?? 'luas_segitiga';
            DB::table('flowchart_submissions')->where('id', $sub->id)->update(['case_key' => $key]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('flowchart_submissions', 'case_key')) {
            Schema::table('flowchart_submissions', function (Blueprint $table) {
                $table->dropColumn('case_key');
            });
        }
    }
};
