<?php

namespace BemaGoalForge\Feedback;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatModel
{
    /**
     * Saves a chat message to the database.
     *
     * @param int $taskId
     * @param string $message
     * @return bool
     */
    public function saveMessage(int $taskId, string $message): bool
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'goalforge_chat';

        $data = [
            'task_id'   => $taskId,
            'message'   => sanitize_text_field($message),
            'created_at' => current_time('mysql'),
        ];

        return $wpdb->insert($tableName, $data, ['%d', '%s', '%s']) !== false;
    }

    /**
     * Retrieves all messages for a given task.
     *
     * @param int $taskId
     * @return array
     */
    public function getMessagesByTaskId(int $taskId): array
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'goalforge_chat';

        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $tableName WHERE task_id = %d ORDER BY created_at ASC", $taskId),
            ARRAY_A
        );

        return $results ?: [];
    }
}
