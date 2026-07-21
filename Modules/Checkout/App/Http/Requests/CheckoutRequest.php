<?php

namespace Modules\Checkout\App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Kontrak request terstandar untuk POST /api/v1/checkout.
 * Melayani tiga kondisi lewat isi items[]: paket saja, addon saja, atau paket+addon.
 * Invoice type & gate "wajib langganan aktif" diturunkan server dari komposisi items
 * (lihat CheckoutService::process), bukan dari field client.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'string'],
            'user_id' => ['nullable', 'string'],

            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'string', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:50'],
            'customer.address' => ['nullable', 'string', 'max:500'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:package,addon'],
            'items.*.id' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.termin' => ['required', 'in:month,year'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.duration' => ['nullable', 'integer', 'min:1'],
            'items.*.duration_type' => ['nullable', 'in:day,month,year'],

            'promo_code' => ['nullable', 'string'],
            'is_renew' => ['nullable', 'boolean'],

            // Channel pembayaran: 'snap' (default, redirect Snap), 'qris', atau 'va' (Core API inline).
            'payment_channel' => ['nullable', 'in:snap,qris,va'],
            // Bank VA (wajib bila channel = va).
            'bank' => ['nullable', 'in:bca,bni,bri,permata'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'customer.name' => 'nama pembeli',
            'customer.email' => 'email pembeli',
            'customer.phone' => 'nomor pembeli',
            'items' => 'item checkout',
            'items.*.type' => 'tipe item',
            'items.*.id' => 'id item',
            'items.*.quantity' => 'jumlah',
            'items.*.termin' => 'termin',
            'items.*.price' => 'harga',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'code' => 'VALIDATION_ERROR',
            'message' => 'Data checkout tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
