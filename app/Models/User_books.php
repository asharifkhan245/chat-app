<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_books extends Model
{

    protected $table = 'user_books';
    protected $fillable = [
        'user_id',
        'book_id',
        'status',
    ];
    use HasFactory;
}
