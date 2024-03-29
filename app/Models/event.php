<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class event extends Model
{
    use HasFactory;
    protected $fillable = ['name','location','contact','content','status','banner','description','user_id','start_time','end_time','area_id'];
//,'description'
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications()
    {
        return $this->hasMany(notification::class);
    }

    public function attendances()
    {
        return $this->hasMany(atendance::class);
    }
    public function feedback()
    {
        return $this->hasMany(feedback::class);
    }

    public function eventKeywords(){
        return $this->hasMany(events_keywords::class);
    }

    public function area(){
        return $this->hasOne(area::class,'id','area_id');
    }

    public function keywords()
    {
        return $this->hasManyThrough(
            keywords::class,
            events_keywords::class,
            'event_id', // Khóa ngoại của bảng trung gian
            'id', // Khóa chính của bảng keywords
            'id', // Khóa chính của bảng events
            'keywords_id' // Khóa ngoại của bảng keywords
        );
    }
}
