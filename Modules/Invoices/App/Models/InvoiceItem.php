<?php

namespace Modules\Invoices\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'invoice_items';

    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'customer_id', 'id');
    }

    public function itemable()
    {
        return $this->morphTo();
    }
}
