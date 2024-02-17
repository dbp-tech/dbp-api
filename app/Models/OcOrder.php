<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OcOrder extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'oc_orders';
    protected $guarded = [];

    public function oc_store()
    {
        return $this->belongsTo(OcStore::class, 'store_id', 'store_id');
    }
}
