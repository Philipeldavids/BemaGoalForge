<?php

namespace BemaGoalForge\MilestoneManagement;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class MilestoneController
{

    public static function renderMilestoneManagement() {
    global $wpdb;

    if (isset($_GET['success'])): ?><div class="notice notice-success is-dismissible">
    <p>
        <?php
        switch ($_GET['success']) {
            case 'milestone_added': echo 'Milestone successfully added.'; break;
            case 'milestone_updated': echo 'Milestone successfully updated.'; break;
            case 'milestone_deleted': echo 'Milestone successfully deleted.'; break;
        }
        ?>
    </p>
</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="notice notice-error is-dismissible">
    <p>
        <?php
        switch ($_GET['error']) {
            case 'invalid_nonce': echo 'Security check failed.'; break;
            case 'invalid_input': echo 'Please provide valid input.'; break;
            case 'db_insert_failed': echo 'Could not save milestone.'; break;
            case 'update_failed': echo 'Could not update milestone.'; break;
            case 'delete_failed': echo 'Could not delete milestone.'; break;
            default: echo 'An unknown error occurred.'; break;
        }
        ?>
    </p>
</div>
<?php endif;


   $milestone_table = $wpdb->prefix . 'goalforge_milestones';
$projects_table = $wpdb->prefix . 'goalforge_projects';

// Handle project filter
$filter_project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$project_filter_sql = $filter_project_id > 0 ? $wpdb->prepare("WHERE m.project_id = %d", $filter_project_id) : '';

$milestones = $wpdb->get_results("
    SELECT m.*, p.title AS project_title
    FROM $milestone_table m
    LEFT JOIN $projects_table p ON m.project_id = p.id
    $project_filter_sql
    ORDER BY m.due_date ASC
");

$projects = $wpdb->get_results("SELECT id, title FROM $projects_table", ARRAY_A);

?>
<div class="wrap">
    <h1>Milestone Management</h1>

    <!-- Filter Form -->
    <form method="get">
        <input type="hidden" name="page" value="milestone-management" />
        <label for="project_id">Filter by Project:</label>
        <select name="project_id" id="project_id" onchange="this.form.submit()">
            <option value="">All Projects</option>
            <?php foreach ($projects as $project): ?>
                <option value="<?php echo esc_attr($project['id']); ?>" <?php selected($filter_project_id, $project['id']); ?>>
                    <?php echo esc_html($project['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <h2>Add Milestone</h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="goalforge_add_milestone">
        <?php wp_nonce_field('add_milestone_nonce', '_wpnonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="project_id_add">Project</label></th>
                <td>
                    <select name="project_id" id="project_id_add" required>
                        <option value="">-- Select Project --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo esc_attr($project['id']); ?>"><?php echo esc_html($project['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="title">Title</label></th>
                <td><input type="text" name="title" id="title" required></td>
            </tr>
            <tr>
                <th><label for="description">Description</label></th>
                <td><textarea name="description" id="description" rows="3"></textarea></td>
            </tr>
            <tr>
                <th><label for="milestone-status">Status</label></th>
                <td>
                    <select id="milestone-status" name="milestone_status">
                        <option value="not_started">Not Started</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="due_date">Date</label></th>
                <td><input type="datetime-local" name="due_date" id="due_date" required></td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary">Add Milestone</button></p>
    </form>

    <h2>Existing Milestones</h2>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>Project</th>
                <th>Title</th>
                <th>Description</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($milestones): ?>
                <?php foreach ($milestones as $ms): ?>
                    <tr>
                        <td><?php echo esc_html($ms->project_title); ?></td>
                        <td><?php echo esc_html($ms->title); ?></td>
                        <td><?php echo esc_html($ms->description); ?></td>
                        <td><?php echo esc_html(date('Y-m-d', strtotime($ms->due_date))); ?></td>
                        <td><?php $status_class = match ($ms->status) 
                        { 
                            'in_progress' => 'goalforge-status-in-progress', 
                            'done' => 'goalforge-status-done', 
                            default => 'goalforge-status-not-started', 
                        }; $status_label = match ($ms->status) 
                        { 'in_progress' => 'In Progress', 
                            'done' => 'Done', 
                            default => 'Not Started',
                         }; 
                        echo '<span class="goalforge-status-badge ' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>'; ?>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=goalforge_edit_milestone&id=' . $ms->id); ?>" class="button small">Edit</a>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                <?php wp_nonce_field('delete_milestone_' . $ms->id); ?>
                                <input type="hidden" name="action" value="goalforge_delete_milestone">
                                <input type="hidden" name="milestone_id" value="<?php echo esc_attr($ms->id); ?>">
                                <button type="submit" class="button small" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No milestones found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<style> .goalforge-status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; color: white; font-weight: bold; } 
.goalforge-status-not-started { background-color: #999; } 
.goalforge-status-in-progress { background-color: #f39c12; } 
.goalforge-status-done { background-color: #27ae60; } </style>
<?php

}

public static function goalforge_render_edit_milestone_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'goalforge_milestones';
    $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}goalforge_projects", ARRAY_A);

    $id = intval($_GET['id'] ?? 0);
    $milestone = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

    if (!$milestone) {
        echo '<div class="notice notice-error"><p>Milestone not found.</p></div>';
        return;
    }

    ?>
    <div class="wrap">
        <h1>Edit Milestone</h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="goalforge_update_milestone">
            <input type="hidden" name="milestone_id" value="<?php echo esc_attr($id); ?>">
            <?php wp_nonce_field('update_milestone_' . $id); ?>
            <table class="form-table">
                <tr>
                    <th><label for="project_id">Project</label></th>
                    <td>
                        <select name="project_id" id="project_id">
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo esc_attr($project['id']); ?>" <?php selected($milestone->project_id, $project['id']); ?>>
                                    <?php echo esc_html($project['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="title">Title</label></th>
                    <td><input type="text" name="title" id="title" value="<?php echo esc_attr($milestone->title); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td><textarea name="description" id="description"><?php echo esc_textarea($milestone->description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="milestone-status">Status</label></th>
                    <td>
                        <select id="milestone-status" name="milestone_status">
                            <option value="not_started" <?php selected($milestone->status, 'not_started'); ?>>Not Started</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="due_date">Date</label></th>
                    <td><input type="date" name="due_date" id="due_date" value="<?php echo esc_attr($milestone->due_date); ?>" required></td>
                </tr>
            </table>
            <p><button type="submit" class="button button-primary">Update Milestone</button></p>
        </form>
    </div>
    <?php
}

public static function goalforge_handle_add_milestone() {
    if (!current_user_can('manage_options') || !check_admin_referer('add_milestone_nonce')) {
        wp_die('Unauthorized request.');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'goalforge_milestones';

    $project_id = intval($_POST['project_id'] ?? 0);
    $title = sanitize_text_field($_POST['title'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $due_date = sanitize_text_field($_POST['due_date'] ?? '');
    $status = sanitize_text_field($_POST['milestone_status'] ?? 'not_started');

    if ($project_id <= 0 || empty($title)) {
    wp_redirect(add_query_arg('error', 'invalid_input', wp_get_referer()));
    exit;
}

    if ($project_id && $title && $due_date) {
        $wpdb->insert($table, [
            'project_id' => $project_id,
            'title' => $title,
            'description' => $description,
            'due_date' => $due_date,
            'status' => $status
        ]);
    }

   if ($inserted !== false) {
    wp_redirect(add_query_arg('success', 'milestone_added', wp_get_referer()));
} else {
    wp_redirect(add_query_arg('error', 'db_insert_failed', wp_get_referer()));
}
exit;
}


public static function goalforge_handle_delete_milestone() {
    if (!current_user_can('manage_options') || !isset($_POST['milestone_id'])) {
        wp_die('Unauthorized request.');
    }

    $milestone_id = intval($_POST['milestone_id']);
    if ($milestone_id <= 0) {
    wp_redirect(add_query_arg('error', 'invalid_id', admin_url('admin.php?page=milestone-management')));
    exit;
}
    if (!check_admin_referer('delete_milestone_' . $milestone_id)) {
        wp_die('Invalid nonce.');
    }

    global $wpdb;
    $deleted = $wpdb->delete($wpdb->prefix . 'goalforge_milestones', ['id' => $milestone_id]);

    if ($deleted !== false) {
        wp_redirect(add_query_arg('success', 'milestone_deleted', admin_url('admin.php?page=milestone-management')));
    } else {
        wp_redirect(add_query_arg('error', 'delete_failed', admin_url('admin.php?page=milestone-management')));
    }
    exit;
}


public static function goalforge_handle_update_milestone() {
    $milestone_id = intval($_POST['milestone_id']);
    if (!current_user_can('manage_options') || !check_admin_referer('update_milestone_' . $milestone_id)) {
        wp_die('Unauthorized request.');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'goalforge_milestones';

    $project_id = intval($_POST['project_id']);
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $due_date = sanitize_text_field($_POST['due_date']);
    $status = sanitize_text_field($_POST['milestone_status'] ?? 'not_started');

    if ($milestone_id <= 0 || empty($title)) {
    wp_redirect(add_query_arg('error', 'invalid_input', wp_get_referer()));
    exit;
}

    $wpdb->update($table, [
        'project_id' => $project_id,
        'title' => $title,
        'description' => $description,
        'due_date' => $due_date,
        'status' => $status
    ], ['id' => $milestone_id]);


if ($updated !== false) {
        wp_redirect(add_query_arg('success', 'milestone_updated', admin_url('admin.php?page=milestone-management')));
    } else {
        wp_redirect(add_query_arg('error', 'update_failed', admin_url('admin.php?page=milestone-management')));
    }
exit;
}
}