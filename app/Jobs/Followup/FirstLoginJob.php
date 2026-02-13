<?php

namespace App\Jobs\Followup;

use App\Models\AccessLog;

class FirstLoginJob extends BaseFollowupJob
{
    public function __construct(AccessLog $accessLog)
    {
        parent::__construct($accessLog);
        $this->currentStage = 'email_verified_success';
        $this->taskName = 'Follow up Login Pertama';
        $this->taskDescription = 'Email customer sudah terverifikasi namun belum melakukan login pertama. Silakan hubungi customer untuk memandu proses login.';
        $this->nextStages = [
            'first_login_success',
            'onboarding_completed',
            'trial_activated',
        ];
    }
}
