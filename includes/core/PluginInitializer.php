<?php

namespace BemaGoalForge\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class PluginInitializer
{
    public function __construct()
    {
        // Initialization moved to separate methods for clarity
        $this->initializeHooks();

    }

    /**
     * Initialize WordPress hooks.
     */
    public function initializeHooks()
    {
        // Hook into activation and deactivation
        register_activation_hook(GOALFORGE_MAIN_FILE, [PluginActivator::class, 'activate']);
        register_deactivation_hook(GOALFORGE_MAIN_FILE, [PluginDeactivator::class, 'deactivate']);

        // Other initialization logic can go here\\

    }

    /**
     * Initialize plugin dependencies.
     */
    public function initializePlugin()
    {
        // Initialize TaskTemplateController AJAX actions
        add_action('wp_ajax_apply_template', [new \BemaGoalForge\TaskManagement\TaskTemplateController(
            new \BemaGoalForge\TaskManagement\TaskTemplateModel(),
            new \BemaGoalForge\TaskManagement\TaskModel(),
            new \BemaGoalForge\Utilities\Logger() // Corrected Logger instantiation
        ), 'ajaxApplyTemplate']);

        // Additional module initializations can go here
        $tester = new \BemaGoalForge\Utilities\Tester();

    }
}
