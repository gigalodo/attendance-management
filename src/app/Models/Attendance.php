<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    const STATUS_BEFORE_WORK = 'before_work';
    const STATUS_WORKING     = 'working';
    const STATUS_RESTING     = 'resting';
    const STATUS_FINISHED    = 'finished';



    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_at',
        'finish_at',
        'status',
        'is_request',
        'is_approved',
        'comments',
    ];

    public function intermissions()
    {
        return $this->hasMany(Intermission::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
