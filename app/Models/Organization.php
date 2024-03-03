<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'organizations';
    protected $guarded = [];

    public function organization_subscription_log_mapping() {
        return $this->hasOne(OrganizationSubscriptionLogMapping::class, 'organization_id', 'id');
    }
}
