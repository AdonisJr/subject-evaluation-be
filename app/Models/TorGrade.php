<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TorGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'tor_id',
        'subject_id',
        'credited_code',
        'title',
        'grade',
        'credits',
        'user_id'
    ];

    // Relationships
    public function tor()
    {
        return $this->belongsTo(UploadedTor::class, 'tor_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // added
    }
}
