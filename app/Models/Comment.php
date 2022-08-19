<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $table = 'comments';
    protected $dates = [
        'created_at'
    ];
    protected $fillable = [
        'travel_id',
        'user_id',
        'comment',
    ];
}
