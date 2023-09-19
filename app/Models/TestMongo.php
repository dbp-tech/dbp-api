<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\HybridRelations;


class TestMongo extends Eloquent
{
    use HybridRelations;
    protected $connection = 'mongodb';
    protected $collection = 'test_mongo';
    protected $guarded = [];
}
