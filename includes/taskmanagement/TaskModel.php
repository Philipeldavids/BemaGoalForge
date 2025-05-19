<?php

namespace BemaGoalForge\TaskManagement;
use BemaGoalForge\Utilities\Validation;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class TaskModel
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'goalforge_tasks';
    }

    /**
     * Saves a new task to the database after validating business rules.
     *
     * @param array $taskData
     * @return bool
     */
    public function saveTask(array $taskData): bool
    {
        global $wpdb;

        // Validate business rules
        if (!$this->validateTaskData($taskData, true)) {
            error_log('TaskModel: Task data validation failed.');
            return false;
        }

        // Insert into the database
        $result = $wpdb->insert(
            $this->table_name,
            [
                'title' => sanitize_text_field($taskData['title']),
                'description' => sanitize_textarea_field($taskData['description']),
                'start_date' => sanitize_text_field($taskData['start_date']),
                'due_date' => sanitize_text_field($taskData['due_date']),
                'reminder_time' => sanitize_text_field($taskData['reminder_time']),
                'project_id' => intval($taskData['project_id']),
                'milestone_id' => intval($taskData['milestone_id']),
                'created_by' => get_current_user_id(),
                'created_at'   => current_time('mysql'),
                'updated_at'   => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', ]
        );

        if ($result === false) {
            error_log('TaskModel: Failed to save task - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Updates task details in the database.
     *
     * @param int $taskId
     * @param array $updatedData
     * @return bool
     */
    public function updateTask(int $taskId, array $updatedData): bool
    {
        global $wpdb;

        // Validate business rules
        if (!$this->validateTaskData($updatedData, true)) {
            error_log("TaskModel: Task update validation failed for task ID $taskId.");
            return false;
        }

        // Update task in the database

     
        $result = $wpdb->update(
            $this->table_name,
            $this->sanitizeTaskData($updatedData),
            ['id' => $taskId],
            ['%s', '%s', '%s', '%d','%d','%d'], // Data types for fields being updated
            ['%d'] // Data type for the WHERE clause
        );

        if ($result === false) {
            error_log('TaskModel: Failed to update task - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Validates task data against business rules.
     *
     * @param array $taskData
     * @param bool $isUpdate
     * @return bool
     */
    private function validateTaskData(array $taskData, bool $isUpdate = false): bool
    {
        // Title must be non-empty and at least 5 characters long
        if (empty($taskData['title']) || strlen($taskData['title']) < 5) {
            error_log('TaskModel: Title must be at least 5 characters long.');
            return false;
        }

        // Description must not exceed 2000 characters
        if (isset($taskData['description']) && strlen($taskData['description']) > 2000) {
            error_log('TaskModel: Description exceeds maximum length of 2000 characters.');
            return false;
        }

        // Due date must be a valid date
        // if (isset($taskData['due_date']) && !Validation::isValidDate($taskData['due_date'])) {
        //     error_log('TaskModel: Invalid due date provided.');
        //     return false;
        // }

        // For updates, at least one field must be provided
        if ($isUpdate && empty($taskData)) {
            error_log('TaskModel: No fields provided for update.');
            return false;
        }

        return true;
    }


    /**
     * Link a task to a project and log the event.
     *
     * @param int $taskId
     * @param int $projectId
     * @return bool
     */
    public function linkTaskToProject(int $taskId, int $projectId): bool
    {
        global $wpdb;

        // Validate existence of the task and project
        if (!$this->isTaskValid($taskId) || !$this->isProjectValid($projectId)) {
            error_log("TaskModel: Invalid Task ID {$taskId} or Project ID {$projectId}");
            return false;
        }

        // Update the task's project ID
        $updateResult = $wpdb->update(
            $this->table_name,
            ['project_id' => $projectId],
            ['id' => $taskId],
            ['%d'],
            ['%d']
        );

        if ($updateResult === false) {
            error_log("TaskModel: Failed to update Task ID {$taskId} with Project ID {$projectId} - " . $wpdb->last_error);
            return false;
        }

        // Log the linking event in the task links table
        $linkTable = $wpdb->prefix . 'goalforge_task_links';
        $logResult = $wpdb->insert(
            $linkTable,
            [
                'task_id'      => $taskId,
                'project_id' => $projectId,
                'linked_at'    => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );

        if ($logResult === false) {
            error_log("TaskModel: Failed to log Task ID {$taskId} linking to Project ID {$projectId} - " . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Retrieve the linking history of a task.
     *
     * @param int $taskId
     * @return array
     */
    public function getTaskLinkHistory(int $taskId): array
    {
        global $wpdb;

        $linkTable = $wpdb->prefix . 'goalforge_task_links';
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$linkTable} WHERE task_id = %d ORDER BY linked_at DESC", $taskId),
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Validate if a task exists.
     *
     * @param int $taskId
     * @return bool
     */
    private function isTaskValid(int $taskId): bool
    {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d", $taskId));
        return $exists > 0;
    }

    /**
     * Validate if a project exists.
     *
     * @param int $projectId
     * @return bool
     */
    private function isProjectValid(int $projectId): bool
    {
        global $wpdb;
        $projectTable = $wpdb->prefix . 'goalforge_projects';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$projectTable} WHERE id = %d", $projectId));
        return $exists > 0;
    }

    private function sanitizeTaskData(array $taskData): array
    {
        $sanitizedData = [
            'title'        => sanitize_text_field($taskData['title'] ?? ''),
            'description'  => isset($taskData['description']) ? sanitize_textarea_field($taskData['description']) : null,
            'due_date'     => isset($taskData['due_date']) 
                                ? sanitize_text_field($taskData['due_date'])
                                : null,
            'project_id' => isset($taskData['project_id']) ? intval($taskData['project_id']) : null,
            'milestone_id' => isset($taskData['milestone_id']) ? intval($taskData['milestone_id']) : null,
            'created_at'   => $taskData['created_at'] ?? current_time('mysql'),
            'updated_at'   => current_time('mysql'),
        ];

        if (isset($taskData['due_date']) && is_null($sanitizedData['due_date'])) {
            error_log("TaskModel: Invalid date provided for task data.");
        }

        return $sanitizedData;
    }


}
