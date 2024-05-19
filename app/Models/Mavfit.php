<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mavfit extends Model
{
    use HasFactory;
    
    protected $fillable = [
        "firstname",
        "lastname",
        "email",
        "phone",
        "info",
         "traffic",
         "question",
         "promo",
    ];
}
