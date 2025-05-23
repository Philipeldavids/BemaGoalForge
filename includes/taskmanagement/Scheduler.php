<?php

namespace BemaGoalForge\TaskManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
use BemaGoalForge\Notification\NotificationModel;
class Scheduler
{
    /**
     * Schedule recurring tasks on plugin activation.
     */
    public static function scheduleTasks()
    {
        if (!wp_next_scheduled('goalforge_cron_task')) {
            wp_schedule_event(time(), 'five_minutes', 'goalforge_cron_task');
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
        global $wpdb;
        //For tasks
    $now = current_time('mysql');
    $tasks_table = "{$wpdb->prefix}goalforge_tasks";
    $assignees_table = "{$wpdb->prefix}goalforge_task_assignees";

    $tasks = $wpdb->get_results("SELECT t.*, a.user_id
        FROM $tasks_table t
        INNER JOIN $assignees_table a ON a.task_id = t.id
        WHERE t.due_date IS NOT NULL AND t.reminder_time IS NOT NULL");
    
    foreach ($tasks as $task) {
        $reminder_time = self::calculate_reminder_timestamp($task->due_date, $task->reminder_time);
        $now_time = strtotime(current_time('mysql'));
        error_log("Checking task: {$task->id}, user: {$task->user_id}");
        error_log("Reminder time: " . date('Y-m-d H:i:s', $reminder_time));
        error_log("Current time: " . date('Y-m-d H:i:s', $now_time));

        // Allow small window (±3 min) for timing flexibility
        if ($reminder_time && abs($now_time - $reminder_time) < 180) {
            NotificationModel::send_reminder_email($task->user_id, $task, 'task');
        }
    }

    //For projects
    $projects_table = "{$wpdb->prefix}goalforge_projects";
    $project_collab_table = "{$wpdb->prefix}goalforge_project_users";

    $projects = $wpdb->get_results("
        SELECT p.*
        FROM $projects_table p
        WHERE p.due_date IS NOT NULL AND p.reminder_time IS NOT NULL
    ");

    foreach ($projects as $project) {
        $reminder_time = self::calculate_reminder_timestamp($project->due_date, $project->reminder_time);
        $now_time = strtotime(current_time('mysql'));
        if ($reminder_time && abs($now_time - $reminder_time) < 180) {
            $collaborators = $wpdb->get_col($wpdb->prepare("
                SELECT user_id FROM $project_collab_table WHERE project_id = %d
            ", $project->id));

            foreach ($collaborators as $user_id) {
                NotificationModel::send_reminder_email($user_id, $project, 'project');
            }
        }
    }
        // Perform scheduled actions, such as sending reminders or processing data
        error_log('GoalForge: Scheduled task executed.');
    }

  public static function calculate_reminder_timestamp($due_date, $reminder_setting) {
    $due = strtotime($due_date);

    switch ($reminder_setting) {
        case 'on_due_date':
            return $due;
        case '5_minutes_before':
            return $due - 5 * 60;
        case '10_minutes_before':
            return $due - 10 * 60;
        case '15_minutes_before':
            return $due - 15 * 60;
        case '1_hour_before':
            return $due - 60 * 60;
        case '2_hours_before':
            return $due - 2 * 60 * 60;
        case '1_day_before':
            return $due - 24 * 60 * 60;
        case '2_days_before':
            return $due - 2 * 24 * 60 * 60;
        default:
            return null;
    }
}
}


