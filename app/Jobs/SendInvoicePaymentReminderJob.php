<?php

namespace App\Jobs;

use App\Mail\InvoicePaymentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoicePaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public string $companyId,
        public string $invoiceCode,
        public string $invoiceTotal,
        public string $expiredDate,
        public string $dueDate,
    ) {}

    public function handle(): void
    {
        $contacts = DB::table('contacts')
            ->where('company_id', $this->companyId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select(['email', 'name'])
            ->get();

        if ($contacts->isEmpty()) {
            return;
        }

        foreach ($contacts as $contact) {
            try {
                if (empty($contact->email)) {
                    continue;
                }

                Mail::to($contact->email)->send(new InvoicePaymentReminder(
                    $contact->name ?? 'Customer',
                    $this->invoiceCode,
                    $this->invoiceTotal,
                    $this->expiredDate,
                    $this->dueDate,
                ));
            } catch (\Throwable $e) {
                Log::error('SendInvoicePaymentReminderJob failed', [
                    'company_id' => $this->companyId,
                    'invoice_code' => $this->invoiceCode,
                    'email' => $contact->email ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
