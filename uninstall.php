<?php

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/core/PluginUninstaller.php';

// Call the uninstaller
BemaGoalForge\Core\PluginUninstaller::uninstall();
