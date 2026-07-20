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
            "limit" => 24,
            "expiresAt" => now()->addHours(24),
        ];
    }

    public function generateSnapToken($invoice, $orderId, $time, $timelimit)
    {
        $params = [
            "transaction_details" => [
                "order_id" => $orderId,
                "gross_amount" => (int) $invoice->total
            ],
            "item_details" => [
                [
                    "id" => $invoice->code,
                    "name" => "Pembayaran Invoice " . $invoice->code,
                    "quantity" => 1,
                    "price" => (int) $invoice->total,
                ]
            ],
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

    /**
     * Charge QRIS langsung via Core API (tanpa Snap) — instruksi pembayaran (QR) tampil
     * inline di aplikasi. `order_id` HARUS memakai format `setOrder()` (`{code}-{ts}`) agar
     * webhook `paymentCallback` bisa memetakan balik ke invoice via `explode('-')[0]`.
     *
     * @return array{order_id:string, transaction_id:?string, transaction_status:?string, qr_url:?string, qr_string:?string, expiry_time:?string, raw:object}
     */
    public function chargeQris($invoice, $orderId, string $unit = 'minute', int $expiryDuration = 15): array
    {
        $params = [
            "payment_type" => "qris",
            "transaction_details" => [
                "order_id" => $orderId,
                "gross_amount" => (int) $invoice->total,
            ],
            "item_details" => [
                [
                    "id" => $invoice->code,
                    "name" => "Pembayaran Invoice " . $invoice->code,
                    "quantity" => 1,
                    "price" => (int) $invoice->total,
                ]
            ],
            "customer_details" => [
                "first_name" => $invoice->customer_name,
                "email" => $invoice->customer_email,
                "phone" => $invoice->customer_phone,
            ],
            "custom_expiry" => [
                "expiry_duration" => $expiryDuration,
                "unit" => $unit,
            ],
        ];

        $response = \Midtrans\CoreApi::charge($params);

        // URL gambar QR = action bernama 'generate-qr-code'.
        $qrUrl = null;
        if (isset($response->actions) && is_array($response->actions)) {
            foreach ($response->actions as $action) {
                if (($action->name ?? null) === 'generate-qr-code') {
                    $qrUrl = $action->url ?? null;
                    break;
                }
            }
        }

        return [
            "order_id" => $response->order_id ?? $orderId,
            "transaction_id" => $response->transaction_id ?? null,
            "transaction_status" => $response->transaction_status ?? null,
            "qr_url" => $qrUrl,
            "qr_string" => $response->qr_string ?? null,
            "expiry_time" => $response->expiry_time ?? null,
            "raw" => $response,
        ];
    }

    /**
     * Charge Virtual Account (bank_transfer) via Core API — nomor VA tampil inline di aplikasi.
     * `order_id` HARUS memakai format `setOrder()` (`{code}-{ts}`) agar webhook bisa memetakan invoice.
     * Bank didukung: bca, bni, bri (via `bank_transfer.bank` → `va_numbers`), permata (→ `permata_va_number`).
     *
     * @return array{order_id:string, transaction_status:?string, bank:string, va_number:?string, expiry_time:?string, raw:object}
     */
    public function chargeBankTransfer($invoice, $orderId, string $bank, string $unit = 'minute', int $expiryDuration = 1440): array
    {
        $params = [
            "payment_type" => "bank_transfer",
            "transaction_details" => [
                "order_id" => $orderId,
                "gross_amount" => (int) $invoice->total,
            ],
            "item_details" => [
                [
                    "id" => $invoice->code,
                    "name" => "Pembayaran Invoice " . $invoice->code,
                    "quantity" => 1,
                    "price" => (int) $invoice->total,
                ]
            ],
            "customer_details" => [
                "first_name" => $invoice->customer_name,
                "email" => $invoice->customer_email,
                "phone" => $invoice->customer_phone,
            ],
            "bank_transfer" => [
                "bank" => $bank,
            ],
            "custom_expiry" => [
                "expiry_duration" => $expiryDuration,
                "unit" => $unit,
            ],
        ];

        $response = \Midtrans\CoreApi::charge($params);

        $vaNumber = null;
        $vaBank = $bank;
        if (isset($response->va_numbers) && is_array($response->va_numbers) && count($response->va_numbers) > 0) {
            $vaNumber = $response->va_numbers[0]->va_number ?? null;
            $vaBank = $response->va_numbers[0]->bank ?? $bank;
        } elseif (isset($response->permata_va_number)) {
            $vaNumber = $response->permata_va_number;
            $vaBank = 'permata';
        }

        return [
            "order_id" => $response->order_id ?? $orderId,
            "transaction_status" => $response->transaction_status ?? null,
            "bank" => $vaBank,
            "va_number" => $vaNumber,
            "expiry_time" => $response->expiry_time ?? null,
            "raw" => $response,
        ];
    }

    public function validateSignature($params)
    {
        $serverKey = config("midtrans.serverKey");
        $signatureKey = $params['signature_key'] ?? null;
        if (empty($signatureKey)) return false;

        $hashed = hash("sha512", $params['order_id'].$params['status_code'].$params['gross_amount'].$serverKey);

        // Timing-safe compare. NOTE: precedence bug lama `!$hashed == $signature_key`
        // di-parse sebagai `(!$hashed) == $signature_key` sehingga signature tidak
        // pernah benar-benar diverifikasi (webhook bisa dipalsukan).
        return hash_equals($hashed, $signatureKey);
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
        // PPN no longer used in billing - skip tax fee
        if ($invoice->tax_amount > 0) {
            $items[] = [
                "id" => "TX01",
                "name" => "PPN " . $invoice->tax . "%",
                "price" => $invoice->tax_amount,
                "quantity" => 1,
            ];
        }

        return $items;
    }

    public function discount($items, $invoice)
    {
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

    public function cancelOrder(string $orderId)
    {
        $transaction = new \Midtrans\Transaction;

        return $transaction->cancel($orderId);
    }
}
