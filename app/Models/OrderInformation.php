<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderInformation extends Model
{
    use HasFactory;
    
    protected $table = 'order_informations';
    protected $guarded = [];
}
