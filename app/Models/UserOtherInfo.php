<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtherInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'gender',
        'category',
        'dob',
        'mobile',
        'address',
        'blood_type',
        'eye_color',
        'height',
        'weight',
        'religion',
        'status',
        'permanent_address',
        'current_address',
        'mother',
        'father',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
