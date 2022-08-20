<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Travel extends Model
{
    use HasFactory;

    protected $table = 'travels';
    protected $dates = [
        'updated_at'
    ];
    protected $fillable = [
        'travel_id',
        'user_id',
        'traveler',
        'finished'
    ];
}
