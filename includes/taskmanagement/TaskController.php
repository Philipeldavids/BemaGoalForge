<?php

namespace BemaGoalForge\TaskManagement;
use BemaGoalForge\Utilities\Validation;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


use BemaGoalForge\TaskManagement\TaskModel;

class TaskController
{
  
    /**
     * Create a new task.
     *
     * @param array $taskData
     * @return bool
     */
    public function createTask(array $taskData): bool
    {
        // Basic input sanitization
        //$sanitizedData = $this->sanitizeInput($taskData);
        
        // Validate start date and due date
        if (!empty($taskData['start_date']) && !empty($taskData['due_date'])) {
            if (strtotime($taskData['start_date']) > strtotime($taskData['due_date'])) {
                error_log('Start date cannot be after due date.');
                return false;
            }
        }

        $taskModel = new TaskModel();
        // Delegate to the model for validation and saving
        $result = $taskModel->saveTask($taskData);

        if (!$result) {
            
            error_log('Failed to create task.');
            return false;
        }

        return true;
    }


    /**
     * Update an existing task.
     *
     * @param int $taskId
     * @param array $updatedData
     * @return bool
     */
    public function updateTask(int $taskId, array $updatedData): bool
    {
        // Validate task ID
        if ($taskId <= 0) {
           
            error_log("Invalid Task ID for update: {$taskId}");
            return false;
        }
    

    
        // Check start date and due date logic
        if (!empty($updatedData['start_date']) && !empty($updatedData['due_date'])) {
            if (strtotime($updatedData['start_date']) > strtotime($updatedData['due_date'])) {
                error_log("Start date cannot be after due date for task ID {$taskId}.");
                return false;
            }
        }
        // Delegate to the model for validation and updating
        $taskModel = new TaskModel();
        $result = $taskModel->updateTask($taskId, $updatedData);
    
        // Log errors if update fails
        if (!$result) {
            error_log("Failed to update task with ID {$taskId}.");
            return false;
        }
    
        return true;
    }

    /**
     * Calculates task or project completion percentage.
     *
     * @param int $id
     * @param string $type
     * @return int
     */
    public function getProgress(int $id, string $type = 'task'): int
    {
        // Placeholder for logic to calculate progress based on type (task/project).
        return 0; // Replace with actual calculation logic.
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
        // Validate inputs at the controller level (basic validation)
        if ($taskId <= 0 || $projectId <= 0) {
            $this->logger->logMessage("Invalid task or project ID: Task ID {$taskId}, Project ID {$projectId}", 'error');
            return false;
        }

        // Delegate the linking and logging to the model
          $taskModel = new TaskModel();
        $result = $taskModel->linkTaskToProject($taskId, $projectId);

        if (!$result) {
            $this->logger->logMessage("Failed to link Task ID {$taskId} to Project ID {$projectId}", 'error');
            return false;
        }

        return true;
    }

    /**
     * Retrieve the task linking history.
     *
     * @param int $taskId
     * @return array
     */
    public function getTaskLinkHistory(int $taskId): array
    {
        // Validate task ID at the controller level
        if ($taskId <= 0) {
            $this->logger->logMessage("Invalid Task ID for history retrieval: {$taskId}", 'error');
            return [];
        }

        // Delegate history retrieval to the model
        return $this->taskModel->getTaskLinkHistory($taskId);
    }

    /**
     * Sanitize input data. Basic sanitazation for title, description, due date, and project ID.
     *
     * @param array $data
     * @return array
     */
    // private function sanitizeInput(array $data): array
    // {
    //     return [
    //         'title'        => sanitize_text_field($data['title'] ?? ''),
    //         'description'  => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
    //         'due_date'     => isset($data['due_date']) ? sanitize_text_field($data['due_date']) : null,
    //         'project_id' => isset($data['project_id']) ? intval($data['project_id']) : null,
    //     ];
    // }
  
}
