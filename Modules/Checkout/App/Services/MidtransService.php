<?php

namespace Modules\Checkout\App\Services;

use App\Helpers\Loging\Log;
use Modules\Logs\App\Services\LogService;

class MidtransService
{
    public function __construct()
    {
        \Midtrans\Config::$serverKey = config("midtrans.serverKey");
        \Midtrans\Config::$isProduction = config("midtrans.isProduction");
        \Midtrans\Config::$isSanitized = config("midtrans.isSanitized");
        \Midtrans\Config::$is3ds = config("midtrans.is3ds");
    }

    public function setOrder($code)
    {
        return (object) [
            "orderId" => $code."-".now()->timestamp,
            "time" => 'hours',
            "limit" => 24
        ];
    }

    public function generateSnapToken($invoice, $orderId, $time, $timelimit)
    {
        $items = [];
        $items = $this->invoiceItems($items, $invoice);
        $items = $this->fee($items, $invoice);
        $items = $this->discount($items, $invoice);

        $params = [
            "transaction_details" => [
                "order_id" => $orderId,
                "gross_amount" => $invoice->total
            ],
            "item_details" => $items,
            "customer_details" => [
                "first_name" => $invoice->customer_name,
                "email" => $invoice->customer_email,
                "phone" => $invoice->customer_phone
            ],
            "expiry" => [
                "unit" => $time,
                "duration" => $timelimit
            ],
            "callbacks" => [
                "finish" => env("CRM_CLIENT_HOST")."/billing/package",
                "error" => env("CRM_CLIENT_HOST")."/billing/package",
                "unfinish" => env("CRM_CLIENT_HOST")."/billing/package",
            ]
        ];

        $token = \Midtrans\Snap::getSnapToken($params);
        return $token;
    }

    public function validateSignature($params)
    {
        $serverKey = config("midtrans.serverKey");
        $hashed = hash("sha512", $params['order_id'].$params['status_code'].$params['gross_amount'].$serverKey);
        if (!$hashed == $params['signature_key']) return false;

        return true;
    }

    public function invoiceItems($items, $invoice)
    {
        foreach ($invoice->items as $item)
        {
            $items[] = [
                "id" => $item->itemable->id,
                "name" => $item->itemable->name,
                "quantity" => $item->quantity,
                "price" => $item->price,
            ];

            if ($item->additional_duration) {
                $items[] = [
                    "id" => $item->itemable->id,
                    "name" => "Perpanjangan {$item->itemable->name}",
                    "quantity" => 1,
                    "price" => $item->additional_total,
                ];
            }
        }

        return $items;
    }

    public function fee($items, $invoice)
    {
        $fees = [
            [
                "id" => "TX01",
                "name" => "PPN ".$invoice->tax."%",
                "price" => $invoice->tax_amount,
                "quantity" => 1,
            ]
        ];

        foreach ($fees as $fee) {
            $items[] = $fee;
        }

        return $items;
    }

    public function discount($items, $invoice)
    {
        // Add referral discount as negative item if exists
        if ($invoice->discount_amount > 0 && $invoice->referral_code) {
            $items[] = [
                "id" => "DISC01",
                "name" => "Diskon Referral ({$invoice->referral_code})",
                "price" => -$invoice->discount_amount, // Negative price for discount
                "quantity" => 1,
            ];
        }

        return $items;
    }

    public function statusPayment($orderId)
    {
        $transaction = new \Midtrans\Transaction;
        return $transaction->status($orderId);
    }

    public function cancelPayment($payments)
    {
        $transaction = new \Midtrans\Transaction;

        foreach($payments as $payment)
        {
            try {

                $transaction->cancel($payment->order_id);

            } catch (\Throwable $th) {

                $payment->update([
                    "is_status" => 3,
                ]);

                LogService::create([
                    'fid' => $payment->id,
                    'category' => 'order',
                    'title' => 'Status Pembayaran',
                    'note' => "Pembayaran dengan order id {$payment->order_id} telah dibatalkan",
                    'company_id' => $payment->invoice->company_id
                ]);

                continue;
            }
        }
    }
}
