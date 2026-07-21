<?php

namespace Modules\Checkout\App\Exceptions;

use Exception;

/**
 * Error domain checkout dengan kode & HTTP status standar, dipetakan ke envelope
 * response { success:false, code, message } oleh CheckoutApiController::store.
 */
class CheckoutException extends Exception
{
    public function __construct(
        private string $errorCode,
        string $message,
        private int $status,
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public static function customerNotFound(): self
    {
        return new self('CUSTOMER_NOT_FOUND', 'Data customer tidak ditemukan untuk company ini.', 404);
    }

    public static function itemNotFound(string $id): self
    {
        return new self('ITEM_NOT_FOUND', "Item dengan id {$id} tidak ditemukan.", 404);
    }

    public static function subscriptionRequired(): self
    {
        return new self('SUBSCRIPTION_REQUIRED', 'Anda harus memiliki langganan aktif untuk membeli addon. Silakan berlangganan terlebih dahulu.', 403);
    }
}
