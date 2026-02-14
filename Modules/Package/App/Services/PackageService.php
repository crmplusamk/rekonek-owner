<?php

namespace Modules\Package\App\Services;

use Modules\Addon\App\Models\Addon;
use Modules\Package\App\Models\Package;

class PackageService
{

    public function calculateTotal($subtotal)
    {
        // PPN no longer used in billing
        $tax = 0;
        $tax_amount = 0;
        $total = (int) floor($subtotal);

        return [
            "subtotal" => (int) $subtotal,
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
        // Use price from frontend if available (price per unit)
        // Otherwise use total (for backward compatibility, but total should be price * quantity)
        if (isset($item['price'])) {
            $price = $item['price'];
        } else {
            // If only total is provided, calculate price per unit
            $price = isset($item['quantity']) && $item['quantity'] > 0 
                ? $item['total'] / $item['quantity'] 
                : $item['total'];
        }
        
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
            'price' => $price,
            'subtotal' => $subtotal
        ];
    }

    public function addonItem($data, $item)
    {
        // Addon price follows termin: monthly price for month, yearly price (monthly * 12) for year
        // The price from frontend already includes the calculation (monthly or yearly)
        // Use price from frontend if available, otherwise calculate from total
        if (isset($item['price'])) {
            $price = $item['price'];
        } elseif (isset($item['total']) && isset($item['quantity']) && $item['quantity'] > 0) {
            // Calculate price per unit from total
            $price = $item['total'] / $item['quantity'];
        } else {
            // Fallback to database price (should not happen if frontend sends correctly)
            $price = $data->price;
        }
        
        $subtotal = $price * $item['quantity'];

        // Duration follows termin: 1 month or 1 year
        $duration = $item['duration'] ?? 1;
        $durationType = $item['duration_type'] ?? ($item['termin'] ?? 'month');

        return [
            'modelable_id' => $data->id,
            'modelable_type' => Addon::class,
            'duration' => $duration,
            'duration_type' => $durationType,
            'start_date' => now(),
            'end_date' => now()->add($this->termin($durationType), $duration),
            'quantity' => $item['quantity'],
            'charge' => $data->charge * $item['quantity'],
            'capital_price' => $data->price,
            'price' => $price,
            'subtotal' => $subtotal
        ];
    }
}
