<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionFeature extends Model
{
    use HasFactory;

    protected $table = 'subscription_feature';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];
}
