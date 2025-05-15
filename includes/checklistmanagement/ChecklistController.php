<?php

namespace BemaGoalForge\ChecklistManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\ChecklistManagement\ChecklistModel;

class ChecklistController
{
    /**
     * Adds a checklist to a task.
     *
     * @param array $checklistData
     * @return bool
     */
    public function addChecklist(array $checklistData): bool
    {
        if (empty($checklistData['task_id']) || empty($checklistData['steps'])) {
            error_log("GoalForge: Missing task ID or steps in addChecklist.");
            return false;
        }

        $checklistModel = new ChecklistModel();
        return $checklistModel->saveChecklist($checklistData);
    }

    /**
     * Updates an existing checklist.
     *
     * @param int $checklistId
     * @param array $updatedData
     * @return bool
     */
    public function updateChecklist(int $checklistId, array $updatedData): bool
    {
        if (empty($checklistId) || empty($updatedData)) {
            error_log("GoalForge: Missing checklist ID or data in updateChecklist.");
            return false;
        }

        $checklistModel = new ChecklistModel();
        return $checklistModel->updateChecklist($checklistId, $updatedData);
    }
}
