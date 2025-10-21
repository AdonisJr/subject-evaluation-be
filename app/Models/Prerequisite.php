<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prerequisite extends Model
{
    //
    use HasFactory;

    protected $table = 'subject_prerequisite'; // 👈 fix the mismatch

    protected $fillable = [
        'subject_id',
        'prerequisite_id'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function required()
    {
        return $this->belongsTo(Subject::class, 'prerequisite_id');
    }
}
