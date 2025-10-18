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
    ];

    public function tor()
    {
        return $this->belongsTo(UploadedTor::class, 'uploaded_tor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
