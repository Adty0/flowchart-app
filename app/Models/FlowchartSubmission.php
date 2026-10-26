<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowchartSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'student_name',
        'class_name',
        'case_key',
        'diagram_data',
        'score',
        'is_remedial_open',
        'feedback',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'diagram_data' => 'array',
            'feedback' => 'array',
            'checked_at' => 'datetime',
            'is_remedial_open' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function caseStudy()
    {
        return $this->belongsTo(CaseStudy::class, 'case_key', 'case_key');
    }
}