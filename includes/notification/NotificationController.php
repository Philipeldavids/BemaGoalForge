<?php

namespace BemaGoalForge\Notification;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class NotificationController
{
    /**
     * Sends a notification to a user.
     *
     * @param int $userId The ID of the user.
     * @param string $content The notification content.
     * @return bool Returns true on success, false on failure.
     */
    public function sendNotification(int $userId, string $content): bool
    {
        // Validate input data
        if ($userId <= 0 || empty($content)) {
            error_log('NotificationController: Invalid user ID or content.');
            return false;
        }

        // Initialize NotificationModel
        $notificationModel = new NotificationModel();

        // Queue the notification
        $result = $notificationModel->queueNotification([
            'user_id'  => $userId,
            'content'  => $content,
            'status'   => 'queued',
            'created_at' => current_time('mysql'),
        ]);

        if ($result) {
            return true;
        } else {
            error_log('NotificationController: Failed to queue notification for user ID ' . $userId);
            return false;
        }
    }
}
