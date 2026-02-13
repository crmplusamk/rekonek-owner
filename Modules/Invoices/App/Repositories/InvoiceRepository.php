<?php

namespace Modules\Invoices\App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Invoices\App\Models\Invoice;
use Modules\Customer\App\Models\Customer;

class InvoiceRepository
{

    public function create($request)
    {
        $data = Invoice::create([
            'code' => Str::upper(Str::random(5)),
            'customer_id' => $request['customer_id'],
            'customer_name' => $request['customer_name'],
            'customer_email' => $request['customer_email'],
            'customer_phone' => $request['customer_phone'],
            'customer_address' => $request['customer_address'],
            'date' => $request['date'],
            'due_date' => $request['due_date'],
            'tax' => $request['tax'],
            'tax_amount' => $request['tax_amount'],
            'discount_percentage' => $request['discount_percentage'],
            'discount_percentage_amount' => $request['discount_percentage_amount'],
            'discount_amount' => $request['discount_amount'],
            'admin_fee' => $request['admin_fee'],
            'service_fee' => $request['service_fee'],
            'subtotal' => $request['subtotal'],
            'total' => $request['total'],
            'type' => $request['type'] ?? 'new',
            'is_status' => $request['is_status'],
            'is_paid' => $request['is_paid'],
            'payment_date' => $request['payment_date'],
            'payment_method' => $request['payment_method'],
            'payment_total' => $request['payment_total'],
            'company_id' => $request['company_id'],
        ]);

         /**
         * Assign invoice items
         */
        foreach($request['items'] as $item)
        {
            $start_date = $item['start_date'] ?? now();
            if (empty($item['end_date']))
            {
                $end_date = Carbon::parse($start_date);
                switch ($item['duration_type']) {
                    case 'day':
                        $end_date = $end_date->addDays($item['duration']);
                        break;
                    case 'month':
                        $end_date = $end_date->addMonths($item['duration']);
                        break;
                    case 'year':
                        $end_date = $end_date->addYears($item['duration']);
                        break;
                    default:
                        $end_date = $end_date;
                        break;
                }
            } else {

                $end_date = $item['end_date'];
            }

            $data->items()->create([
                'itemable_id' => $item['modelable_id'],
                'itemable_type' => $item['modelable_type'],
                'duration' => $item['duration'],
                'duration_type' => $item['duration_type'],
                'start_date' => $start_date,
                'end_date' => $end_date,
                'quantity' => $item['quantity'],
                'charge' => $item['charge'] ?? 1,
                'capital_price' => $item['capital_price'] ?? 0,
                'price' => $item['price'] ?? 0,
                'subtotal' => $item['subtotal'],
                'additional_duration' => $item['additional_duration'] ?? null,
                'additional_duration_type' => $item['additional_duration_type'] ?? null,
                'additional_total' => $item['additional_total'] ?? null,
                'additional_charge' => $item['additional_charge'] ?? null,
            ]);
        }

        return $data;
    }

    function getByCompanyId($request)
    {
        $data = Invoice::where("company_id", $request['company_id'])
            ->with('items.itemable', 'payments')
            ->orderBy("created_at", "desc")
            ->get();

        return $data;
    }

    function findById($id)
    {
        $data = Invoice::with([
                'items.itemable',
                'payments' => function($data) {
                    $data->orderBy('date', 'desc');
                },
                'activePayment'
            ])
            ->find($id);

        return $data;
    }

    function findUnpaidById($id)
    {
        $data = Invoice::with([
                'items.itemable',
                'payments' => function($data) {
                    $data->orderBy('date', 'desc');
                },
                'activePayment'
            ])
            ->where([
                "is_status" => 1,
                "is_paid" => false,
            ])
            ->find($id);

        return $data;
    }

    function findByCode($id)
    {
        $data = Invoice::with([
                "payments",
                "items"
            ])
            ->where([
                "code" => $id,
                "is_paid" => 0,
                "is_status" => 1
            ])
            ->first();

        return $data;
    }

    function update($invoice, $data)
    {
        $invoice->update($data);
    }
}
