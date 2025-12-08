<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\User\App\Models\User;
use Modules\WhatsappOtp\App\Models\WhatsappOtpSession;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('notification.{id}', function (User $user, $id) {
    return $user->id === $id;
});

Broadcast::channel('whatsapp-session.{session}', function (User $user, $session) {
    if (WhatsappOtpSession::where('created_at', $user->id)->where('session', $session)->exists()) {
        return true;
    }
});
