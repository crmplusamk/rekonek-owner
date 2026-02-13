<?php

namespace App\Jobs\Followup;

use App\Models\AccessLog;

class RequestTokenJob extends BaseFollowupJob
{
    public function __construct(AccessLog $accessLog)
    {
        parent::__construct($accessLog);
        $this->currentStage = 'request_token';
        $this->taskName = 'Follow up Token Request';
        $this->taskDescription = 'Customer memerlukan bantuan untuk melanjutkan verifikasi token setelah request token. Silakan hubungi customer untuk memberikan panduan verifikasi token.';
        $this->nextStages = [
            'token_verified',
            'registration_success',
            'email_verified_success',
            'first_login_success',
            'onboarding_completed',
            'trial_activated',
        ];
    }
}
