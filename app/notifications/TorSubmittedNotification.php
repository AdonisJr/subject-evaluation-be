<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TorSubmittedNotification extends Notification
{
    use Queueable;

    protected $tor;
    protected $user;

    public function __construct($tor, $user)
    {
        $this->tor = $tor;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New TOR Submitted',
            'message' => "{$this->user->first_name} {$this->user->last_name} uploaded a new TOR.",
            'tor_id' => $this->tor->id,
            'user_id' => $this->user->id,
        ];
    }
}
