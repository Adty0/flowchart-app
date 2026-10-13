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
    Schema::create('flowchart_submissions', function (Blueprint $table) {
        $table->id();
        $table->string('student_name')->nullable();
        $table->json('diagram_data');
        $table->unsignedTinyInteger('score')->default(0);
        $table->json('feedback')->nullable();
        $table->timestamp('checked_at')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('flowchart_submissions');
}
};

