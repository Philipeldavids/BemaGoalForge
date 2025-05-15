<?php

namespace BemaGoalForge\TaskManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\Utilities\Validation;

class TaskTemplateModel
{

    private string $tableName;

    public function __construct()
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'goalforge_task_templates';
    }

    /**
     * Save a new task template to the database.
     *
     * @param array $data
     * @return bool
     */
    public function saveTemplate(array $data): bool
    {
        global $wpdb;

        // Sanitize and validate input data
        $sanitizedData = $this->sanitizeTemplateData($data);

        // Validate required fields
        if (empty($sanitizedData['title'])) {
            error_log('TaskTemplateModel: Title is required for saving template.');
            return false;
        }

        // Insert data into the database
        $result = $wpdb->insert(
            $this->tableName,
            $sanitizedData,
            ['%s', '%s', '%s', '%d']
        );

        if ($result === false) {
            error_log('GoalForge: Failed to save task template - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Update a task template in the database.
     * 
     * @param int $templateId
     * @param array $templateData
     * @return bool
     */
    public function updateTemplate(int $templateId, array $templateData): bool
    {
        global $wpdb;

        // Sanitize and validate the input data
        $sanitizedData = $this->sanitizeTemplateData($templateData);

        // Remove any null fields to avoid overwriting with null in the database
        $sanitizedData = array_filter($sanitizedData, fn($value) => !is_null($value));

        // Update the template in the database
        $result = $wpdb->update(
            $this->tableName,
            $sanitizedData,
            ['id' => $templateId],
            array_fill(0, count($sanitizedData), '%s'), // Data types for updated fields
            ['%d']                                      // Data type for the WHERE clause
        );

        if ($result === false) {
            error_log("TaskTemplateModel: Failed to update template with ID $templateId. Error: " . $wpdb->last_error);
            return false;
        }

        return true;
    }


    /**
     * Retrieve a template by its ID.
     *
     * @param int $templateId
     * @return array|null
     */
    public static function getTemplateById(int $templateId): ?array
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'goalforge_task_templates';

        $query = $wpdb->prepare("SELECT * FROM $tableName WHERE id = %d", $templateId);
        $result = $wpdb->get_row($query, ARRAY_A);

        return $result ?: null;
    }

    public function sanitizeTemplateData(array $data): array
    {
        $sanitizedData = [
            'title'        => sanitize_text_field($data['title'] ?? ''),
            'description'  => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
            'due_date'     => isset($data['due_date']) && Validation::isValidDate($data['due_date'])
                                ? sanitize_text_field($data['due_date'])
                                : null,
            'project_id' => isset($data['project_id']) ? intval($data['project_id']) : null,
        ];

        if (isset($data['due_date']) && is_null($sanitizedData['due_date'])) {
            error_log("TaskTemplateModel: Invalid date provided for template data.");
        }

        return $sanitizedData;
    }

        /**
     * Retrieve all task templates.
     *
     * @return array
     */
    public function getAllTemplates(): array
    {
        global $wpdb;

        $query = "SELECT * FROM {$this->tableName}";
        $results = $wpdb->get_results($query, ARRAY_A);

        return $results ?: [];
    }

}
