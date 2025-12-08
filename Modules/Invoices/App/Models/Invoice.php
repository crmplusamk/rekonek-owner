<?php

namespace Modules\Invoices\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Customer\App\Models\Customer;
use Modules\Payment\App\Models\Payment;

class Invoice extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'invoices';

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'id');
    }

    public function activePayment()
    {
        return $this->hasOne(Payment::class, 'invoice_id', 'id')
            ->where('due_date', '>=', now())
            ->whereIn('is_status', [0, 1])
            ->orderBy('date', 'desc');
    }
}
