<?php

namespace BemaGoalForge\Dashboard;

use wpdb;

class UserDashboardController
{
  public static function renderUserDashboard()
{
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view your dashboard.</p>';
    }

    $user_id = get_current_user_id();
global $wpdb;

$tasks_table      = "{$wpdb->prefix}goalforge_tasks";
$projects_table = "{$wpdb->prefix}goalforge_projects";
$project_users_table = "{$wpdb->prefix}goalforge_project_users";
$milestones_table = "{$wpdb->prefix}goalforge_milestones";
$checklist_table  = "{$wpdb->prefix}goalforge_task_checklists";
$assignees_table = "{$wpdb->prefix}goalforge_task_assignees";
$users_table      = "{$wpdb->prefix}users";

// Get tasks assigned to user with milestone
$tasks = $wpdb->get_results("
    SELECT t.*, m.title AS milestone_title
    FROM {$wpdb->prefix}goalforge_tasks t
    INNER JOIN {$wpdb->prefix}goalforge_task_assignees ta ON ta.task_id = t.id
    LEFT JOIN {$wpdb->prefix}goalforge_milestones m ON t.milestone_id = m.id
    WHERE ta.user_id = $user_id
    ORDER BY m.title ASC
");


// Get collaborators
$collaborators = $wpdb->get_results("
SELECT DISTINCT u.ID, u.display_name, p.title AS project_title
FROM $project_users_table pu
INNER JOIN $projects_table p ON pu.project_id = p.id
INNER JOIN $tasks_table t ON t.project_id = p.id
INNER JOIN $assignees_table a ON a.task_id = t.id
INNER JOIN $users_table u ON pu.user_id = u.ID
WHERE a.user_id = $user_id AND u.ID != $user_id
");

// Get checklist items
$task_ids = wp_list_pluck($tasks, 'id');
$checklists = [];
if (!empty($task_ids)) {
    $in_clause = implode(',', array_map('intval', $task_ids));
    $results = $wpdb->get_results("SELECT * FROM $checklist_table WHERE task_id IN ($in_clause)", ARRAY_A);
    foreach ($results as $item) {
        $checklists[$item['task_id']][] = $item;
    }
}
//get bonuses
$project_ids = $wpdb->get_col(
$wpdb->prepare("SELECT project_id FROM $project_users_table WHERE user_id = $user_id")
);

$bonuses = [];
if (!empty($project_ids)) {
$placeholders = implode(',', array_fill(0, count($project_ids), '%d'));
$sql = "
SELECT b.*, p.title AS project_title
FROM {$wpdb->prefix}goalforge_project_bonuses b
INNER JOIN $projects_table p ON b.project_id = p.id
WHERE b.project_id IN ($placeholders)
ORDER BY b.created_at DESC
";
$query = $wpdb->prepare($sql, ...$project_ids);
$bonuses = $wpdb->get_results($query);
}


ob_start();
?>
<style>
    .goalforge-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .goalforge-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .goalforge-card h3 {
        margin-top: 0;
    }
    .checklist-item input {
        margin-right: 8px;
    }
    .goalforge-progress-bar {
        background: #eee;
        border-radius: 5px;
        overflow: hidden;
        margin: 10px 0;
    }
    .goalforge-progress {
        background: #4caf50;
        height: 10px;
        width: 0;
        transition: width 0.3s ease;
    }
    .goalforge-task {
        margin-bottom: 20px;
    }
</style>

<div class="goalforge-dashboard-grid">
    <?php
    // echo '<pre>Assigned Task Count: ' . count($tasks) . '</pre>';

    $milestoneGroups = [];
    foreach ($tasks as $task) {
        $milestoneGroups[$task->milestone_title][] = $task;
    }

    foreach ($milestoneGroups as $milestone => $groupedTasks) : ?>
        <div class="goalforge-card">
            <h3><?php echo esc_html($milestone); ?></h3>
            <?php foreach ($groupedTasks as $task) :
                $taskChecklist = $checklists[$task->id] ?? [];
                $totalItems = count($taskChecklist);
                $completedItems = count(array_filter($taskChecklist, fn($c) => $c['is_completed']));
                $percent = $totalItems ? round(($completedItems / $totalItems) * 100) : 0;
                ?>
                <div class="goalforge-task">
                    <strong><?php echo esc_html($task->title); ?></strong>

                    <?php if ($totalItems > 0) : ?>
                        <div class="goalforge-progress-bar">
                            <div class="goalforge-progress" style="width: <?php echo esc_attr($percent); ?>%;"></div>
                        </div>
                        <small><?php echo "$completedItems of $totalItems checklist items completed ($percent%)"; ?></small>
                    <?php endif; ?>

                    <?php if (!empty($taskChecklist)) : ?>
                        <ul class="checklist">
                            <?php foreach ($taskChecklist as $item) : ?>
                                <li class="checklist-item">
                                    <label>
                                        <input type="checkbox" data-id="<?php echo $item['id']; ?>" <?php checked($item['is_completed'], 1); ?> disabled>
                                        <?php echo esc_html($item['title']); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <em>No checklist items.</em>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="goalforge-card">
        <h3>Collaborators</h3>
        <?php if (!empty($collaborators)) : ?>
            <ul>
                <?php foreach ($collaborators as $collab) : ?>
                    <li><?php echo esc_html($collab->display_name . ' (' . $collab->project_title . ')'); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>No collaborators found.</p>
        <?php endif; ?>
    </div>
    <div class="goalforge-card"> <h3>Bonuses</h3> <?php if (!empty($bonuses)) : ?> 
        <ul> <?php foreach ($bonuses as $bonus) : ?> 
            <li>                 
                 - <em><?php echo number_format($bonus->bonus_amount, 2); ?> pts</em><br> 
                 <small><?php echo date('M d, Y', strtotime($bonus->created_at)); ?></small> 
                </li> <?php endforeach; ?> </ul> 
                <?php else : ?> 
        <p>No bonuses yet.</p> <?php endif; ?> </div>
</div>
<?php
return ob_get_clean();

}

}
