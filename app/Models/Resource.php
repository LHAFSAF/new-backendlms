<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = ['title', 'type', 'content', 'module_id'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
