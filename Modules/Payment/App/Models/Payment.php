<?php

namespace Modules\Payment\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Invoices\App\Models\Invoice;

class Payment extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'payments';

    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}
