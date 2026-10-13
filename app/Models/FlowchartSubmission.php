<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowchartSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'student_name',
        'class_name',
        'diagram_data',
        'score',
        'feedback',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'diagram_data' => 'array',
            'feedback' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}