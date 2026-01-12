<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

trait AccessLogFollowupTrait
{
    /**
     * Get sales companies (companies with is_sales = true)
     */
    protected function getSalesCompanies()
    {
        return DB::connection('client')
            ->table('companies')
            ->where('is_sales', true)
            ->pluck('id')
            ->toArray();
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
            'is_demo_data' => false
        ];

        DB::connection('client')
            ->table('tasks')
            ->insert($taskData);

        return $taskId;
    }

    /**
     * Create followup in client database
     */
    protected function createFollowup($data)
    {
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
            'updated_at' => $now
        ];

        DB::connection('client')
            ->table('followups')
            ->insert($followupData);

        return $followupId;
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
     * Check if customer has progressed beyond a certain stage in last 24 hours
     */
    protected function getStuckCustomers($currentStage, $nextStages, $hoursLimit = 24)
    {
        $cutoffTime = Carbon::now()->subHours($hoursLimit);

        // Get all customers who have reached current stage
        $customersAtStage = DB::table('access_logs')
            ->where('progress', $currentStage)
            ->where('created_at', '<=', $cutoffTime)
            ->get();

        $stuckCustomers = [];

        foreach ($customersAtStage as $customer) {
            // Check if customer has progressed to next stages
            $hasProgressed = DB::table('access_logs')
                ->where(function($query) use ($customer) {
                    if (!empty($customer->company_id)) {
                        $query->where('company_id', $customer->company_id);
                    }
                    if (!empty($customer->email)) {
                        $query->orWhere('email', $customer->email);
                    }
                })
                ->whereIn('progress', $nextStages)
                ->exists();

            if (!$hasProgressed) {
                // Check if we already created a task for this customer at this stage
                $existingTask = $this->hasExistingFollowupTask($customer, $currentStage);
                
                if (!$existingTask) {
                    $stuckCustomers[] = $customer;
                }
            }
        }

        return $stuckCustomers;
    }

    /**
     * Check if followup task already exists for this customer at this stage
     */
    protected function hasExistingFollowupTask($customer, $stage)
    {
        $salesCompanies = $this->getSalesCompanies();
        
        foreach ($salesCompanies as $companyId) {
            if (!empty($customer->number)) {
                $contact = $this->getContactByNumber($customer->number, $companyId);
                
                if ($contact) {
                    // Check if task exists for this contact with description containing the stage
                    $existingTask = DB::connection('client')
                        ->table('tasks')
                        ->where('contact_id', $contact->id)
                        ->where('type', 'follow up')
                        ->where('description', 'like', '%' . $stage . '%')
                        ->where('created_at', '>=', Carbon::now()->subDays(1))
                        ->exists();
                    
                    if ($existingTask) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Get followup messages for different stages
     */
    protected function getFollowupMessages()
    {
        return [
            'request_token' => "Halo! Kami melihat Anda telah meminta token verifikasi untuk mendaftar di Retalk. Apakah Anda memerlukan bantuan untuk melanjutkan proses registrasi? 😊",
            'token_verified' => "Selamat! Token verifikasi Anda sudah berhasil. Yuk lanjutkan proses registrasi untuk menikmati fitur-fitur Retalk yang menarik! 🚀",
            'registration_success' => "Pendaftaran Anda sudah berhasil! Sekarang silakan cek email Anda untuk verifikasi email agar akun Anda bisa aktif sepenuhnya. 📧",
            'email_verified_success' => "Email Anda sudah terverifikasi! Sekarang saatnya login pertama kali dan rasakan pengalaman Retalk yang luar biasa! 🎉",
            'first_login_success' => "Selamat datang di Retalk! Ayo lengkapi proses onboarding untuk memaksimalkan penggunaan platform kami. Ada panduan menarik menunggu Anda! ✨",
            'onboarding_completed' => "Onboarding selesai! Waktunya mengaktifkan trial gratis Anda untuk menjelajahi semua fitur premium Retalk. Jangan lewatkan kesempatan ini! 🎊"
        ];
    }

    /**
     * Process stuck customers for a specific stage
     */
    protected function processStuckCustomers($stage, $nextStages, $taskName, $taskDescription)
    {
        $stuckCustomers = $this->getStuckCustomers($stage, $nextStages);
        $salesCompanies = $this->getSalesCompanies();
        $messages = $this->getFollowupMessages();
        $processedCount = 0;

        foreach ($stuckCustomers as $customer) {
            foreach ($salesCompanies as $companyId) {
                if (!empty($customer->number)) {
                    $contact = $this->getContactByNumber($customer->number, $companyId);
                    $admin = $this->getAdminUser($companyId);

                    if ($contact && $admin) {
                        try {
                            // Create task
                            $taskId = $this->createTask([
                                'name' => $taskName,
                                'description' => $taskDescription . ' (' . $customer->email . ')',
                                'assign_to_id' => $admin->id,
                                'contact_id' => $contact->id,
                                'company_id' => $companyId,
                                'created_by' => $admin->id
                            ]);

                            // Create followup
                            $followupId = $this->createFollowup([
                                'contact_id' => $contact->id,
                                'task_id' => $taskId,
                                'message' => $messages[$stage] ?? 'Follow up diperlukan untuk melanjutkan proses.',
                                'created_by' => $admin->id
                            ]);

                            // Update task with followup ID
                            $this->updateTaskWithFollowupId($taskId, $followupId);

                            $processedCount++;
                            $this->info("Created followup task for {$customer->email} at stage {$stage}");
                            break; // Move to next customer after successful creation
                        } catch (\Exception $e) {
                            $this->error("Error creating task for {$customer->email}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $processedCount;
    }
}