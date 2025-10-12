<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'curriculum_id',
        'code',
        'name',
        'units',
        'semester',
        'year_level',
    ];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function prerequisites()
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_prerequisite',
            'subject_id',
            'prerequisite_id'
        );
    }
}
