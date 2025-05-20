<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
protected $fillable = ['title', 'description', 'category', 'teacher_id', 'image'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }
    public function enrolledStudents()
{
    return $this->belongsToMany(User::class, 'course_user')->withTimestamps();
}

}
