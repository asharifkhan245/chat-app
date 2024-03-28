<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'media',
        'status',
    ];
    use HasFactory;
}
