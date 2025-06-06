<?php 
/** * Plugin Name: Bema GoalForge * 
 * Description: A plugin for managing tasks, projects, and rewarding achievements. * 
 * Version: 1.0.0 * 
 * Author: Philip Ayanfe* 
 * Text Domain: goalforge * 
 * Author URI: https://bemaisltd.com * 
 * License: GPL2 */ 
namespace BemaGoalForge; 
if (!defined('ABSPATH')) { 
    exit; // Exit if accessed directly 
    } 

use BemaGoalForge\BonusManagement\BonusAjaxHandler; 
use BemaGoalForge\Core\PluginActivator; 
use BemaGoalForge\Core\PluginDeactivator; 
use BemaGoalForge\Core\PluginUninstaller; 
use BemaGoalForge\Dashboard\AdminDashboardController; 
use BemaGoalForge\Feedback\ChatController;
use BemaGoalForge\Dashboard\PublicDashboardController; 
use BemaGoalForge\Utilities\Logger; 
use BemaGoalForge\Utilities\Tester; 

final class GoalForge { 
    
    private static ?GoalForge $instance = null; private const VERSION = '1.0.0'; 
    private ?Logger $logger = null; 
    public static function instance(): GoalForge { 
        if (is_null(self::$instance)) 
        { self::$instance = new self(); } 
        return self::$instance; } 
    private function __construct() { 
        try { 
            $this->defineConstants(); 
            $this->initializeAutoloader(); 
            $this->createLogFolder(); 
            
            $this->logger = new Logger(); 
            
            $this->initializeHooks(); 
        } catch (\Exception $e) { 
            if (isset($this->logger)) { 
                $this->logger->logError('Initialization Error: ' . $e->getMessage()); 
            } else { 
                error_log('GoalForge Initialization Error: ' . $e->getMessage()); 
            } wp_die(__('An error occurred while initializing GoalForge. Please check the error log.', 'goalforge')); 
        } 
    } 
    private function defineConstants() { 
        define('GOALFORGE_VERSION', self::VERSION); 
        define('GOALFORGE_MAIN_FILE', __FILE__); 
        define('GOALFORGE_PATH', plugin_dir_path(__FILE__)); 
        define('GOALFORGE_URL', plugin_dir_url(__FILE__)); 
        define('GOALFORGE_ASSETS', GOALFORGE_URL . 'assets/'); 
    } 
    private function initializeAutoloader() { 
        require_once GOALFORGE_PATH . 'includes/autoloader.php'; 
    } 
    private function createLogFolder() { 
        $logDir = GOALFORGE_PATH . 'logs'; 
        if (!is_dir($logDir)) {
             if (!mkdir($logDir, 0755, true)) {
                 error_log('GoalForge: Failed to create log directory.'); 
                } 
            } 
            define('GOALFORGE_LOG_DIR', $logDir . '/'); 
    } 
    private function initializeHooks() { 
        require_once plugin_dir_path(__FILE__) . 'includes/Core/PluginActivator.php'; 
        require_once plugin_dir_path(__FILE__) . 'includes/Database/DatabaseHandler.php'; 

        register_activation_hook(GOALFORGE_MAIN_FILE, [PluginActivator::class, 'activate']); 
        register_deactivation_hook(GOALFORGE_MAIN_FILE, [PluginDeactivator::class, 'deactivate']); 
        register_uninstall_hook(GOALFORGE_MAIN_FILE, [PluginUninstaller::class, 'uninstall']); 
        
        add_action('plugins_loaded', [$this, 'init']); 
        add_action('admin_enqueue_scripts', ['BemaGoalForge\Dashboard\AdminDashboardController', 'enqueueAdminScripts']); 
        add_action('plugins_loaded', function () { 
            if (is_admin()) { 
                AdminDashboardController::init(); 
            } else { 
                PublicDashboardController::init(); 
            } 
        }); 
        //Handle link task to project

        add_action('admin_post_goalforge_link_task_to_project', ['\BemaGoalForge\Dashboard\AdminDashboardController', 'handleLinkTaskToProject']);
       
        //Handle project submission from admin dashboard


        // Handle project form submission from frontend 
        add_action('admin_post_goalforge_add_project', function () { 
        if (!current_user_can('edit_posts')) { 
            wp_die('Unauthorized'); 
        } 
        if (!isset($_POST['goalforge_nonce']) || !wp_verify_nonce($_POST['goalforge_nonce'], 'goalforge_create_project_action')) {
        wp_die('Nonce verification failed');
}

            $projectData = [ 
                'title'         => sanitize_text_field($_POST['goalforge_title']), 
                'description'   => sanitize_textarea_field($_POST['goalforge_description']), 
                'start_date'    => sanitize_text_field($_POST['goalforge_start_date']), 
                'due_date'      => sanitize_text_field($_POST['goalforge_due_date']), 
                'created_by'    => get_current_user_id(),
                'reminder_time' => sanitize_text_field($_POST['project_reminder_time']),
                'department' => sanitize_text_field($_POST['goalforge_department']),
                'status' => sanitize_text_field($_POST['goalforge_status'])  
             ];

            $controller = new \BemaGoalForge\ProjectManagement\ProjectController(); 
            $result = $controller->createProject($projectData); 
            if ($result) { 
                wp_redirect(add_query_arg(['project_status' => 'success'], wp_get_referer())); 
            } else { 
                wp_redirect(add_query_arg(['project_status' => 'error'], wp_get_referer())); 
            } 
            exit; 
        }); 

        //update task
        add_action('admin_post_goalforge_update_task', ['BemaGoalForge\Dashboard\AdminDashboardController', 'handleUpdateTask']);        

        //delete project
        add_action('admin_post_goalforge_delete_project', ['BemaGoalForge\ProjectManagement\ProjectController', 'delete_project']);
    // Update project        
            add_action('admin_post_goalforge_update_project', function () {
        if (!current_user_can('edit_posts')) wp_die('Unauthorized');
        check_admin_referer('goalforge_update_project_action', 'goalforge_nonce');

        $id = intval($_POST['project_id']);

        $data = [
            'title' => sanitize_text_field($_POST['goalforge_title']),
            'description' => sanitize_textarea_field($_POST['goalforge_description']),
            'start_date' => sanitize_text_field($_POST['goalforge_start_date']),
            'due_date' => sanitize_text_field($_POST['goalforge_due_date']),
            'reminder_time' => sanitize_text_field($_POST['project_reminder_time']),
            'department' => sanitize_text_field($_POST['goalforge_department']),
            'status' => sanitize_text_field($_POST['goalforge_status'])
        ];

        $controller = new \BemaGoalForge\ProjectManagement\ProjectController();
        $result = $controller->updateProject($id, $data);

        $status = $result ? 'updated' : 'error';
        // wp_redirect(admin_url('admin.php?page=goalforge_project_list&project_status=' . $status));
        // exit;
        wp_redirect(add_query_arg(['project_status' => $result ? 'updated' : 'error'], admin_url("admin.php?page=goalforge_edit_project&id={$id}")));
        exit;
    });

    //assign collaborators
    add_action('admin_post_goalforge_assign_collaborators', function () {
        if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
        }

        if (
            !isset($_POST['project_id'], $_POST['collaborators'], $_POST['_wpnonce']) ||
            !wp_verify_nonce($_POST['_wpnonce'], 'goalforge_assign_collaborators_nonce')
        ) {
            wp_redirect(add_query_arg(['assign_status' => 'error'], wp_get_referer()));
            exit;
        }

        $project_id = intval($_POST['project_id']);
        $collaborators = array_map('intval', (array) $_POST['collaborators']);

        $controller = new \BemaGoalForge\ProjectManagement\ProjectController();
        $success = $controller->updateCollaborators($project_id, $collaborators);
        foreach($collaborators as $collaboratorId){
             Notification\NotificationModel::send_assignment_email($collaboratorId, 'project', $project_id);
        }
       

        wp_redirect(add_query_arg(['assign_status' => $success ? 'success' : 'error'], wp_get_referer()));
        exit;
    });
   
    // Chat bot 
   add_action('wp_ajax_goalforge_chatbot_query', ['BemaGoalForge\Feedback\ChatController', 'handleChatbotQuery']);
    add_action('wp_ajax_nopriv_goalforge_chatbot_query', ['BemaGoalForge\Feedback\ChatController', 'handleChatbotQuery']);


    //Assign user to task
    add_action('admin_post_goalforge_assign_user_to_task', ['BemaGoalForge\TaskManagement\TaskController', 'assignUserToTask']);

    // Milestone handler for add
    add_action('admin_post_goalforge_add_milestone', ['BemaGoalForge\MilestoneManagement\MilestoneController', 'goalforge_handle_add_milestone']);

    // Milestone handler for delete
    add_action('admin_post_goalforge_delete_milestone', ['BemaGoalForge\MilestoneManagement\MilestoneController','goalforge_handle_delete_milestone']);

    //Milestone handler for Edit
    add_action('admin_post_goalforge_update_milestone', ['BemaGoalForge\MilestoneManagement\MilestoneController','goalforge_handle_update_milestone']);
     
     //Delete checklist
     add_action('admin_post_delete_checklist', ['BemaGoalForge\ChecklistManagement\ChecklistController', 'handleDeleteChecklist']);
   
    //edit checklist
    add_action('admin_post_edit_checklist', ['BemaGoalForge\ChecklistManagement\ChecklistController', 'handleEditChecklist']);
     //create checklist
    add_action('admin_post_create_checklist', ['BemaGoalForge\ChecklistManagement\ChecklistController', 'handleCreateChecklist']);
    //create task
    add_action('admin_post_goalforge_create_task', function () {
        if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
        }
        check_admin_referer('goalforge_create_task_action', 'goalforge_task_nonce');

       $taskData = [
                'title' => sanitize_text_field($_POST['task_title']),
                'description' => sanitize_textarea_field($_POST['task_description']),
                'start_date' => sanitize_text_field($_POST['task_start_date']),
                'due_date' => sanitize_text_field($_POST['task_due_date']),
                'reminder_time' => sanitize_text_field($_POST['task_reminder_time']),
                'milestone_id' => intval($_POST['milestone_id']),
                'project_id' => intval($_POST['project_id']),
                'created_by' => get_current_user_id(),
                'status' => sanitize_text_field($_POST['task_status'])
            ];

            $controller = new \BemaGoalForge\TaskManagement\TaskController(); 
            $success = $controller->createTask($taskData); 

        
        $redirect = add_query_arg(
            ['task_status' => $success ? 'success' : 'error'],
            admin_url('admin.php?page=goalforge_create_task')
        );
        wp_redirect($redirect);
        exit;

        });
        //update checklists

        add_action('wp_ajax_goalforge_toggle_checklist', function () {
    check_ajax_referer('goalforge_checklist_nonce', 'nonce');

    $user_id = get_current_user_id();
    $checklist_id = intval($_POST['checklist_id']);
    $is_completed = intval($_POST['is_completed']);

    global $wpdb;
    $checklist_table = "{$wpdb->prefix}goalforge_task_checklists";

    // Confirm user is assigned to the task
    $task_id = $wpdb->get_var($wpdb->prepare("SELECT task_id FROM $checklist_table WHERE id = %d", $checklist_id));
    $is_assigned = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}goalforge_task_assignees WHERE task_id = %d AND user_id = %d",
        $task_id, $user_id
    ));

    if (!$is_assigned) {
        wp_send_json_error(['message' => 'Not authorized.']);
    }

    $updated = $wpdb->update(
        $checklist_table,
        ['is_completed' => $is_completed],
        ['id' => $checklist_id],
        ['%d'],
        ['%d']
    );

    if ($updated !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'DB update failed.']);
    }
});

        //Add Comments and Update status handlers

        add_action('wp_ajax_goalforge_update_task_status',  function () {
            global $wpdb;
            $task_id = intval($_POST['task_id']);
            $status = sanitize_text_field($_POST['status']);
            $wpdb->update("{$wpdb->prefix}goalforge_tasks", ['status' => $status], ['id' => $task_id]);
            wp_send_json_success();
        });
       

        add_action('wp_ajax_goalforge_add_task_comment', function () {
           global $wpdb;
            $table = $wpdb->prefix . 'goalforge_task_comments';

            $task_id = intval($_POST['task_id']);
            $user_id = get_current_user_id();
            $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $content = sanitize_text_field($_POST['content']);

            $wpdb->insert($table, [
                'task_id' => $task_id,
                'user_id' => $user_id,
                'parent_id' => $parent_id,
                'content' => $content,
            ]);

            wp_send_json_success(['comment' => $content]);

        });

        //edit comment 

        add_action('wp_ajax_goalforge_edit_comment', function () {
        global $wpdb;
        $comment_id = intval($_POST['comment_id']);
        $new_content = sanitize_text_field($_POST['content']);
        $user_id = get_current_user_id();
            $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}goalforge_task_comments WHERE id = %d", $comment_id));

            if ($comment && $comment->user_id == $user_id) {
                $wpdb->update("{$wpdb->prefix}goalforge_task_comments", ['content' => $new_content], ['id' => $comment_id]);
                wp_send_json_success(['message' => 'Comment updated.']);
            } else {
                wp_send_json_error(['message' => 'Unauthorized or comment not found.']);
            }

        });

        
        add_action('wp_ajax_goalforge_delete_comment', function() {
        global $wpdb;
        $comment_id = intval($_POST['comment_id']);
        $user_id = get_current_user_id();

        $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}goalforge_task_comments WHERE id = %d", $comment_id));

        if ($comment && $comment->user_id == $user_id) {
            $wpdb->delete("{$wpdb->prefix}goalforge_task_comments", ['id' => $comment_id]);
            wp_send_json_success(['message' => 'Comment deleted.']);
        } else {
            wp_send_json_error(['message' => 'Unauthorized or comment not found.']);
        }

        });

        
                //status and comments enqueue
        add_action('wp_enqueue_scripts', function() {
    // Check if shortcode is used on the current page
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'goalforge_user_dashboard')) {
            wp_enqueue_script('goalforge-user-dashboard-js', plugin_dir_url(__FILE__) . 'assets/js/goalforge-user-dashboard.js', ['jquery'], '1.0', true);

            // Provide ajaxurl to script
           // wp_localize_script('goalforge-user-dashboard-js', 'ajaxurl', admin_url('admin-ajax.php'));
            wp_localize_script('goalforge-user-dashboard-js', 'goalforge_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('goalforge_checklist_nonce')
            ]);
        }
    });

//     add_action('wp_enqueue_scripts', function () {
//     wp_enqueue_script('goalforge-frontend', plugin_dir_url(__FILE__) . 'assets/js/goalforge-frontend.js', ['jquery'], '1.0', true);
   
// });

        
   


        //delete task

        add_action('admin_post_goalforge_delete_task', function () {
            if (
                !current_user_can('manage_options') ||
                !isset($_GET['task_id']) ||
                !wp_verify_nonce($_GET['_wpnonce'], 'goalforge_delete_task_action')
            ) {
                wp_die('Unauthorized action.');
            }

            global $wpdb;
            $task_id = intval($_GET['task_id']);

            $deleted = $wpdb->delete("{$wpdb->prefix}goalforge_tasks", ['id' => $task_id]);

             

            wp_redirect(admin_url('admin.php?page=goalforge_create_task'));
            exit;
        });

        // Hook the cron task callback

        add_filter('cron_schedules', function ($schedules) {
            $schedules['five_minutes'] = [
                'interval' => 5 * 60, // 5 minutes in seconds
                'display'  => __('Every Five Minutes')
            ];
            return $schedules;
        });

        add_action('goalforge_cron_task', ['BemaGoalForge\TaskManagement\Scheduler', 'runScheduledTask']);

        // Enqueue frontend form styles 
        
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']); 
        // Register shortcode for user view
        add_shortcode('goalforge_user_dashboard', ['BemaGoalForge\Dashboard\UserDashboardController', 'renderUserDashboard']);
        //Register shortcode for chatbot

        add_shortcode('goalforge_chatbot', ['BemaGoalForge\Feedback\ChatController', 'renderChatbot']);

        add_shortcode('goalforge_project_form', ['\BemaGoalForge\ProjectManagement\Shortcodes', 'renderProjectForm']); 
        
        add_shortcode('goalforge_project_dashboard', ['\BemaGoalForge\Shortcodes\ShortcodeRenderer', 'renderProjectDashboard']); 
        
        add_action('admin_post_goalforge_assign_bonus_to_project', ['\BemaGoalForge\BonusManagement\BonusAjaxHandler', 'assignBonusToProject']);
        add_action('wp_ajax_goalforge_fetch_bonus', ['\BemaGoalForge\BonusManagement\BonusAjaxHandler', 'fetchProjectBonus']);
         
        // Debug 
        add_action('admin_enqueue_scripts', function ($hook) { 
            error_log("Current hook: {$hook}"); }); 
    } 
        public function enqueueFrontendAssets() { 
            wp_enqueue_style( 'goalforge-project-form', plugin_dir_url(__FILE__) . 'assets/css/project-form.css', [], self::VERSION ); 
        
        } 
        public function init() { 
            $pluginInitializer = new \BemaGoalForge\Core\PluginInitializer(); 
            $pluginInitializer->initializePlugin(); 
        } 
    } 
    GoalForge::instance();