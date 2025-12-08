<?php

namespace Modules\Customer\App\Models;

use App\Scopes\CustomerScope;
use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Invoices\App\Models\Invoice;

class Customer extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'contacts';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected static function booted()
	{
		static::addGlobalScope(new CustomerScope);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id', 'id');
    }
}
