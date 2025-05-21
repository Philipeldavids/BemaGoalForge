<?php

namespace BemaGoalForge\Notification;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\TaskManagement\TaskModel;
use BemaGoalForge\ProjectManagement\ProjectModel;
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

public static function send_assignment_email($user_id, $context = 'task', $context_id = 0)
{
    $user = get_user_by('id', $user_id);
    if (!$user || !$user->user_email) {
        return;
    }

    $subject = '';
    $message = '';

     if ($context === 'task') {
        $task = TaskModel::get_task_by_id($context_id); // You need to implement this
         if (!$task) return;
        $subject = 'You have been assigned a new task';
        $message = "Hi {$user->display_name},\n\nYou’ve been assigned a new task: \"{$task->title}\".";
    } elseif ($context === 'project') {
        $project = ProjectModel::get_project_by_id($context_id); // Implement as needed
         if (!$project) return;
        $subject = 'You have been added to a new project';
        $message = "Hi {$user->display_name},\n\nYou’ve been added to the project: \"{$project->title}\".";
    }

    wp_mail($user->user_email, $subject, $message);
}

}
