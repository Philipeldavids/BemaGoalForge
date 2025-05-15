<?php

namespace BemaGoalForge\TaskManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Scheduler
{
    /**
     * Schedule recurring tasks on plugin activation.
     */
    public static function scheduleTasks()
    {
        if (!wp_next_scheduled('goalforge_cron_task')) {
            wp_schedule_event(time(), 'hourly', 'goalforge_cron_task');
        }
    }

    /**
     * Unschedule recurring tasks on plugin deactivation.
     */
    public static function unscheduleTasks()
    {
        if (wp_next_scheduled('goalforge_cron_task')) {
            wp_clear_scheduled_hook('goalforge_cron_task');
        }
    }

    /**
     * Cron task callback for periodic plugin functionality.
     */
    public static function runScheduledTask()
    {
        // Perform scheduled actions, such as sending reminders or processing data
        error_log('GoalForge: Scheduled task executed.');
    }
}

// Hook the cron task callback
add_action('goalforge_cron_task', [Scheduler::class, 'runScheduledTask']);
