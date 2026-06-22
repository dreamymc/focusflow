<?php

namespace App\Notifications;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invitation $invitation,
        public User $inviter
    ) {}

    public function via(object $notifiable): array
    {
        // On-demand notifications (bare email string) get mail only
        // Registered User models get mail + database + broadcast
        return $notifiable instanceof User
            ? ['mail', 'database', 'broadcast']
            : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = url('/invitations?token=' . $this->invitation->token);

        return (new MailMessage)
            ->subject("You've been invited to {$this->invitation->workspace->name}")
            ->greeting("Hello!")
            ->line("{$this->inviter->name} has invited you to join **{$this->invitation->workspace->name}** as a **{$this->invitation->role->value}**.")
            ->action('Accept Invitation', $acceptUrl)
            ->line('If you did not expect this invitation, you can ignore this email.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'workspace_id' => $this->invitation->workspace->id,
            'workspace_name' => $this->invitation->workspace->name,
            'inviter_name' => $this->inviter->name,
            'role' => $this->invitation->role->value,
            'token' => $this->invitation->token,
        ];
    }
}
