<?php

namespace BemaGoalForge\ProjectManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ProjectModel
{
    /**
     * Saves a new project to the database.
     *
     * @param array $projectData Associative array containing 'title', 'description', 'due_date'.
     * @return bool True on success, false on failure.
     */
    public function saveProject(array $projectData): bool
    {
        global $wpdb;
        

       $table_name = $wpdb->prefix . 'goalforge_projects';
        $result = $wpdb->insert(
            $table_name,
            [
                'title'       => sanitize_text_field($projectData['title']),
                'description' => sanitize_textarea_field($projectData['description']),
                'start_date'  => sanitize_text_field($projectData['start_date']),
                'due_date'    => sanitize_text_field($projectData['due_date']),
                'reminder_time' => sanitize_text_field($projectData['reminder_time']),
                'created_by'    => sanitize_text_field($projectData['created_by']),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {    
            
            error_log('GoalForge: Failed to save project - ' . $wpdb->last_error);
            return false;
        }

        // Optional: Handle assigned users in a separate table
        if (!empty($projectData['users']) && is_array($projectData['users'])) {
            $project_id = $wpdb->insert_id;
            $user_table = $wpdb->prefix . 'goalforge_project_users';

            foreach ($projectData['users'] as $user_id) {
                $wpdb->insert($user_table, [
                    'project_id' => (int) $project_id,
                    'user_id'    => (int) $user_id,
                ], ['%d', '%d']);
            }
        }

        return true;

    }    

    /**
     * Updates project details in the database.
     *
     * @param int $projectId The ID of the project to update.
     * @param array $updatedData Associative array of fields to update.
     * @return bool True on success, false on failure.
     */
    public function updateProject(int $projectId, array $updatedData): bool
    {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'goalforge_projects';
    
        $result = $wpdb->update(
            $table_name,
            [
                'title'       => sanitize_text_field($updatedData['title']),
                'description' => sanitize_textarea_field($updatedData['description']),
                'due_date'    => sanitize_text_field($updatedData['due_date']),
                'updated_at'  => current_time('mysql'),
            ],
            ['id' => $projectId], // Where clause
            ['%s', '%s', '%s', '%s'], // Data format
            ['%d'] // Where clause format
        );
    
        if ($result === false) {
            error_log('GoalForge: Failed to update project - ' . $wpdb->last_error);
            return false;
        }
    
        return true;
    }
    
    
}
