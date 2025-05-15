<?php

namespace BemaGoalForge\ChecklistManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChecklistModel
{
    /**
     * Saves a checklist to the database.
     *
     * @param array $checklistData
     * @return bool
     */
    public function saveChecklist(array $checklistData): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'goalforge_checklists';
        $result = $wpdb->insert(
            $table_name,
            [
                'task_id'    => intval($checklistData['task_id']),
                'steps'      => maybe_serialize($checklistData['steps']),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * Updates a checklist in the database.
     *
     * @param int $checklistId
     * @param array $updatedData
     * @return bool
     */
    public function updateChecklist(int $checklistId, array $updatedData): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'goalforge_checklists';
        $result = $wpdb->update(
            $table_name,
            ['steps' => maybe_serialize($updatedData['steps'])],
            ['id' => $checklistId],
            ['%s'],
            ['%d']
        );

        return $result !== false;
    }
}
