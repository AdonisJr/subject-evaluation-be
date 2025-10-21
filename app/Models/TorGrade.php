<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TorGrade extends Model
{
    use HasFactory;

    protected $casts = [
        'is_credited' => 'boolean',
        'percent_grade' => 'float',
        'credits' => 'integer',
    ];

    protected $fillable = [
        'tor_id',
        'user_id',
        'extracted_code',
        'credited_id',
        'credited_code',
        'title',
        'grade' => 'float',
    ];

    // Relationships
    public function tor()
    {
        return $this->belongsTo(UploadedTor::class, 'tor_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'credited_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // added
    }
}
