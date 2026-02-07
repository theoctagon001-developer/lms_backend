<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class venue extends Model
{
   // The table name is explicitly set to 'venue'
   protected $table = 'venue';

   // Disable timestamps if not present in the table (no created_at/updated_at columns)
   public $timestamps = false;

   // Define the fillable property for mass assignment
   protected $fillable = ['venue'];
   public function timetables()
    {
        return $this->hasMany(timetable::class, 'venue_id', 'id');
    }
    public function attendances()
   {
       return $this->hasMany(attendance::class, 'venue_id', 'id');
   }
}
