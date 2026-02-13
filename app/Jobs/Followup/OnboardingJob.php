<?php

namespace App\Jobs\Followup;

use App\Models\AccessLog;

class OnboardingJob extends BaseFollowupJob
{
    public function __construct(AccessLog $accessLog)
    {
        parent::__construct($accessLog);
        $this->currentStage = 'first_login_success';
        $this->taskName = 'Follow up Onboarding';
        $this->taskDescription = 'Customer sudah berhasil login namun belum menyelesaikan onboarding. Silakan hubungi customer untuk memandu proses onboarding.';
        $this->nextStages = [
            'onboarding_completed',
            'trial_activated',
        ];
    }
}
