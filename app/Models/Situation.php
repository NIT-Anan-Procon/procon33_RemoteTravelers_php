<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Situation extends Model
{
    use HasFactory;

    protected $table = 'situations';
    protected $dates = [
        'created_at',
    ];
    protected $fillable = [
        'situation_id',
        'situation',
        'travel_id',
    ];
}
