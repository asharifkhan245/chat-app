<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardDetail extends Model
{

    protected  $table = "card_details";

    protected $fillable = [
        'token',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'name',
        'user_id',
        'is_primary'
    ];
    use HasFactory;
}
