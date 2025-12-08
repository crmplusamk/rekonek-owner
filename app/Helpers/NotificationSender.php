<?php

namespace App\Helpers;

use Throwable;
use Illuminate\Support\Facades\DB;
use App\Models\Scopes\CompanyScope;
use PhpParser\Node\Expr\Cast\Bool_;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Expr\Cast\Object_;
use App\Events\PublicNotificationEvent;
use App\Notifications\UserNotification;
use App\Events\PrivateNotificationEvent;
use Illuminate\Support\Facades\Notification;
use Modules\User\App\Models\User;

class NotificationSender
{

    protected $users;
    protected $data;

    public function __construct($data, $users = null)
    {
        $this->data  = $data;
        $this->users = $users;
    }

    /**
     * init
     *
     * @param Object $data
     * @param Object $users
     */
    public static function send(Object $data)
    {
        return new static($data);
    }

    /**
     * toPrivate
     *
     * @param boolean $withSocket
     * @param boolean $withDatabase
     */
    public function toPrivate(bool $withSocket, bool $withDatabase, Object $users)
    {
        try {

            $this->users = $users;
            $withSocket ? PrivateNotificationEvent::dispatch($this->data, $this->users) : null;
            $withDatabase ? $this->sendNotifications() : null;
            return true;

        } catch (Throwable $e) {

            return $e->getMessage();
        }
    }

    /**
     * toPublic
     *
     * @param boolean $withSocket
     * @param boolean $withDatabase
     */
    public function toPublic(bool $withSocket, bool $withDatabase)
    {
        try {

            $withSocket ? PublicNotificationEvent::dispatch($this->data) : null;
            $withDatabase ? $this->sendNotifications() : null;
            return true;

        } catch (Throwable $e) {

            return $e->getMessage();
        }
    }

    protected function sendNotifications()
    {
        $users = ($this->users) ? $this->users : User::get();
        Notification::sendNow($users, new UserNotification($this->data));
    }
}
