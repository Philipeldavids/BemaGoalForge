<?php

namespace BemaGoalForge\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\Database\DatabaseHandler;

class PluginUninstaller
{
    /**
     * Uninstall the plugin.
     */
    public static function uninstall()
    {
        // Remove database tables
        DatabaseHandler::dropTables();

        // Clean up options, transients, or any other data
        delete_option('goalforge_plugin_version');
        delete_option('goalforge_settings');
    }
}
