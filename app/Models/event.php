<?php

namespace App\Models;

use App\Casts\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class event extends Model
{
    use HasFactory;
    protected $fillable = ['name','location','contact','description','content','status','banner','user_id','start_time','end_time'];

    protected $casts = [
        'banner' => Image::class,
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
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
