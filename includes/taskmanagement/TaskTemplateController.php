<?php

namespace BemaGoalForge\TaskManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\Utilities\Logger;
use BemaGoalForge\TaskManagement\TaskModel;
use BemaGoalForge\TaskManagement\TaskTemplateModel;

class TaskTemplateController
{
    private TaskTemplateModel $taskTemplateModel;
    private TaskModel $taskModel;
    private Logger $logger;


    public function __construct(TaskTemplateModel $taskTemplateModel, TaskModel $taskModel, Logger $logger)
    {
        $this->taskTemplateModel = $taskTemplateModel;
        $this->taskModel = $taskModel;
        $this->logger = $logger;
    }

    /**
     * Apply a predefined task template to create a new task and return the task ID.
     *
     * @param int $templateId
     * @return int|bool Returns the new task ID on success or false on failure.
     */
    public function applyTemplate(int $templateId): int|bool
    {
        $templateData = $this->taskTemplateModel->getTemplateById($templateId);

        if (!$templateData) {
            $this->logger->logMessage("Invalid template ID: $templateId", 'error');
            return false;
        }

        $taskData = [
            'title'        => $templateData['title'],
            'description'  => $templateData['description'],
            'due_date'     => $templateData['due_date'],
            'project_id' => $templateData['project_id'] ?? null,
        ];

        $isSaved = $this->taskModel->saveTask($taskData);

        if (!$isSaved) {
            $this->logger->logMessage("Failed to create task from template ID: $templateId", 'error');
            return null;
        }
    
        // Return the ID of the newly created task
        global $wpdb;
        $taskId = $wpdb->insert_id;
    
        if (!$taskId) {
            $this->logger->logMessage("Task creation from template ID $templateId succeeded but task ID retrieval failed.", 'warning');
            return null;
        }
    
        return $taskId;
    }

    /**
     * Create a new task template for future use.
     *
     * @param array $templateData
     * @return bool
     */
    public function createTemplate(array $templateData): bool
    {
        // Validate the input data
        $sanitizedData = $this->taskTemplateModel->sanitizeTemplateData($templateData);

        // Check for required fields
        if (empty($sanitizedData['title']) || empty($sanitizedData['description'])) {
            $this->logger->logMessage("Failed to create task template: Missing required fields", 'error');
            return false;
        }

        // Save the template
        if (!$this->taskTemplateModel->saveTemplate($sanitizedData)) {
            $this->logger->logMessage("Failed to create task template: " . json_encode($sanitizedData), 'error');
            return false;
        }

        return true;
    }


 
    /**
     * Update an existing task template.
     *
     * @param int $templateId
     * @param array $updatedData
     * @return bool
     */
    public function updateTemplate(int $templateId, array $updatedData): bool
    {
        $sanitizedData = $this->taskTemplateModel->sanitizeTemplateData($updatedData);

        $result = $this->taskTemplateModel->updateTemplate($templateId, $sanitizedData);

        if (!$result) {
            $this->logger->logMessage("Failed to update task template with ID $templateId", 'error');
            return false;
        }

        return true;
    }

    /**
     * AJAX handler for applying a template via UI.
     */
    public function ajaxApplyTemplate()
    {
        check_ajax_referer('apply_template_nonce', '_wpnonce');

        $templateId = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;

        if ($templateId <= 0) {
            wp_send_json_error(['message' => 'Invalid template ID']);
        }

        try {
            $taskTemplateModel = new TaskTemplateModel();
            $taskModel = new TaskModel();
            $logger = new Logger(); // Corrected Logger instantiation
            $controller = new TaskTemplateController($taskTemplateModel, $taskModel, $logger);

            $taskId = $controller->applyTemplate($templateId);

            if (!$taskId) {
                wp_send_json_error(['message' => 'Failed to create task from template']);
            }

            $taskData = $this->$taskModel->getTaskById($taskId);
            if (!$taskData) {
                wp_send_json_error(['message' => 'Failed to retrieve created task data']);
            }

            wp_send_json_success(['task' => $taskData]);
        } catch (\Exception $e) {
            error_log("GoalForge: Error in AJAX applyTemplate - " . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred. Please try again later.']);
        }
    }

    /**
     * Initialize AJAX hooks for template actions.
     */
    public static function init()
    {
        add_action('wp_ajax_apply_template', [self::class, 'ajaxApplyTemplate']);
    }

    public function renderTemplatePage()
    {
        ?>
        <div class="wrap">
            <h1>Create Task from Template</h1>
            <form id="apply-template-form">
                <label for="template-select">Select Template:</label>
                <select id="template-select">
                    <option value="">-- Select a Template --</option>
                    <?php
                    // Fetch templates dynamically
                    $templates = $this->taskTemplateModel->getAllTemplates();
                    foreach ($templates as $template) {
                        echo sprintf('<option value="%d">Template: %s</option>', esc_attr($template['id']), esc_html($template['title']));
                    }
                    ?>
                </select>

                <input type="hidden" id="apply-template-nonce" value="<?php echo esc_attr(wp_create_nonce('apply_template_nonce')); ?>" />

                <button id="apply-template-btn" class="button button-primary">Create Task</button>
            </form>

            <div id="new-task-result"></div>
        </div>
        <?php
    }

    
}
