<?php

namespace App\Jobs;

use App\Mail\ThankYouSubscribedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendThankYouSubscribedMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $companyId,
        public string $invoiceCode,
        public string $paymentDateLabel
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

                Mail::to($contact->email)->send(new ThankYouSubscribedMail(
                    $contact->name ?? 'Customer',
                    $this->invoiceCode,
                    $this->paymentDateLabel
                ));
            } catch (\Throwable $e) {
                Log::error('SendThankYouSubscribedMailJob failed', [
                    'company_id' => $this->companyId,
                    'invoice_code' => $this->invoiceCode,
                    'email' => $contact->email ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

