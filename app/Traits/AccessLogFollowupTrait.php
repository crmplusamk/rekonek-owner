<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait AccessLogFollowupTrait
{
    /**
     * Get sales companies (companies with is_sales = true)
     */
    /**
     * Get sales company (company with is_sales = true)
     */
    protected function getSalesCompany()
    {
        return DB::connection('client')
            ->table('companies')
            ->where('is_sales', true)
            ->value('id');
    }

    /**
     * Get admin user for a company
     */
    protected function getAdminUser($companyId)
    {
        return DB::connection('client')
            ->table('users')
            ->where('company_id', $companyId)
            ->where('is_superadmin', true)
            ->where('type', 'admin')
            ->first();
    }

    /**
     * Get contact by phone number
     */
    protected function getContactByNumber($number, $companyId)
    {
        return DB::connection('client')
            ->table('contacts')
            ->where('phone', $number)
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * Create task in client database
     */
    protected function createTask($data)
    {
        try {
            $taskId = Str::uuid();
            $now = Carbon::now();

            $taskData = [
                'id' => $taskId,
                'name' => $data['name'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'medium',
                'start_date' => $now,
                'due_date' => $now,
                'type' => 'follow up',
                'is_active' => true,
                'status' => 'not started',
                'feature' => 'any',
                'is_customer' => false,
                'created_method' => 'sistem',
                'assign_to_id' => $data['assign_to_id'],
                'contact_id' => $data['contact_id'],
                'company_id' => $data['company_id'],
                'created_at' => $now,
                'created_by' => $data['created_by'],
                'updated_at' => $now,
                'is_demo_data' => false,
            ];

            DB::connection('client')
                ->table('tasks')
                ->insert($taskData);

            return $taskId;
        } catch (\Exception $e) {
            Log::error('Failed to create task: '.$e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create followup in client database
     */
    protected function createFollowup($data)
    {
        try {
            $followupId = Str::uuid();
            $now = Carbon::now();

            $followupData = [
                'id' => $followupId,
                'contact_id' => $data['contact_id'],
                'tipe' => 'tasks',
                'tid' => $data['task_id'],
                'message' => $data['message'],
                'date' => $now,
                'due_date' => $now,
                'created_by' => $data['created_by'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            DB::connection('client')
                ->table('followups')
                ->insert($followupData);

            return $followupId;
        } catch (\Exception $e) {
            Log::error('Failed to create followup: '.$e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update task with followup tid
     */
    protected function updateTaskWithFollowupId($taskId, $followupId)
    {
        DB::connection('client')
            ->table('tasks')
            ->where('id', $taskId)
            ->update(['tid' => $followupId]);
    }



    /**
     * Get followup messages for different stages
     */
    protected function getFollowupMessages()
    {
        return [
            'request_token' => 'Halo! Kami melihat Anda telah meminta token verifikasi untuk mendaftar di Retalk. Apakah Anda memerlukan bantuan untuk melanjutkan proses registrasi? 😊',
            'token_verified' => 'Selamat! Token verifikasi Anda sudah berhasil. Yuk lanjutkan proses registrasi untuk menikmati fitur-fitur Retalk yang menarik! 🚀',
            'registration_success' => 'Pendaftaran Anda sudah berhasil! Sekarang silakan cek email Anda untuk verifikasi email agar akun Anda bisa aktif sepenuhnya. 📧',
            'email_verified_success' => 'Email Anda sudah terverifikasi! Sekarang saatnya login pertama kali dan rasakan pengalaman Retalk yang luar biasa! 🎉',
            'first_login_success' => 'Selamat datang di Retalk! Ayo lengkapi proses onboarding untuk memaksimalkan penggunaan platform kami. Ada panduan menarik menunggu Anda! ✨',
            'onboarding_completed' => 'Onboarding selesai! Waktunya mengaktifkan trial gratis Anda untuk menjelajahi semua fitur premium Retalk. Jangan lewatkan kesempatan ini! 🎊',
        ];
    }

    /**
     * Process stuck customers for a specific stage
     */
    /**
     * Process single stuck customer from AccessLog
     */
    /**
     * Process single stuck customer from AccessLog
     */
    protected function processStuckCustomers($accessLog, $taskName, $taskDescription)
    {
        Log::info("Processing stuck customer: {$accessLog->email} at stage: {$accessLog->progress}");

        $salesCompanyId = $this->getSalesCompany();
        $messages = $this->getFollowupMessages();
        $processedCount = 0;

        if (!$salesCompanyId) {
            Log::error("CRITICAL: Sales Company with is_sales=true NOT FOUND. Cannot process followup for {$accessLog->email}.");
            return 0;
        }

        // Priority 1: Check by Number (most reliable for direct contact)
        $contact = null;
        if (!empty($accessLog->number)) {
            $contact = $this->getContactByNumber($accessLog->number, $salesCompanyId);
        }
        
        // Logic admin
        $admin = $this->getAdminUser($salesCompanyId);

        if (!$admin) {
            Log::error("CRITICAL: Admin user not found for Sales Company ID: {$salesCompanyId}. Cannot assign task for {$accessLog->email}.");
            return 0;
        }

        if (!$contact) {
            Log::warning("SKIPPING: Contact not found in Sales CRM for number: {$accessLog->number} (Email: {$accessLog->email}). Check if contact sync is working.");
            return 0;
        }

        if ($contact && $admin) {
            try {
                $taskId = $this->createTask([
                    'name' => $taskName,
                    'description' => $taskDescription . ' (' . ($accessLog->email ?? $accessLog->number) . ')',
                    'assign_to_id' => $admin->id,
                    'contact_id' => $contact->id,
                    'company_id' => $salesCompanyId,
                    'created_by' => $admin->id
                ]);

                $followupId = $this->createFollowup([
                    'contact_id' => $contact->id,
                    'task_id' => $taskId,
                    'message' => $messages[$accessLog->progress] ?? 'Follow up diperlukan untuk melanjutkan proses.',
                    'created_by' => $admin->id
                ]);

                $this->updateTaskWithFollowupId($taskId, $followupId);
                
                // Mark this specific log as processed
                $accessLog->update(['followup_sent' => true]);
                
                $processedCount++;
                Log::info("SUCCESS: Created followup task for {$accessLog->email} task_id={$taskId}");

            } catch (\Exception $e) {
                Log::error("EXCEPTION: Error creating task for {$accessLog->email}: " . $e->getMessage());
            }
        }

        return $processedCount;
    }
}
