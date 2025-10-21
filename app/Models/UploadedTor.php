<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadedTor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'curriculum_id', // ✅ newly added
        'file_path',
        'public_id',
        'status',
        'remarks',
    ];

    /**
     * Relationship: the user who uploaded the TOR
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: the curriculum this TOR is associated with
     */
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }


    // TOR Grades
    public function torGrades()
    {
        return $this->hasMany(TorGrade::class, 'tor_id');
    }

    // Advising
    public function advising()
    {
        return $this->hasMany(Advising::class, 'uploaded_tor_id');
    }
}
