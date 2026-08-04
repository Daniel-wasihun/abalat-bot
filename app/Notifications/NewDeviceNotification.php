<?php

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\URL;
use App\Mail\SecurityAlertMail;
use App\Services\BackMessage;

class NewDeviceNotification extends Notification implements ShouldBroadcastNow {

    // ── Notification type constants ───────────────────────────────────────────
    /**
     * A brand-new token/session has appeared for this account.
     * Covers: first-time device, same device after normal logout,
     *         and any other case that is not a terminated-session re-login.
     */
    const TYPE_NEW_DEVICE = 'new_device';

    /**
     * A session that was explicitly TERMINATED (by another device or via a
     * security email link) has logged in again.  This is the more alarming
     * variant and uses dedicated translation keys.
     */
    const TYPE_TERMINATED_RELOGIN = 'terminated_relogin';

    public array $sessionData;

    public function __construct(array $sessionData) {
        $this->sessionData = $sessionData;
    }

    // ── Broadcast channel ────────────────────────────────────────────────────

    /**
     * Broadcast on the same private channel used by UserSessionUpdated so the
     * frontend only needs one subscription per user.
     */
    public function broadcastOn(): array {
        return [
            new PrivateChannel('user.' . ($this->sessionData['user_id'] ?? 0)),
        ];
    }

    // ── Delivery channels ────────────────────────────────────────────────────

    public function via(object $notifiable): array {
        return ['mail', 'database', 'broadcast'];
    }

    // ── Mail ─────────────────────────────────────────────────────────────────

    public function toMail(object $notifiable): SecurityAlertMail {
        $sessionId = $this->sessionData['session_id'];
        $userId    = $notifiable->id;

        $approveUrl   = URL::signedRoute('security.approve-session',   ['session_id' => $sessionId]);
        $terminateUrl = URL::signedRoute('security.terminate-session', ['session_id' => $sessionId]);
        $lockUrl      = URL::signedRoute('security.lock-account',      ['user_id'    => $userId]);

        $name = $notifiable->name[app()->getLocale()]
            ?? $notifiable->name['en']
            ?? $notifiable->name;

        return (new SecurityAlertMail([
            'name'              => $name,
            'notification_type' => $this->notificationType(),
            'device_name'       => $this->sessionData['device_name'],
            'ip_address'        => $this->sessionData['ip_address'] ?? null,
            'location'          => $this->sessionData['location'],
            'time'              => now()->format('M d, Y H:i:s'),
            'approve_url'       => $approveUrl,
            'terminate_url'     => $terminateUrl,
            'lock_url'          => $lockUrl,
        ]))->to($notifiable->email);
    }

    // ── Broadcast payload ────────────────────────────────────────────────────

    public function toBroadcast(object $notifiable): BroadcastMessage {
        return new BroadcastMessage($this->buildPayload());
    }

    // ── Database payload ─────────────────────────────────────────────────────

    public function toArray(object $notifiable): array {
        return $this->buildPayload();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function notificationType(): string {
        return $this->sessionData['notification_type'] ?? self::TYPE_NEW_DEVICE;
    }

    private function isTerminatedRelogin(): bool {
        return $this->notificationType() === self::TYPE_TERMINATED_RELOGIN;
    }

    /**
     * Build the shared payload used for both broadcast and database storage.
     * Keys ending in `_key` let the frontend re-translate on locale change.
     */
    private function buildPayload(): array {
        $type = $this->notificationType();

        [$titleKey, $messageKey, $shortKey] = $this->isTerminatedRelogin()
            ? [
                'notifications.terminated_relogin_title',
                'notifications.terminated_relogin_message',
                'notifications.terminated_relogin_short',
            ]
            : [
                'notifications.new_device_login_title',
                'notifications.new_device_login_message',
                'notifications.new_device_login_short',
            ];

        $messageParams = [
            'device'   => $this->sessionData['device_name'],
            'location' => $this->sessionData['location'],
        ];

        return [
            'id'                 => $this->id,
            'notification_type'  => $type,
            'type'               => 'security',
            'title'              => BackMessage::get($titleKey),
            'message'            => BackMessage::get($messageKey, $messageParams),
            'title_key'          => $titleKey,
            'message_key'        => $messageKey,
            'short_message_key'  => $shortKey,
            'short_message'      => BackMessage::get($shortKey),
            'message_params'     => $messageParams,
            'session_id'         => $this->sessionData['session_id'],
            'device_name'        => $this->sessionData['device_name'],
            'device_type'        => $this->sessionData['device_type'] ?? null,
            'ip_address'         => $this->sessionData['ip_address'] ?? null,
            'location'           => $this->sessionData['location'],
            'time'               => now()->toIso8601String(),
            'link'               => '/dashboard/profile/devices',
        ];
    }
}
