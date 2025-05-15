<?php

namespace BemaGoalForge\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\Database\DatabaseHandler;

class PluginDeactivator
{
    /**
     * Deactivate the plugin.
     */
    public static function deactivate()
    {
        // Unschedule cron jobs
        if (wp_next_scheduled('goalforge_cron_task')) {
            wp_clear_scheduled_hook('goalforge_cron_task');
        }

        // Perform other deactivation tasks if needed
    }
}
