<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    //Vai tratar o items como array, criando um cast
    protected $casts=[
        'items'=> 'array'
    ];

    protected $dates =['date'];
}
