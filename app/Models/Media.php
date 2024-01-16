<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;
    protected $fillable =[
        'media_name',
        'user_id',
        'topic_id',
        'subtopic_id',
        'media_url',
        'media_type'
    ];
}
