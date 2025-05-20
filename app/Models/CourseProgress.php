<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseProgress extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'completed_modules', // si tu l'utilises
        'progress',           // AJOUTE CECI
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

