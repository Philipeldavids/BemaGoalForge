<?php

namespace BemaGoalForge\Feedback;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatController
{
    /**
     * Sends a message to the task chat.
     *
     * @param int $taskId
     * @param string $message
     * @return bool
     */
    public function sendMessage(int $taskId, string $message): bool
    {
        if (empty($message)) {
            return false;
        }

        $chatModel = new ChatModel();
        return $chatModel->saveMessage($taskId, $message);
    }

    /**
     * Retrieves all messages for a given task.
     *
     * @param int $taskId
     * @return array
     */
    public function fetchMessages(int $taskId): array
    {
        $chatModel = new ChatModel();
        return $chatModel->getMessagesByTaskId($taskId);
    }
}
