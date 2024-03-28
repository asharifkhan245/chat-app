<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDetails extends Model
{

    protected $table = 'payment_details';

    protected $fillable = [
        'card_number',
        'cvv',
        'expiration_month',
        'expiration_year',
        'user_id',
        'name',
        'email'
    ];
    use HasFactory;
}
