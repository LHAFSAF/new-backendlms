<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
  protected $fillable = [
    'name', 'email', 'password', 'role', 'api_token',
];

public function courses()
{
    return $this->belongsToMany(Course::class, 'course_user')->withTimestamps();
}

public function modules()
{
    return $this->belongsToMany(Module::class, 'module_user')->withTimestamps();
}
public function enrolledCourses()
{
    return $this->belongsToMany(Course::class, 'course_user')->withTimestamps();
}
public function completedModules()
{
    return $this->belongsToMany(Module::class, 'module_user')->withTimestamps();
}
public function completedResources()
{
    return $this->belongsToMany(\App\Models\Resource::class, 'resource_user')->withTimestamps();
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
