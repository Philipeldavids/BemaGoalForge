<?php

namespace BemaGoalForge\ChecklistManagement;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use BemaGoalForge\ChecklistManagement\ChecklistModel;

class ChecklistController
{
    /**
     * Adds a checklist to a task.
     *
     * @param array $checklistData
     * @return bool
     */
    public function addChecklist(array $checklistData): bool
    {
        if (empty($checklistData['task_id']) || empty($checklistData['steps'])) {
            error_log("GoalForge: Missing task ID or steps in addChecklist.");
            return false;
        }

        $checklistModel = new ChecklistModel();
        return $checklistModel->saveChecklist($checklistData);
    }

    /**
     * Updates an existing checklist.
     *
     * @param int $checklistId
     * @param array $updatedData
     * @return bool
     */
    public function updateChecklist(int $checklistId, array $updatedData): bool
    {
        if (empty($checklistId) || empty($updatedData)) {
            error_log("GoalForge: Missing checklist ID or data in updateChecklist.");
            return false;
        }

        $checklistModel = new ChecklistModel();
        return $checklistModel->updateChecklist($checklistId, $updatedData);
    }

     public static function renderManageChecklists()
        {
           global $wpdb;
        $checklist_table = $wpdb->prefix . 'goalforge_task_checklists';
        $task_table = $wpdb->prefix . 'goalforge_tasks';
            // Fetch tasks for select dropdown
        $tasks = $wpdb->get_results("SELECT id, title FROM $task_table");

// Handle messages
if (isset($_GET['created'])) {
    echo '<div class="notice notice-success"><p>Checklist created successfully.</p></div>';
} elseif (isset($_GET['updated'])) {
    echo '<div class="notice notice-success"><p>Checklist updated successfully.</p></div>';
} elseif (isset($_GET['deleted'])) {
    echo '<div class="notice notice-success"><p>Checklist deleted successfully.</p></div>';
}

?>

<div class="wrap">
    <h1>Manage Checklists</h1>

    <!-- Create Checklist Form -->
    <h2>Create New Checklist Item</h2>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom: 30px;">
        <?php wp_nonce_field('create_checklist_action'); ?>
        <input type="hidden" name="action" value="create_checklist">

        <table class="form-table">
            <tr>
                <th><label for="task_id">Task</label></th>
                <td>
                    <select name="task_id" id="task_id" required>
                        <option value="">Select Task</option>
                        <?php foreach ($tasks as $task): ?>
                            <option value="<?php echo esc_attr($task->id); ?>"><?php echo esc_html($task->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="title">Checklist Title</label></th>
                <td><textarea name="title" id="title" rows="2" style="width: 100%;" required></textarea></td>
            </tr>
        </table>

        <?php submit_button('Create Checklist'); ?>
    </form>

    <!-- Existing Checklist Table -->
    <h2>All Checklist Items</h2>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Task</th>
                <th>Title</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $checklists = $wpdb->get_results(
                "SELECT c.*, t.title AS task_title
                 FROM $checklist_table c 
                 LEFT JOIN $task_table t ON c.task_id = t.id"
            );

            if ($checklists) :
                foreach ($checklists as $item) :
                    ?>
                    <tr>
                        <td><?php echo esc_html($item->id); ?></td>
                        <td><?php echo esc_html($item->task_title); ?></td>
                        <td><?php echo esc_html($item->title); ?></td>
                        <td><?php echo $item->is_completed ? '✅ Completed' : '❌ Incomplete'; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=edit-checklist&id=' . $item->id); ?>" class="button button-small">Edit</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=delete_checklist&id=' . $item->id), 'delete_checklist_action'); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this checklist?')">Delete</a>
                        </td>
                    </tr>
                    <?php
                endforeach;
            else :
                echo '<tr><td colspan="5">No checklists found.</td></tr>';
            endif;
            ?>
        </tbody>
    </table>
</div>

<?php

        }

  public static function handleCreateChecklist()
{
if (!current_user_can('manage_options')) {
wp_die('Unauthorized');
}
if (!check_admin_referer('create_checklist_action')) {
    wp_die('Invalid nonce');
}

global $wpdb;

$task_id = intval($_POST['task_id'] ?? 0);
$title = sanitize_text_field($_POST['title'] ?? '');

if ($task_id && $title) {
$result = $wpdb->insert(
$wpdb->prefix . 'goalforge_task_checklists',
[
'task_id' => $task_id,
'title' => $title,
'is_completed'=> 0,
'created_at' => current_time('mysql'),
'updated_at' => current_time('mysql'),
]
);
    if($result){
        wp_redirect(admin_url('admin.php?page=create-checklist&created=1'));
    }
     else {
    wp_redirect(admin_url('admin.php?page=create-checklist&error=1'));
}
} else {
    wp_redirect(admin_url('admin.php?page=create-checklist&error=1'));
   
}
exit;

}      

public static function renderEditChecklist()
{
global $wpdb;
$id = intval($_GET['id'] ?? 0);
$item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}goalforge_task_checklists WHERE id = %d", $id));
if (!$item) {
    echo '<div class="notice notice-error"><p>Checklist not found.</p></div>';
    return;
}

?>
<div class="wrap">
    <h1>Edit Checklist</h1>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('edit_checklist_action'); ?>
        <input type="hidden" name="action" value="edit_checklist">
        <input type="hidden" name="id" value="<?php echo esc_attr($item->id); ?>">

        <table class="form-table">
            <tr>
                <th><label for="title">Description</label></th>
                <td><textarea name="title" id="title" rows="3" required><?php echo esc_textarea($item->title); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="is_completed">Completed?</label></th>
                <td>
                    <input type="checkbox" name="is_completed" value="1" <?php checked($item->is_completed); ?> />
                </td>
            </tr>
        </table>

        <?php submit_button('Update Checklist'); ?>
    </form>
</div>
<?php

}

public static function handleEditChecklist()
{
if (!current_user_can('manage_options')) {
wp_die('Unauthorized');
}
if (!check_admin_referer('edit_checklist_action')) {
    wp_die('Invalid nonce');
}

global $wpdb;

$id = intval($_POST['id'] ?? 0);
$title = sanitize_text_field($_POST['title'] ?? '');
$is_completed = isset($_POST['is_completed']) ? 1 : 0;

$wpdb->update(
    $wpdb->prefix . 'goalforge_task_checklists',
    [
        'title' => $title,
        'is_completed' => $is_completed
    ],
    ['id' => $id]
);

wp_redirect(admin_url('admin.php?page=create-checklist&updated=1'));
exit;

}
        public static function handleDeleteChecklist()
        {
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            if (!isset($_GET['id']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_checklist_action')) {
                wp_die('Invalid request');
            }

            global $wpdb;
            $wpdb->delete("{$wpdb->prefix}goalforge_checklists", ['id' => intval($_GET['id'])]);
            wp_redirect(admin_url('admin.php?page=create-checklist&deleted=1'));
            exit;
        }

}
