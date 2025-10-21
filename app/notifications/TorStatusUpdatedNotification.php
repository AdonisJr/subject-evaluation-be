<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TorStatusUpdatedNotification extends Notification
{
    use Queueable;

    protected $tor;
    protected $status;
    protected $admin;

    public function __construct($tor, $status, $admin)
    {
        $this->tor = $tor;
        $this->status = $status;
        $this->admin = $admin;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Request Status Updated',
            'message' => "Your Request has been {$this->status} by {$this->admin->first_name}.",
            'tor_id' => $this->tor->id,
        ];
    }
}
