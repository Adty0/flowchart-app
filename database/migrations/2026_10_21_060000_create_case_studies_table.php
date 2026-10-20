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
        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->string('case_key', 64)->unique();
            $table->string('title', 150);
            $table->string('icon', 30)->default('📐');
            $table->string('badge', 60)->default('Alur Sekuensial');
            $table->string('formula', 255)->nullable();
            $table->text('description');
            $table->string('flow_type', 30)->default('sequential'); // sequential, branching, loop
            $table->text('expected_inputs')->nullable(); // e.g. "alas, tinggi, a, t"
            $table->text('expected_process')->nullable(); // e.g. "0.5 * alas * tinggi"
            $table->text('expected_decision')->nullable(); // e.g. "nilai >= 75"
            $table->string('expected_yes_output', 100)->nullable(); // e.g. "lulus"
            $table->string('expected_no_output', 100)->nullable(); // e.g. "remedial"
            $table->text('expected_outputs')->nullable(); // e.g. "luas, hasil"
            $table->json('reference_flow')->nullable(); // standard diagram nodes & edges
            $table->json('scoring_rubric')->nullable(); // custom score weights
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_studies');
    }
};
