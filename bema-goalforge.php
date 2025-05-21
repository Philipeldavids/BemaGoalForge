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

        // Enqueue frontend form styles 
        
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']); 
        // Register shortcode 
        add_shortcode('goalforge_user_dashboard', ['BemaGoalForge\Dashboard\UserDashboardController', 'renderUserDashboard']);

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