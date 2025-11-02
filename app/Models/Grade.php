<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'credited_id',
        'tor_grade_id',
        'advising_id',
        'type',
        'status',
        'year_level',
        'grade',
        'grade_percent',
        'school_year',
    ];

    /* ---------------- Relationships ---------------- */

    // The user this grade belongs to
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // The main subject (for both credited & advising)
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // The subject that was credited (linked to curriculum subject)
    public function credited()
    {
        return $this->belongsTo(Subject::class, 'credited_id');
    }

    // The TOR subject that matched this grade (from Uploaded TORs)
    public function torGrade()
    {
        return $this->belongsTo(Subject::class, 'tor_grade_id');
    }

    // The advising record if this was assigned during enrollment/advising
    public function advising()
    {
        return $this->belongsTo(Advising::class);
    }
}
