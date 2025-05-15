<?php

namespace BemaGoalForge\BonusManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BonusAjaxHandler
{
    /**
     * Initialize the AJAX handler.
     */
    // public static function init()
    // {
    //     add_action('wp_ajax_assign_bonus_to_project', [self::class, 'assignBonusToProject']);
    // }

    /**
     * Handle the AJAX request for assigning a bonus to a project.
     */
public static function assignBonusToProject()
{
    // Verify the nonce for security
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'assign_bonus_to_project_nonce')) {
        wp_redirect(add_query_arg(['page' => 'bonus-assignment', 'error' => 'invalid_nonce'], admin_url('admin.php')));
        exit;
    }

    // Sanitize and validate inputs
    $projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
    $bonusAmount = isset($_POST['bonus_amount']) ? floatval($_POST['bonus_amount']) : 0.0;

    error_log($bonusAmount);
    if ($projectId <= 0) {
        wp_redirect(add_query_arg(['page' => 'bonus-assignment', 'error' => 'invalid_project'], admin_url('admin.php')));
        exit;
    }

    if ($bonusAmount <= 0) {
        wp_redirect(add_query_arg(['page' => 'bonus-assignment', 'error' => 'invalid_bonus'], admin_url('admin.php')));
        exit;
    }

    // Assign the bonus
    $controller = new BonusController();
    $result = $controller->assignBonus($projectId, $bonusAmount);

    if ($result !== false) {
        wp_redirect(add_query_arg(['page' => 'bonus-assignment', 'success' => 1], admin_url('admin.php')));
    } else {
        error_log('GoalForge: Failed to assign bonus.');
        wp_redirect(add_query_arg(['page' => 'bonus-assignment', 'error' => 'assignment_failed'], admin_url('admin.php')));
    }

    exit;
}


    
    public static function fetchProjectBonus()
    {
    if (!check_ajax_referer('assign_bonus_to_project_nonce', '_wpnonce', false)) {
    wp_send_json_error(['message' => 'Invalid nonce.'], 403);
    }

    $projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

    if ($projectId <= 0) {
        wp_send_json_error(['message' => 'Invalid project ID.']);
    }

    global $wpdb;
    $bonus = $wpdb->get_var($wpdb->prepare(
        "SELECT bonus_amount FROM {$wpdb->prefix}goalforge_project_bonuses WHERE project_id = %d",
        $projectId
    ));

    if ($bonus !== null) {
        wp_send_json_success(['bonus_amount' => $bonus]);
    } else {
        wp_send_json_success(['bonus_amount' => 0]);
    }

    }
}
