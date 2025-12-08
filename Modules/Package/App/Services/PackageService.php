<?php

namespace Modules\Package\App\Services;

use Modules\Addon\App\Models\Addon;
use Modules\Package\App\Models\Package;

class PackageService
{

    public function calculateTotal($subtotal)
    {
        $service_fee = 2000;
        $admin_fee = 10000;
        $tax = 11;

        $tax_amount = ($subtotal + $service_fee + $admin_fee) * $tax / 100;
        $total = $subtotal + $service_fee + $admin_fee + $tax_amount;

        return [
            "subtotal" => $subtotal,
            "admin_fee" => $admin_fee,
            "service_fee" => $service_fee,
            "tax" => $tax,
            "tax_amount" => $tax_amount,
            "total" => $total,
        ];
    }

    public function termin($termin)
    {
        return $termin.'s';
    }

    public function packageItem($data, $item)
    {
        $price = $item['total'];
        $subtotal = $price * $item['quantity'];

        return [
            'modelable_id' => $data->id,
            'modelable_type' => Package::class,
            'duration' => $item['duration'],
            'duration_type' => $item['duration_type'],
            'start_date' => now(),
            'end_date' => now()->add($this->termin($item['termin']), $item['duration']),
            'quantity' => $item['quantity'],
            'charge' => null,
            'capital_price' => $data->price,
            'price' => $item['total'],
            'subtotal' => $subtotal
        ];
    }

    public function addonItem($data, $item)
    {
        $price = $data->price * $item['duration'];
        $subtotal = $price * $item['quantity'] ;
        $additionalTotal = isset($item['additionalDay']) && isset($item['additionalCharge']) ? $item['additionalDay'] * $item['additionalCharge'] * $data->price : 0;

        return [
            'modelable_id' => $data->id,
            'modelable_type' => Addon::class,
            'duration' => $item['termin_duration'],
            'duration_type' => $item['termin'],
            'start_date' => now(),
            'end_date' => now()->add($this->termin($item['termin']), $item['termin_duration']),
            'quantity' => $item['quantity'],
            'charge' => $data->charge * $item['quantity'],
            'capital_price' => $data->price,
            'price' => $price,
            'subtotal' => $subtotal + $additionalTotal,
            'additional_duration' => isset($item['additionalDay']) ? $item['additionalDay'] : null,
            'additional_duration_type' => isset($item['additionalDay']) ? 'day' : null,
            'additional_charge' => isset($item['additionalCharge']) ? $item['additionalCharge'] : null,
            'additional_total' => $additionalTotal ?? null
        ];
    }
}
