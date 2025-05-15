<?php

namespace BemaGoalForge\Dashboard;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\Utilities\Logger;
use BemaGoalForge\TaskManagement\TaskModel;
use BemaGoalForge\ProjectManagement\ProjectModel;

class PublicDashboardController
{
    private TaskModel $taskModel;
    private ProjectModel $projectModel;
    private Logger $logger;

    public function __construct(TaskModel $taskModel, ProjectModel $projectModel, Logger $logger)
    {
        $this->taskModel = $taskModel;
        $this->projectModel = $projectModel;
        $this->logger = $logger;
    }

    /**
     * Initialize hooks for the public dashboard.
     */
    public static function init()
    {
        $controller = new self(new TaskModel(), new ProjectModel(), new Logger());

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [self::class, 'enqueuePublicScripts']);

        // Add shortcode for rendering the dashboard
        add_shortcode('goalforge_public_dashboard', [$controller, 'renderDashboard']);
    }

    /**
     * Enqueue scripts and styles for the public dashboard.
     */
    public static function enqueuePublicScripts()
    {
        wp_enqueue_style(
            'goalforge-public-styles',
            plugins_url('/assets/css/public.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'goalforge-public-scripts',
            plugins_url('/assets/js/public.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('goalforge-public-scripts', 'GoalForgePublic', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('goalforge_public_nonce'),
        ]);
    }

    /**
     * Render the public dashboard.
     *
     * @return string
     */
    public function renderDashboard(): string
    {
        ob_start();

        // Fetch data for the dashboard
        $projects = $this->projectModel->getAllProjects();
        $tasks = $this->taskModel->getAllTasks();

        // Load the dashboard template
        $template_path = plugin_dir_path(__FILE__) . '../../templates/public/public-dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->logger->logMessage('Public dashboard template not found.', 'error');
            echo '<p>Error loading dashboard. Please contact support.</p>';
        }

        return ob_get_clean();
    }
}
