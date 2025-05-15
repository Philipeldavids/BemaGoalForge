<?php

namespace BemaGoalForge\ProjectManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\Database\DatabaseHandler;
use BemaGoalForge\ProjectManagement\ProjectModel; 

class ProjectController { 
    
    /** * Handles creating a new project. * * 
     * @param array $projectData * 
     * @return bool */ 
    
     public function createProject(array $projectData): bool { 
        
        
        // Basic validation 
        if (empty($projectData['title']) || empty($projectData['start_date']) || empty($projectData['due_date'])) { 
            error_log('GoalForge: Missing required project data (title, start_date, or due_date).'); 
            return false; 
        } 
        // Validate date logic 
        if (!empty($projectData['start_date']) && !empty($projectData['due_date'])) { 
            if (strtotime($projectData['start_date']) > strtotime($projectData['due_date'])) { 
                error_log('GoalForge: Start date cannot be after due date.'); return false; 
            } 
        } 
        // Save project via model 
        $projectModel = new ProjectModel(); 
        return $projectModel->saveProject($projectData); 
    } 


    /**
     * Updates an existing project's data.
     *
     * @param int $projectId The ID of the project to update.
     * @param array $updatedData Associative array of fields to update.
     * @return bool True on success, false on failure.
     */
    public function updateProject(int $projectId, array $updatedData): bool
    {
        // Validate required fields
        if (empty($updatedData['title']) || empty($updatedData['description']) || empty($updatedData['due_date'])) {
            error_log('GoalForge: Missing required fields for updating project.');
            return false;
        }
    
        // Ensure project ID is valid
        if ($projectId <= 0) {
            error_log('GoalForge: Invalid project ID provided.');
            return false;
        }

        if (!empty($updatedData['start_date']) && !empty($updatedData['due_date'])) {
            if (strtotime($updatedData['start_date']) > strtotime($updatedData['due_date'])) {
                error_log('GoalForge: Start date cannot be after due date.');
                return false;
            }
        }
    
        // Pass sanitized data to the model
        $projectModel = new ProjectModel();
        return $projectModel->updateProject($projectId, $updatedData);
    }
    
    public static function getProjectById($projectId) {
        global $wpdb;
        $table = $wpdb->prefix . 'goalforge_projects';

        $query = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $projectId);
        $project = $wpdb->get_row($query);

        return $project;
    }

    public static function getCollaboratorsByProjectId($projectId)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'goalforge_project_users';
        $results = $wpdb->get_col(
        $wpdb->prepare("SELECT user_id FROM $table WHERE project_id = %d", $projectId)
        );

        return is_array($results) ? $results : [];

    }

    public function updateCollaborators($projectId, array $userIds)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'goalforge_project_users';

        // Remove all current collaborators
        $wpdb->delete($table, ['project_id' => $projectId], ['%d']);

        // Reinsert new collaborators
        $inserted = 0;
        foreach ($userIds as $userId) {
            $result = $wpdb->insert($table, [
                'project_id' => $projectId,
                'user_id'    => $userId,
            ], ['%d', '%d']);

            if ($result !== false) {
                $inserted++;
            }
        }

        return $inserted >= 0;
    }


    public static function getAllProjects($limit = 10, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'goalforge_projects';
        return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table ORDER BY due_date DESC LIMIT %d OFFSET %d", $limit, $offset)
        );
    }
    public static function getProjectCount() {
        global $wpdb;
        $table = $wpdb->prefix . 'goalforge_projects';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
}
