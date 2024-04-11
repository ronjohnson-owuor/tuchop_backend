<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refferall extends Model
{
    use HasFactory;
    
    protected $fillable =[
        'refferer',
        'reffered',
        'award'
    ];
}
