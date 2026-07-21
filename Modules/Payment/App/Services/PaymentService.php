<?php

namespace Modules\Payment\App\Services;

use Illuminate\Support\Str;
use Modules\Payment\App\Models\Payment;

/**
 * Operasi payment (service pattern). Dipakai lintas module oleh
 * Checkout (buat/cari/update payment Midtrans).
 */
class PaymentService
{
    public function create($request)
    {
        $data = Payment::create([
            'invoice_id' => $request['invoice_id'],
            'order_id' => $request['order_id'] ?? null,
            'date' => $request['date'] ?? null,
            'due_date' => $request['due_date'] ?? null,
            'method' => $request['method'] ?? null,
            'total' => $request['total'],
            'is_status' => $request['is_status'],
            'note' => $request['note'] ?? null,
            'metadata' => $request['metadata'] ?? null,
            'snap_token' => $request['snap_token'] ?? null
        ]);

        return $data;
    }

    public function findByOrderId($id)
    {
        $data = Payment::where([
            'order_id' => $id,
        ])->first();

        return $data;
    }

    public function findById($id)
    {
        return Payment::with('invoice')->find($id);
    }

    public function update($payment, $data)
    {
        $data = $payment->update($data);
        return $data;
    }

    public function updateOrCreate($param, $request)
    {
        Payment::updateOrCreate($param, $request);
    }
}
