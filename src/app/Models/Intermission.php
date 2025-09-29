<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'start_at',
        'finish_at',
    ];
}
