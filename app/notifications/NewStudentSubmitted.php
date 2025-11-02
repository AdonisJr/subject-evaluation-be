<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewStudentSubmitted extends Notification
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
            'title' => 'New Student Request Submitted',
            'message' => "{$this->user->first_name} {$this->user->last_name} submitted a request for advising.",
            'tor_id' => $this->tor->id,
            'user_id' => $this->user->id,
        ];
    }
}
