<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advising extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_tor_id',
        'user_id',
        'semester',
        'year_level',
        'subject_code',
        'subject_title',
        'subject_id',
        'school_year'
    ];

    public function tor()
    {
        return $this->belongsTo(UploadedTor::class, 'tor_id');
    }

    public function tor_grade()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function grade()
    {
        return $this->hasOne(Grade::class);
    }
}
