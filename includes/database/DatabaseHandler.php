<?php

namespace BemaGoalForge\Database;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class DatabaseHandler
{

    /**
     * Create the departments table.
     *
     * @return bool
     */
    public static function createDepartmentsTable(): bool
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'goalforge_departments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(50) UNIQUE DEFAULT NULL,
            description TEXT DEFAULT NULL,
            manager_id BIGINT(20) UNSIGNED DEFAULT NULL,
            parent_department_id BIGINT(20) UNSIGNED DEFAULT NULL,
            budget DECIMAL(15, 2) DEFAULT NULL,
            headcount INT UNSIGNED DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (manager_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
            FOREIGN KEY (parent_department_id) REFERENCES {$wpdb->prefix}goalforge_departments(id) ON DELETE SET NULL
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        if (!empty($wpdb->last_error)) {
            error_log('DatabaseHandler: Failed to create departments table. Error: ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Drop the departments table during plugin deactivation.
     *
     * @return bool
     */
    public static function dropDepartmentsTable(): bool
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'goalforge_departments';

        $wpdb->query("DROP TABLE IF EXISTS $table_name");

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            error_log("DatabaseHandler: Failed to drop departments table.");
            return false;
        }

        return true;
    }


    /**
     * Create required database tables during plugin activation.
     *
     * @return bool
     */
    public static function createTables(): bool
    {
        global $wpdb;

        // Table schema definitions
        $charset_collate = $wpdb->get_charset_collate();

        // User Departments table for assigning and managing roles
        $user_departments_table = $wpdb->prefix . 'goalforge_user_departments';
        $user_departments_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}goalforge_user_departments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            department_id BIGINT(20) UNSIGNED NOT NULL,
            role VARCHAR(255) DEFAULT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (department_id) REFERENCES {$wpdb->prefix}goalforge_departments(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Projects table
        $projects_table = $wpdb->prefix . 'goalforge_projects';
        // SQL for projects table
        $projects_sql = "CREATE TABLE IF NOT EXISTS $projects_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            start_date DATETIME DEFAULT NULL, -- New start date field
            due_date DATETIME DEFAULT NULL,
            reminder_time VARCHAR(50) DEFAULT NULL, -- New reminder time field (e.g., '1 hour before')
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";
        

        //Tasks table
        $tasks_table = $wpdb->prefix . 'goalforge_tasks';    
        $sql = "CREATE TABLE IF NOT EXISTS $tasks_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        start_date DATETIME NOT NULL,
        due_date DATETIME NOT NULL,
        reminder_time VARCHAR(50),
        project_id BIGINT(20) UNSIGNED,
        milestone_id BIGINT(20) UNSIGNED,
        created_by BIGINT(20) UNSIGNED,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (project_id) REFERENCES {$wpdb->prefix}goalforge_projects(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
    ) $charset_collate;";


        // Task Links table
        $task_links_table = $wpdb->prefix . 'goalforge_task_links';
        $task_links_sql = "CREATE TABLE IF NOT EXISTS $task_links_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id BIGINT(20) UNSIGNED NOT NULL,
            project_id BIGINT(20) UNSIGNED DEFAULT NULL,
            linked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (task_id) REFERENCES $tasks_table(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id) REFERENCES $projects_table(id) ON DELETE SET NULL
        ) $charset_collate;";


        // Table schema for task templates
        $tasks_templates_table = $wpdb->prefix . 'goalforge_task_templates';
        $tasks_templates_sql = "CREATE TABLE IF NOT EXISTS $tasks_templates_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            start_date DATE NOT NULL,
            due_date DATETIME DEFAULT NULL,
            project_id BIGINT(20) UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

  
        $bonus_table = $wpdb->prefix . 'goalforge_project_bonuses';
        $bonus_sql = "CREATE TABLE IF NOT EXISTS $bonus_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            bonus_amount FLOAT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (project_id) REFERENCES {$wpdb->prefix}goalforge_projects(id) ON DELETE CASCADE
        ) $charset_collate;";

// Project Users (Collaborators) table — for assigning users to projects
$project_users_table = $wpdb->prefix . 'goalforge_project_users';
$project_users_sql = "CREATE TABLE IF NOT EXISTS $project_users_table (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT(20) UNSIGNED NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (project_id) REFERENCES {$projects_table}(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
) $charset_collate;";

        // Chat table
        $chat_table = $wpdb->prefix . 'goalforge_chat';
        $chat_sql = "CREATE TABLE IF NOT EXISTS $chat_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id BIGINT(20) UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (task_id) REFERENCES {$wpdb->prefix}goalforge_tasks(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Notifications table
        $notifications_table = $wpdb->prefix . 'goalforge_notifications';
        $notifications_sql = "CREATE TABLE IF NOT EXISTS $notifications_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'queued',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        //MILESTONE TABLE
        $milestone_table = $wpdb->prefix . 'goalforge_milestones';
        $milestone_sql = "CREATE TABLE IF NOT EXISTS $milestone_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT(20) UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            due_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (project_id) REFERENCES {$wpdb->prefix}goalforge_projects(id) ON DELETE CASCADE
        ) $charset_collate;";

        $assignee_table = $wpdb->prefix . 'goalforge_task_assignees';   

        $assignee_sql = "CREATE TABLE IF NOT EXISTS $assignee_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY task_user_unique (task_id, user_id)
        ) $charset_collate;";

        $checklist_table = $wpdb->prefix . 'goalforge_task_checklists';

        $checklist_sql = "CREATE TABLE IF NOT EXISTS $checklist_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id BIGINT(20) UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            is_completed TINYINT(1) DEFAULT 0,
            completed_by BIGINT(20) UNSIGNED DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY task_id (task_id),
            KEY completed_by (completed_by)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Execute SQL
        dbDelta($user_departments_sql);
        dbDelta($projects_sql);
        dbDelta($sql);
        dbDelta($task_links_sql);
        dbDelta($tasks_templates_sql);
        dbDelta($bonus_sql);
        dbDelta($chat_sql);
        dbDelta($notifications_sql);
        dbDelta($project_users_sql);
        dbDelta($milestone_sql);
        dbDelta($assignee_sql);
        dbDelta($checklist_sql);


        // Check if tables exist

        // Create departments table
        if (!self::createDepartmentsTable()) {
            error_log('DatabaseHandler: Failed to create departments table.');
            return false;
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$user_departments_table'") !== $user_departments_table) {
            error_log('GoalForge: Failed to create user Department table.');
            return false;
        }
            
        if ($wpdb->get_var("SHOW TABLES LIKE '$projects_table'") !== $projects_table) {
            error_log('GoalForge: Failed to create projects table.');
            return false;
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$tasks_table'") !== $tasks_table) {
            error_log('GoalForge: Failed to create tasks table.');
            return false;
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$task_links_table'") !== $task_links_table) {
            error_log('GoalForge: Failed to create tasks links history table.');
            return false;
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$tasks_templates_table'") !== $tasks_templates_table) {
            error_log('GoalForge: Failed to create tasks template table.');
            return false;
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$project_users_table'") !== $project_users_table) {
            error_log('GoalForge: Failed to create project users table.');
            return false;
        }
         if ($wpdb->get_var("SHOW TABLES LIKE '$milestone_table'") !== $milestone_table) {
            error_log('GoalForge: Failed to create milestone table.');
            return false;
        }
         if ($wpdb->get_var("SHOW TABLES LIKE '$assignee_table'") !== $assignee_table) {
            error_log('GoalForge: Failed to create assignee table.');
            return false;
        }
         if ($wpdb->get_var("SHOW TABLES LIKE '$checklist_table'") !== $checklist_table) {
            error_log('GoalForge: Failed to create assignee table.');
            return false;
        }

        return true;
    }

    /**
     * Drop plugin-specific database tables during uninstallation.
     *
     * @return bool
     */
    public static function dropTables(): bool
    {
        global $wpdb;
    
        // Drop tables in reverse order of dependencies
        $tables = [
            $wpdb->prefix . 'goalforge_project_bonuses', // Dependent table
            $wpdb->prefix . 'goalforge_task_links',     // Dependent table
            $wpdb->prefix . 'goalforge_chat',
            $wpdb->prefix . 'goalforge_notifications',
            $wpdb->prefix . 'goalforge_task_templates', // Independent table
            $wpdb->prefix . 'goalforge_tasks',          // Independent table
            $wpdb->prefix . 'goalforge_user_departments',    // Independent table
            $wpdb->prefix . 'goalforge_projects',       // Primary table
             $wpdb->prefix . '$project_users_table',
             $wpdb->prefix . '$milestone_table',
             $wpdb->prefix . '$assignee_table'
        ];
    
        foreach ($tables as $table_name) {
            // Verify table existence
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                // Drop table
                $wpdb->query("SET FOREIGN_KEY_CHECKS=0;"); // Temporarily disable foreign key checks
                $wpdb->query("DROP TABLE IF EXISTS $table_name");
                $wpdb->query("SET FOREIGN_KEY_CHECKS=1;"); // Re-enable foreign key checks
    
                // Verify table was dropped
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                    error_log("GoalForge: Failed to drop table '$table_name'.");
                    return false;
                }
            } else {
                error_log("GoalForge: Table '$table_name' does not exist.");
            }
        }
    
        return true;
    }
    

    /**
     * Handle database schema updates.
     *
     * @param string $new_version
     * @return bool
     */
    public static function updateSchema(string $new_version): bool
    {
        // Placeholder: Add schema migration logic if required
        return true;
    }

    /**
     * Execute raw SQL queries.
     *
     * @param string $query
     * @return bool
     */
    private static function executeQuery(string $query): bool
    {
        global $wpdb;

        $result = $wpdb->query($query);
        if ($result === false) {
            error_log('GoalForge: Failed to execute query - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    
}
