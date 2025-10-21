<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;   // 👈 Add this

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'student_id',
        'role',
        'address',
        'email',
        'password',
    ];

    public function otherInfo()
    {
        return $this->hasOne(UserOtherInfo::class);
    }

    public function course()
    {
        return $this->hasOneThrough(
            Course::class,
            UserOtherInfo::class,
            'user_id', // Foreign key on user_other_infos
            'id',      // Foreign key on courses
            'id',      // Local key on users
            'course_id' // Local key on user_other_infos
        );
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
