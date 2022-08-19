<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';
    protected $dates = [
        'created_at'
    ];
    protected $fillable = [
        'report_id',
        'travel_id',
        'image',
        'comment',
        'excitement',
        'location'
    ];
}
