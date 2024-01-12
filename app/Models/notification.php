<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class notification extends Model
{
    use HasFactory,SoftDeletes;
//'receiver_id'
    protected $fillable = ['title','content','status','event_id','time_send','sent_at','user_id'];

//user_receiver

    public function create_by()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }

    public function event()
    {
        return $this->hasOne(event::class,'id','event_id');
    }
}
