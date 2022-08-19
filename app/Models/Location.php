<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';
    protected $dates = [
        'created_at'
    ];
    protected $fillable = [
        'user_id',
        'location'
    ];
}
