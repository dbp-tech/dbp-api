<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\HybridRelations;


class TokpedProduct extends Eloquent
{
    use HybridRelations;
    protected $connection = 'mongodb';
    protected $collection = 'tokped_products';
    protected $guarded = [];
}
