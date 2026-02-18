<?php

namespace Modules\Verification\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AccessLogService;
use Illuminate\Http\Request;

/**
 * Legacy: OTP send/verify methods removed (registration no longer uses phone/OTP).
 * Controller kept for possible future use; model RegistrationToken still used by OtpHistory.
 */
class VerificationApiController extends Controller
{
    protected AccessLogService $accessLogService;

    public function __construct(AccessLogService $accessLogService)
    {
        $this->accessLogService = $accessLogService;
    }
}
