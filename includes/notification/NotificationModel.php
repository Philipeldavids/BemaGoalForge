<?php

namespace BemaGoalForge\Notification;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class NotificationModel
{
    /**
     * Queues a notification for delivery.
     *
     * @param array $notificationData Associative array of notification data.
     * @return bool Returns true on success, false on failure.
     */
    public function queueNotification(array $notificationData): bool
    {
        global $wpdb;

        // Validate required fields
        if (
            !isset($notificationData['user_id']) || 
            !isset($notificationData['content']) ||
            empty($notificationData['user_id']) ||
            empty($notificationData['content'])
        ) {
            error_log('NotificationModel: Missing required notification fields.');
            return false;
        }

        // Insert notification into the database
        $table = $wpdb->prefix . 'goalforge_notifications';
        $result = $wpdb->insert(
            $table,
            [
                'user_id'    => intval($notificationData['user_id']),
                'content'    => sanitize_text_field($notificationData['content']),
                'status'     => sanitize_text_field($notificationData['status']),
                'created_at' => sanitize_text_field($notificationData['created_at']),
            ],
            ['%d', '%s', '%s', '%s']
        );

        if ($result === false) {
            error_log('NotificationModel: Failed to insert notification. Error: ' . $wpdb->last_error);
            return false;
        }

        return true;
    }
}
