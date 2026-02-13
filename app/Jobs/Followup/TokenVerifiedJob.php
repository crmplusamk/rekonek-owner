<?php

namespace App\Jobs\Followup;

use App\Models\AccessLog;

class TokenVerifiedJob extends BaseFollowupJob
{
    public function __construct(AccessLog $accessLog)
    {
        parent::__construct($accessLog);
        $this->currentStage = 'token_verified';
        $this->taskName = 'Follow up Registrasi';
        $this->taskDescription = 'Token customer sudah terverifikasi namun belum melanjutkan ke registrasi. Silakan hubungi customer untuk memandu proses registrasi akun.';
        $this->nextStages = [
            'registration_success',
            'email_verified_success',
            'first_login_success',
            'onboarding_completed',
            'trial_activated',
        ];
    }
}
