<?php

namespace Modules\SettingAffiliator\App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\App\Models\User;

class AffiliatorConfig extends Model
{
    use UuidTrait;

    protected $table = 'affiliator_configs';

    protected $guarded = [];

    protected $casts = [
        'commission_value_registrasi' => 'decimal:2',
        'commission_value_perpanjangan' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
