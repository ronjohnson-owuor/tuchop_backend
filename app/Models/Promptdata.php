<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promptdata extends Model
{
    use HasFactory;
    protected $fillable =[
        'module_id',
        'module_owner_id',
        'submodule_id',
        'question',
        'answer',
        'follow_up_question',
        'videos'
    ];
}
