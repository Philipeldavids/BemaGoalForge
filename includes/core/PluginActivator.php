<?php

namespace BemaGoalForge\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\Database\DatabaseHandler;
use BemaGoalForge\TaskManagement\Scheduler;

class PluginActivator
{
    /**
     * Handles plugin activation tasks.
     */
    public static function activate()
    {
        self::checkCompatibility();

        // Create the departments table 
        DatabaseHandler::createDepartmentsTable();

        // Create database tables
        DatabaseHandler::createTables();

        // Schedule any necessary cron jobs
        Scheduler::scheduleTasks();

    }

    /**
     * Check WordPress and PHP compatibility.
     */
    private static function checkCompatibility()
    {
        global $wp_version;

        if (version_compare(PHP_VERSION, '7.4', '<') || version_compare($wp_version, '5.0', '<')) {
            wp_die(
                esc_html__('Bema GoalForge requires PHP 7.4 or higher and WordPress 5.0 or higher. Please update your server environment.'),
                esc_html__('Plugin Activation Error'),
                ['back_link' => true]
            );
        }
    }
}
