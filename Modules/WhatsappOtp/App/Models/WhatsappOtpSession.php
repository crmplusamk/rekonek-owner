<?php

namespace Modules\WhatsappOtp\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Whatsapp\Database\factories\WhatsappSessionFactory;

class WhatsappOtpSession extends Model
{
    use HasFactory, UuidTrait;

    protected $table = 'whatsapp_otp_sessions';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

}
