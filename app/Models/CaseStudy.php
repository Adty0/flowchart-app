<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_key',
        'title',
        'icon',
        'badge',
        'formula',
        'description',
        'flow_type',
        'expected_inputs',
        'expected_process',
        'expected_decision',
        'expected_yes_output',
        'expected_no_output',
        'expected_outputs',
        'reference_flow',
        'scoring_rubric',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'reference_flow' => 'array',
        'scoring_rubric' => 'array',
        'is_default'     => 'boolean',
        'is_active'      => 'boolean',
    ];

    /**
     * Guru yang membuat studi kasus ini.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Submissions yang telah dikerjakan siswa untuk studi kasus ini.
     */
    public function submissions()
    {
        return $this->hasMany(FlowchartSubmission::class, 'case_key', 'case_key');
    }
}
