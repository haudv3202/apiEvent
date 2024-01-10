<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class notification extends Model
{
    use HasFactory;
//'receiver_id'
    protected $fillable = ['title','content','status','event_id','time_send','sent_at'];


//    public function user_receiver()
//    {
//        return $this->belongsTo(User::class, 'receiver_id','id');
//    }

    public function event()
    {
        return $this->hasOne(event::class,'id','event_id');
    }
}
