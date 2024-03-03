<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionModuleMapping extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'subscription_module_mapping';
    protected $guarded = [];

    public function system_module() {
        return $this->hasOne(SystemModule::class, 'id', 'module_id');
    }
}
