<?php

namespace App\Services;

use App\Jobs\Followup\FirstLoginJob;
use App\Jobs\Followup\IncompleteSetupReminderJob;
use App\Jobs\Followup\OnboardingJob;
use App\Jobs\Followup\RegistrationSuccessJob;
use App\Jobs\Followup\RequestTokenJob;
use App\Jobs\Followup\TokenVerifiedJob;
use App\Jobs\Followup\SuccessCelebrationJob;
use App\Models\AccessLog;
use Illuminate\Support\Facades\Log;

class AccessLogService
{
    protected array $followupConfig = [
        'request_token' => [
            'job' => RequestTokenJob::class,
            'delay_hours' => 24,
        ],
        'token_verified' => [
            'job' => TokenVerifiedJob::class,
            'delay_hours' => 24,
        ],
        'registration_success' => [
            'job' => RegistrationSuccessJob::class,
            'delay_hours' => 24,
        ],
        'email_verified_success' => [
            'job' => FirstLoginJob::class,
            'delay_hours' => 24,
        ],
        'first_login_success' => [
            'job' => OnboardingJob::class,
            'delay_hours' => 24,
        ],
        'trial_activated' => [
            'job' => IncompleteSetupReminderJob::class,
            'delay_minutes' => 30,
        ],
        'whatsapp_connected' => [
            'job' => SuccessCelebrationJob::class,
            'delay_minutes' => 10,
        ],
    ];

    public function create(array $data): AccessLog
    {
        $log = AccessLog::updateOrCreate(
            [
                'progress' => $data['progress'],
                'email' => $data['email'] ?? null,
                'company_id' => $data['company_id'] ?? null,
            ],
            $data
        );

        $this->dispatchFollowupJob($log);

        return $log;
    }

    protected function dispatchFollowupJob(AccessLog $log): void
    {
        $progress = $log->progress;

        if (! isset($this->followupConfig[$progress])) {
            return;
        }

        $config = $this->followupConfig[$progress];

        try {
            
            $jobClass = $config['job'];
            $delay = null;

            if (isset($config['delay_hours'])) {
                $delay = now()->addHours($config['delay_hours']);

            } elseif (isset($config['delay_minutes'])) {
                $delay = now()->addMinutes($config['delay_minutes']);
            }

            $job = new $jobClass($log);

            if ($delay) {
                $job->delay($delay);
            }

            dispatch($job);
            Log::info("Followup job dispatched for progress={$progress}, customer=".($log->company_id ?? $log->email ?? $log->id));

        } catch (\Exception $e) {

            Log::error('Failed to dispatch followup job: '.$e->getMessage(), [
                'progress' => $progress,
                'log_id' => $log->id,
            ]);
        }
    }
}
