<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class atendance extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','event_id','rate'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
