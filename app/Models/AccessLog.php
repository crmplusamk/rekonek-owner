<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory, UuidTrait;

    // Progress stages
    const PROGRESS_REQUEST_TOKEN = 'request_token';
    const PROGRESS_TOKEN_VERIFIED = 'token_verified';
    const PROGRESS_REGISTRATION_SUCCESS = 'registration_success';
    const PROGRESS_EMAIL_VERIFIED = 'email_verified_success';
    const PROGRESS_FIRST_LOGIN = 'first_login_success';
    const PROGRESS_ONBOARDING_COMPLETED = 'onboarding_completed';
    const PROGRESS_TRIAL_ACTIVATED = 'trial_activated';
    const PROGRESS_WA_CONNECTED = 'whatsapp_connected';
    const PROGRESS_INCOMPLETE_SETUP_REMINDER_SENT = 'incomplete_setup_reminder_sent';

    protected $table = 'access_logs';

    protected $guarded = [];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'followup_sent' => 'boolean',
    ];
}

