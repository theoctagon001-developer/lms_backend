<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class role extends Model
{
    protected $table = 'role';
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $fillable = ['type'];
    public function users()
    {
        return $this->belongsToMany(user::class, 'user_role', 'role_id', 'user_id');
    }
}
