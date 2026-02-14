<?php

namespace App\Jobs\Followup;

use App\Models\AccessLog;

class RegistrationSuccessJob extends BaseFollowupJob
{
    public function __construct(AccessLog $accessLog)
    {
        parent::__construct($accessLog);
        $this->currentStage = 'registration_success';
        $this->taskName = 'Follow up Verifikasi Email';
        $this->taskDescription = 'Customer sudah berhasil registrasi namun belum melakukan verifikasi email. Silakan hubungi customer untuk memandu proses verifikasi email.';
        $this->nextStages = [
            'email_verified_success',
            'first_login_success',
            'onboarding_completed',
            'trial_activated',
        ];
    }
}
