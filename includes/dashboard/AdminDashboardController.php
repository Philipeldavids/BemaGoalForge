<?php

namespace BemaGoalForge\Dashboard;



if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
use BemaGoalForge\ProjectManagement\ProjectController;
use BemaGoalForge\TaskManagement\TaskController;

class AdminDashboardController {

    public static function init() {
        add_action('admin_menu', [self::class, 'addAdminMenu']);
    }

    public static function addAdminMenu() {
        // Main GoalForge Menu
        add_menu_page(
            'GoalForge Dashboard', 'GoalForge', 'manage_options',
            'goalforge-dashboard', [self::class, 'renderDashboard'], 'dashicons-chart-bar', 2
        );
    
        // Submenu: Link Tasks
        add_submenu_page(
            'goalforge-dashboard', 'Link Tasks to Projects', 'Link Tasks',
            'manage_options', 'task-linking', [self::class, 'renderTaskLinkingForm']
        );
    
        // Submenu: Assign Bonus
        add_submenu_page(
            'goalforge-dashboard', 'Assign Bonus to Projects', 'Assign Bonus',
            'manage_options', 'bonus-assignment', [self::class, 'renderBonusAssignmentForm']
        );
    
        // Submenu: Create Task
        add_submenu_page(
            'goalforge-dashboard', 'Create Task', 'Create Task',
            'manage_options', 'goalforge_create_task', [self::class, 'renderCreateTaskForm']
        );
    
        //Submenu: Create Project
        add_submenu_page(
            'goalforge-dashboard', 'Create Project', 'Create Project',
            'manage_options', 'create-project', [self::class, 'renderCreateProjectForm']
        );

        //Submenu: All Projects
        // add_submenu_page(
        //     'goalforge-dashboard', 'All Projects', 'Create Project',
        //     'manage_options', 'goalforge_project_list', [self::class, 'renderCreateProjectForm']
        // );

        add_submenu_page(
            null, 'Edit Project', 'Edit Project',
            'edit_posts', 'goalforge_edit_project', [self::class, 'renderEditProject']
        );

        add_submenu_page(
            null, 'Assign Collaborators', 'Assign Collaborators',
            'edit_posts', 'goalforge_assign_collaborators', [self::class, 'renderAssignCollaborators']
        );
        add_submenu_page(
            null,'Edit Task', 'Edit Task',
            'edit_posts', 'goalforge_edit_task', [self::class, 'renderEditTaskForm']
        );
        add_submenu_page(
            'goalforge-dashboard', 'Milestone Management','Milestones',
            'manage_options', 'milestone-management', ['BemaGoalForge\MilestoneManagement\MilestoneController', 'renderMilestoneManagement']
            );
        add_submenu_page(
        null, 'Milestone Management','Milestones',
        'edit_posts', 'goalforge_edit_milestone', ['BemaGoalForge\MilestoneManagement\MilestoneController', 'goalforge_render_edit_milestone_page']
        );
        add_submenu_page(
            'goalforge-dashboard',  'Manage Checklists',
            'Checklists', 'manage_options', 'create-checklist', ['BemaGoalForge\ChecklistManagement\ChecklistController', 'renderManageChecklists']
        );
        // add_submenu_page(
        //     null, 'Create Checklist', 'Create Checklist',
        //     'manage_options', 'create-checklist', ['BemaGoalForge\ChecklistManagement\ChecklistController', 'renderManageChecklists']
        //     );
        add_submenu_page(
            null, 'Edit Checklist', 'Edit Checklist',
            'manage_options', 'edit-checklist', ['BemaGoalForge\ChecklistManagement\ChecklistController', 'renderEditChecklist']
            );
    }
    
    

    public static function renderDashboard()
    {
       global $wpdb;

    $tasks_table = $wpdb->prefix . 'goalforge_tasks';
    $projects_table = $wpdb->prefix . 'goalforge_projects';
    $milestone_table = $wpdb->prefix . 'goalforge_milestones';
    $project_users_table = $wpdb->prefix . 'goalforge_project_users';
    $users_table = $wpdb->prefix . 'users';

    // Fetch tasks with project title
    $tasks = $wpdb->get_results("
        SELECT t.*, p.title AS project_title, m.title AS milestone_title
        FROM $tasks_table t
        LEFT JOIN $projects_table p ON t.project_id = p.id
        LEFT JOIN $milestone_table M on t.milestone_id = m.id
    ");

    // Fetch collaborators with project info
    $collaborators = $wpdb->get_results("
        SELECT pu.project_id, u.ID AS user_id, u.display_name, p.title AS project_title
        FROM $project_users_table pu
        LEFT JOIN $users_table u ON pu.user_id = u.ID
        LEFT JOIN $projects_table p ON pu.project_id = p.id
    ");
    

        // Fetch data for analytics (e.g., number of tasks, projects, etc.)
        $taskCount = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}goalforge_tasks");
        $projectCount = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}goalforge_projects");

        ?>
        <div class="wrap"> 
            <h1>GoalForge Dashboard</h1>
            <p>Total Tasks: <?php echo esc_html($taskCount); ?></p>
            <p>Total Projects: <?php echo esc_html($projectCount); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=task-linking'); ?>" class="button button-primary">Link Tasks to Projects</a></p>
            <p><a href="<?php echo admin_url('admin.php?page=bonus-assignment'); ?>" class="button button-primary">Assign Bonuses</a></p>
     

            <div id="goalforge-dashboard-grid"></div>

            <script>
            const goalforgeData = {
                tasks: <?php echo json_encode($tasks); ?>,
                collaborators: <?php echo json_encode($collaborators); ?>
            };
            </script>
            <script>
document.addEventListener("DOMContentLoaded", function () {
    const grid = document.getElementById("goalforge-dashboard-grid");

    if (!goalforgeData || !grid) return;

    const { tasks, collaborators } = goalforgeData;

    const taskSection = document.createElement("div");
    taskSection.innerHTML = `<h2>Tasks</h2>`;
    const taskTable = document.createElement("table");
    taskTable.className = "widefat fixed striped";
    taskTable.innerHTML = `
     <thead> 
     <tr> 
     <th>Title</th> 
     <th>Description</th> 
     <th>Project</th> 
     <th>Milestone</th> 
     <th>Due Date</th> 
     <th>Status</th>
     </tr> 
     </thead>
        <tbody>
            ${tasks.map(task => `
                <tr>
                    <td>${task.title}</td>
                    <td>${task.description || ''}</td>
                    <td>${task.project_title || '—'}</td>
                    <td>${task.milestone_title || '—'}</td>
                    <td>${task.due_date ? new Date(task.due_date).toLocaleString() : '—'}</td>
                    <td>${task.status ? task.status.replace('_', ' ') : '—'}</td>
                </tr>
            `).join('')}
        </tbody>
    `;
    taskSection.appendChild(taskTable);

    const collabSection = document.createElement("div");
    collabSection.innerHTML = `<h2>Collaborators</h2>`;
    const collabTable = document.createElement("table");
    collabTable.className = "widefat fixed striped";
    collabTable.innerHTML = `
        <thead>
            <tr>
                <th>Project</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            ${collaborators.map(c => `
                <tr>
                    <td>${c.project_title || '—'}</td>
                    <td>${c.display_name || '—'}</td>
                </tr>
            `).join('')}
        </tbody>
    `;
    collabSection.appendChild(collabTable);

    grid.appendChild(taskSection);
    grid.appendChild(collabSection);
});
</script>

        </div>
    
        <?php
    }

    public static function generateReport()
    {
        global $wpdb;

        // Example report generation logic
        $reports = $wpdb->get_results("
            SELECT m.title AS project, COUNT(t.id) AS task_count
            FROM {$wpdb->prefix}goalforge_projects m
            LEFT JOIN {$wpdb->prefix}goalforge_tasks t
            ON m.id = t.project_id
            GROUP BY m.id
        ", ARRAY_A);

        ?>
        <div class="wrap">
            <h1>Project Progress Reports</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Task Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo esc_html($report['project']); ?></td>
                            <td><?php echo esc_html($report['task_count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }


    public static function renderBonusAssignmentForm()
    {
        if (!empty($_GET['success'])): ?>
        <div class="notice notice-success"><p>Bonus assigned successfully.</p></div>
    <?php elseif (!empty($_GET['error'])): ?>
        <div class="notice notice-error"><p>Failed to assign bonus. Please try again.</p></div>
    <?php endif;
        global $wpdb;
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}goalforge_projects", ARRAY_A);
        ?>
        <div class="wrap">
            <h1>Assign Bonus to Project</h1>
            <form id="assign-bonus-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="goalforge_assign_bonus_to_project">
                 <?php wp_nonce_field('assign_bonus_to_project_nonce', '_wpnonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="project-id">Select Project</label></th>
                        <td>
                            <select id="project-id" name="project_id" >
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects as $project) : ?>
                                    <option value="<?php echo esc_attr($project['id']); ?>">
                                        <?php echo esc_html($project['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span id="current-bonus" style="margin-left: 10px; font-weight: bold;"></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bonus-amount">Bonus Amount</label></th>
                        <td>
                            <input type="number" id="bonus-amount" name="bonus_amount" step="0.01" min="0" >
                        </td>
                    </tr>
                </table>
                <button type="submit" class="button button-primary">Assign Bonus</button>
            </form>
            <script> document.addEventListener('DOMContentLoaded', function () { 
            const select = document.getElementById('project-id'); 
            const bonusDisplay = document.getElementById('current-bonus'); 
            const nonce = '<?php echo wp_create_nonce('assign_bonus_to_project_nonce'); ?>'; 
            function updateBonus(projectId) { 
                if (!projectId) { 
                    bonusDisplay.textContent = ''; 
                    return; 
                } 
                fetch(ajaxurl + '?action=goalforge_fetch_bonus&_wpnonce=' + nonce + '&project_id=' + projectId) 
                .then(response => response.json()) 
                .then(data => { if (data.success && data.data.bonus_amount > 0) {
                     bonusDisplay.textContent = 'Current Bonus: $' + parseFloat(data.data.bonus_amount).toFixed(2); 
                    } else { 
                        bonusDisplay.textContent = 'No bonus set.'; 
                    } 
                }) 
                    .catch(() => { bonusDisplay.textContent = 'Error loading bonus.'; 
                    }); 
                } select.addEventListener('change', () => {
                     updateBonus(select.value); 
                    }); 
        // Trigger on load if a project is preselected 
        if (select.value) { updateBonus(select.value); } }); 
        </script>
            <div id="bonus-result"></div>
        </div>        
        <?php
        
    }

    public static function enqueueAdminScripts($hook)
    {
        
        // Global styles for all admin pages
        wp_enqueue_style(
            'goalforge-admin-styles',
            plugins_url('/assets/css/admin.css', __FILE__),
            [],
            '1.0.0'
        );
        
        wp_enqueue_style(
                    'goalforge-dashboard-styles',
                    plugins_url('/assets/css/dashboard.css', __FILE__),
                    [],
                    '1.0.0'
                );
        // Enqueue Flatpickr styles globally for date pickers
        wp_enqueue_style(
            'flatpickr-styles',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );
    
        // Page-specific scripts and styles
        switch ($hook) {
            case 'toplevel_page_goalforge-dashboard':                
                wp_enqueue_script(
                    'goalforge-dashboard', 
                    plugins_url('/assets/js/dashboard.js', __FILE__),
                     [],
                      false,
                       true
                );
                break;
    
            case 'goalforge_page_task-linking':
                wp_enqueue_script(
                    'task-linking-script',
                    plugins_url('/assets/js/task-linking.js', __FILE__),
                    ['jquery'],
                    '1.0.0',
                    true
                );
                wp_localize_script('task-linking-script', 'TaskLinking', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('link_task_to_project_nonce'),
                ]);
                break;
    
            case 'goalforge_page_bonus-assignment':
                wp_enqueue_script(
                    'bonus-assignment-script',
                    plugins_url('/assets/js/bonus-assignment.js', __FILE__),
                    ['jquery'],
                    '1.0.0',
                    true
                );
                wp_localize_script('bonus-assignment-script', 'BonusAssignment', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('assign_bonus_to_project_nonce'),
                ]);
                break;
    
            case 'goalforge_page_create-task':
                wp_enqueue_script(
                    'create-task-script',
                    plugins_url('/assets/js/create-task.js', __FILE__),
                    ['jquery', 'flatpickr'],
                    '1.0.0',
                    true
                );
                wp_localize_script('create-task-script', 'TaskCreation', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('goalforge_create_task_action'),
                ]);
                break;
    
            case 'goalforge_page_create-project':
                wp_enqueue_script(
                    'create-project-script',
                    plugins_url('/assets/js/create-project.js', __FILE__),
                    ['jquery', 'flatpickr'],
                    '1.0.0',
                    true
                );
                wp_localize_script('create-project-script', 'ProjectCreation', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('goalforge_create_project_action'),
                ]);
                break;
    
            case 'goalforge_page_goalforge-templates':
                wp_enqueue_script(
                    'apply-template-script',
                    plugins_url('/assets/js/apply-template.js', __FILE__),
                    ['jquery'],
                    '1.0.0',
                    true
                );
                wp_localize_script('apply-template-script', 'GoalForge', [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('apply_template_nonce'),
                ]);
                break;
    
            case 'goalforge_page_reminder-feature':
                // Enqueue Flatpickr and reminder-specific script
                wp_enqueue_script(
                    'reminder-feature-script',
                    plugins_url('/assets/js/reminder-feature.js', __FILE__),
                    ['jquery', 'flatpickr'],
                    '1.0.0',
                    true
                );
                wp_localize_script('reminder-feature-script', 'ReminderFeature', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('save_task_dates_nonce'),
                ]);
                break;
        }
    
        // Enqueue Flatpickr JS globally for pages that require it
        wp_enqueue_script(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            [],
            '4.6.13',
            true
        );
    }
    
    
    public static function renderTaskLinkingForm() {
        global $wpdb;

        // Fetch tasks and projects
$tasks = $wpdb->get_results("SELECT id, title, project_id FROM {$wpdb->prefix}goalforge_tasks", ARRAY_A);
$projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}goalforge_projects", ARRAY_A);
?>
<div class="wrap">
    <h1>Link Tasks to Projects</h1>
    <form id="link-task-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="goalforge_link_task_to_project">
        <?php wp_nonce_field('goalforge_link_task_action', 'goalforge_link_task_nonce'); ?>

        <table class="form-table">
            <tr>
                <th><label for="task-id">Select Task</label></th>
                <td>
                    <select id="task-id" name="task_id" >
                        <option value="">-- Select Task --</option>
                        <?php foreach ($tasks as $task) : ?>
                            <option value="<?php echo esc_attr($task['id']); ?>">
                                <?php echo esc_html($task['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="project-id">Select Project</label></th>
                <td>
                    <select id="project-id" name="project_id" >
                        <option value="">-- Select Project --</option>
                        <?php foreach ($projects as $project) : ?>
                            <option value="<?php echo esc_attr($project['id']); ?>">
                                <?php echo esc_html($project['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <button type="submit" class="button button-primary">Link Task</button>
    </form>

    <?php if (!empty($_GET['success'])): ?>
        <div class="notice notice-success"><p>Task linked successfully.</p></div>
    <?php elseif (!empty($_GET['error'])): ?>
        <div class="notice notice-error"><p>Failed to link task. Please try again.</p></div>
    <?php endif; ?>
</div>
<?php

    }

    public static function renderCreateTaskForm() {
       // Handle project filter if present
        global $wpdb;
        $users = get_users();
    $project_id_filter = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    
   
    $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}goalforge_projects");
    $milestones = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}goalforge_milestones");
    $query = '';
    if (!empty($project_id_filter)) { 
    $query .= $wpdb->prepare(" WHERE project_id = %d", $project_id_filter); } 

    $tasks = $wpdb->get_results("
        SELECT t.*, p.title AS project_title, m.title AS milestone_title
        FROM {$wpdb->prefix}goalforge_tasks t
        LEFT JOIN {$wpdb->prefix}goalforge_projects p ON t.project_id = p.id
        LEFT JOIN {$wpdb->prefix}goalforge_milestones m ON t.milestone_id = m.id
        $query
        ORDER BY t.due_date DESC
    ");
    ?>
<div class="wrap">
    <h1>Create Task</h1>

    <?php if (isset($_GET['task_status'])): ?>
        <div class="notice <?php echo $_GET['task_status'] === 'success' ? 'notice-success' : 'notice-error'; ?>">
            <p><?php echo $_GET['task_status'] === 'success' ? 'Task created successfully!' : 'Failed to create task.'; ?></p>
        </div>
    <?php endif; ?>

    <form id="create-task-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="goalforge_create_task">
        <?php wp_nonce_field('goalforge_create_task_action', 'goalforge_task_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="task-title">Task Title</label></th>
                <td><input type="text" id="task-title" name="task_title" ></td>
            </tr>
            <tr>
                <th><label for="task-description">Task Description</label></th>
                <td><textarea id="task-description" name="task_description"></textarea></td>
            </tr>
            <tr>
                <th><label for="task-start-date">Start Date</label></th>
                <td><input type="datetime-local" id="task-start-date" name="task_start_date" ></td>
            </tr>
            <tr>
                <th><label for="task-due-date">Due Date</label></th>
                <td><input type="datetime-local" id="task-due-date" name="task_due_date" ></td>
            </tr>
            <tr>
                <th><label for="task-reminder-time">Reminder</label></th>
                <td>
                    <select id="task-reminder-time" name="task_reminder_time">
                        <option value="">-- None --</option>
                        <option value="on_due_date">On due date</option>
                        <option value="5_minutes_before">5 minutes before</option>
                        <option value="10_minutes_before">10 minutes before</option>
                        <option value="15_minutes_before">15 minutes before</option>
                        <option value="1_hour_before">1 hour before</option>
                        <option value="2_hours_before">2 hours before</option>
                        <option value="1_day_before">1 day before</option>
                        <option value="2_days_before">2 days before</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="task-status">Status</label></th>
                <td>
                    <select id="task-status" name="task_status" >
                        <option value="todo">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="project-id">Assign to Project</label></th>
                <td>
                    <select id="project-id" name="project_id">
                        <option value="">-- None --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo esc_attr($project->id); ?>"><?php echo esc_html($project->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="milestone-id">Assign to Milestone</label></th>
                <td>
                    <select id="milestone-id" name="milestone_id">
                        <option value="">-- None --</option>
                        <?php foreach ($milestones as $milestone): ?>
                            <option value="<?php echo esc_attr($milestone->id); ?>"><?php echo esc_html($milestone->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <button type="submit" class="button button-primary">Create Task</button>
    </form>

    <hr>

    <h2>Task List</h2>

    
    <form method="get" style="margin-bottom: 1rem;"> 
        <input type="hidden" name="page" value="goalforge_create_task" /> 
        <label for="project_id"><strong>Filter by Project:</strong></label> 
        <select name="project_id" id="project_id" onchange="this.form.submit()"> 
            <option value="">All Projects</option> 
            <?php foreach ($projects as $proj): ?> 
                <option value="<?php echo esc_attr($proj->id); ?>" 
                <?php selected($project_id_filter, $proj->id); ?>> <?php echo esc_html($proj->title); ?> 
            </option> <?php endforeach; ?> </select> 
    </form>

    <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Due Date</th>
                <th>Project</th>
                <th>Milestone</th>
                <th>Reminder</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tasks)) : ?>
                <?php foreach ($tasks as $task) : ?>
                    <tr>
                        <td><?php echo esc_html($task->title); ?></td>
                        <td><?php echo esc_html(date('Y-m-d H:i', strtotime($task->due_date))); ?></td>
                        <td><?php echo esc_html($task->project_title); ?></td>
                        <td><?php echo esc_html($task->milestone_title); ?></td>
                        <td><?php echo esc_html($task->reminder_time ?: '—'); ?></td>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $task->status))); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=goalforge_edit_task&id=' . intval($task->id)); ?>">✏️ Edit</a> |
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=goalforge_delete_task&task_id=' . intval($task->id)), 'goalforge_delete_task_action'); ?>"
                            onclick="return confirm('Are you sure you want to delete this task?');">🗑️ Delete</a>
                        </td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="goalforge_assign_user_to_task">
                                <input type="hidden" name="task_id" value="<?php echo esc_attr(intval($task->id)); ?>">
                                <?php wp_nonce_field('assign_user_to_task', '_wpnonce'); ?>
                                <select name="user_id" >
                                    <option value="">Assign to user</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>">
                                            <?php echo esc_html($user->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="button">Assign</button>
                            </form>
                        </td>
                        
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4">No tasks found.</td></tr>
            <?php endif; ?>
        </tbody>
</table>
 
</div>
<?php foreach ($tasks as $task): 
   $assigned = $wpdb->get_results($wpdb->prepare(
    "SELECT u.display_name FROM {$wpdb->prefix}goalforge_task_assignees a 
     JOIN {$wpdb->users} u ON a.user_id = u.ID 
     WHERE a.task_id = %d", intval($task->id)
));
echo implode(', ', wp_list_pluck($assigned, $task->title, 'display_name'));
 
     endforeach ?>
     <?php 
    }
    
public static function renderCreateProjectForm()
{
    //ob_start();

    // Status message
    if (isset($_GET['project_status'])): ?>
        <div class="goalforge-status-message <?php echo $_GET['project_status'] === 'success' ? 'success' : 'error'; ?>">
            <?php
            if ($_GET['project_status'] === 'success') {
                echo '<div class="notice notice-success"><p>✅ Project created successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ Failed to create project. Please try again.</p></div>';
            }
            ?>
        </div>
    <?php endif;

    ?>

    <div class="wrap">
    <h1>Create Project</h1>
    <form id="create-project-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="goalforge_add_project">
        <table class="form-table">
            <tr>
                <th><label for="project-title">Project Title</label></th>
                <td><input type="text" id="project-title" name="goalforge_title" ></td>
            </tr>
            <tr>
                <th><label for="project-description">Description</label></th>
                <td><textarea id="project-description" name="goalforge_description"></textarea></td>
            </tr>
            <tr>
                <th><label for="project-department">Department</label></th>
                <td>
                    <select id="project-department" name="goalforge_department" >
                        <option value="">-- Select Department --</option>
                        <option value="Finance">Finance</option>
                        <option value="IT and Development">IT and Development</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Production">Production</option>
                        <option value="Administration">Administration</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="project-start-date">Start Date</label></th>
                <td><input type="datetime-local" id="project-start-date" name="goalforge_start_date" ></td>
            </tr>
            <tr>
                <th><label for="project-due-date">Due Date</label></th>
                <td><input type="datetime-local" id="project-due-date" name="goalforge_due_date" ></td>
            </tr>
            <tr> <th><label for="project-status">Status</label></th> 
            <td> <select id="project-status" name="goalforge_status" > 
                <option value="">-- Select Status --</option> 
                <option value="Planning">Planning</option> 
                <option value="Active">Active</option> 
                <option value="Completed">Completed</option> 
            </select> 
            </td> 
            </tr>
            <tr>
                <th><label for="project-reminder-time">Reminder</label></th>
                <td>
                    <select id="project-reminder-time" name="project_reminder_time">
                        <option value="">-- None --</option>
                        <option value="on_due_date">On due date</option>
                        <option value="5_minutes_before">5 minutes before</option>
                        <option value="10_minutes_before">10 minutes before</option>
                        <option value="15_minutes_before">15 minutes before</option>
                        <option value="1_hour_before">1 hour before</option>
                        <option value="2_hours_before">2 hours before</option>
                        <option value="1_day_before">1 day before</option>
                        <option value="2_days_before">2 days before</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php wp_nonce_field('goalforge_create_project_action', 'goalforge_nonce'); ?>
        <button type="submit" class="button button-primary" name="goalforge_create_project">Create Project</button>
    </form>
</div>


    <?php
    // List all projects below the form
            $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $projects_per_page = 5;
            $offset = ($current_page - 1) * $projects_per_page;

            $projects = ProjectController::getAllProjects($projects_per_page, $offset);
            $total_projects = ProjectController::getProjectCount();
            $total_pages = ceil($total_projects / $projects_per_page);

    
    ?>
    <div class="wrap">
        <h2>All Projects</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($projects)) : ?>
                    <?php foreach ($projects as $project) : ?>
                        <tr>
                            <td><?php echo esc_html($project->title); ?></td>
                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($project->due_date))); ?></td>
                            <td><?php echo esc_html($project->status ?? '—'); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=goalforge_edit_project&id=' . intval($project->id)); ?>">✏️ Edit</a> |
                                <a href="<?php echo admin_url('admin.php?page=goalforge_assign_collaborators&id=' . intval($project->id)); ?>">👥 Assign to User</a> |
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" 
                                onsubmit="return confirm('Are you sure you want to delete this project?');"> 
                                <input type="hidden" name="action" value="goalforge_delete_project"> 
                                <input type="hidden" name="project_id" value="<?php echo esc_attr($project->id); ?>">
                                 <?php wp_nonce_field('goalforge_delete_project_action', 'goalforge_nonce'); ?>
                                 <button type="submit" class="button button-danger">Delete</button> </form>
                                
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="3">No projects found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav">
        <div class="tablenav-pages">
            <span class="pagination-links">
                <?php if ($current_page > 1): ?>
                    <a class="prev-page" href="<?php echo esc_url(add_query_arg('paged', $current_page - 1)); ?>">&laquo;</a>
                <?php endif; ?>

                <span class="paging-input">
                    Page <?php echo $current_page; ?> of <span class="total-pages"><?php echo $total_pages; ?></span>
                </span>

                <?php if ($current_page < $total_pages): ?>
                    <a class="next-page" href="<?php echo esc_url(add_query_arg('paged', $current_page + 1)); ?>">&raquo;</a>
                <?php endif; ?>
            </span>
        </div>
    </div>

    </div>
    <style>
    table.wp-list-table th, table.wp-list-table td {
        padding: 8px;
    }
    table.wp-list-table a {
        text-decoration: none;
    }
</style>

    <?php

    //return ob_get_clean();
}
public static function renderEditTaskForm()
{
    global $wpdb;

    $task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $tasks_table = $wpdb->prefix . 'goalforge_tasks';

    $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tasks_table WHERE id = %d", $task_id));
    if (!$task) {
        echo '<div class="notice notice-error"><p>Task not found.</p></div>';
        return;
    }

    $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}goalforge_projects");
     $milestones = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}goalforge_milestones");

    ?>
    <div class="wrap">
        <h1>Edit Task</h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="goalforge_update_task">
            <input type="hidden" name="task_id" value="<?php echo esc_attr($task->id); ?>">
            <?php wp_nonce_field('goalforge_edit_task_nonce', '_wpnonce'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="title">Title</label></th>
                    <td><input name="title" type="text" value="<?php echo esc_attr($task->title); ?>" ></td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td><textarea name="description"><?php echo esc_textarea($task->description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="start_date">Start Date</label></th>
                    <td><input name="start_date" type="datetime-local" value="<?php echo esc_attr($task->start_date); ?>"></td>
                </tr>
                <tr>
                    <th><label for="due_date">Due Date</label></th>
                    <td><input name="due_date" type="datetime-local" value="<?php echo esc_attr($task->due_date); ?>"></td>
                </tr>
                <tr>
                    <th><label for="task-status">Status</label></th>
                    <td>
                        <select id="task-status" name="task_status" >
                            <option value="todo" <?php selected($task->status, 'todo'); ?>>To Do</option>
                            <option value="in_progress" <?php selected($task->status, 'in_progress'); ?>>In Progress</option>
                            <option value="done" <?php selected($task->status, 'done'); ?>>Done</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="project_id">Project</label></th>
                    <td>
                        <select name="project_id">
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?php echo esc_attr($proj->id); ?>" <?php selected($proj->id, $task->project_id); ?>>
                                    <?php echo esc_html($proj->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="project_id">Milestone</label></th>
                    <td>
                        <select name="milestone_id">
                            <?php foreach ($milestones as $mile): ?>
                                <option value="<?php echo esc_attr($mile->id); ?>" <?php selected($mile->id, $task->milestone_id); ?>>
                                    <?php echo esc_html($mile->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <button type="submit" class="button button-primary">Update Task</button>
        </form>
    </div>
    <?php
}

   public static function renderEditProject() {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo '<div class="notice notice-error"><p>Invalid project ID.</p></div>';
        return;
    }

    $projectId = intval($_GET['id']);
    $project = \BemaGoalForge\ProjectManagement\ProjectController::getProjectById($projectId);

    if (!$project) {
        echo '<div class="notice notice-error"><p>Project not found.</p></div>';
        return;
    }
    if (isset($_GET['project_status'])) {
    $status = $_GET['project_status'];
    if ($status === 'updated') {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Project updated successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>❌ Failed to update project.</p></div>';
    }
}
    ob_start();
    ?>
    <div class="wrap">
        <h1>Edit Project: <?php echo esc_html($project->title); ?></h1>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="goalforge_update_project">
            <input type="hidden" name="project_id" value="<?php echo esc_attr($project->id); ?>">

            <table class="form-table">
                <tr>
                    <th><label for="goalforge_title">Title</label></th>
                    <td><input type="text" name="goalforge_title" id="goalforge_title" value="<?php echo esc_attr($project->title); ?>" ></td>
                </tr>
                <tr>
                    <th><label for="goalforge_description">Description</label></th>
                    <td><textarea name="goalforge_description" id="goalforge_description"><?php echo esc_textarea($project->description); ?></textarea></td>
                </tr>
                <tr>
                <th><label for="project-department">Department</label></th>
                <td>
                    <select id="project-department" name="goalforge_department" >
                        <option value="">-- Select Department --</option>
                        <option value="Finance">Finance</option>
                        <option value="IT and Development">IT and Development</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Production">Production</option>
                        <option value="Administration">Administration</option>
                    </select>
                </td>
            </tr>
                <tr>
                    <th><label for="goalforge_start_date">Start Date</label></th>
                    <td><input type="datetime-local" name="goalforge_start_date" id="goalforge_start_date" value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($project->start_date))); ?>"></td>
                </tr>
                <tr>
                    <th><label for="goalforge_due_date">Due Date</label></th>
                    <td><input type="datetime-local" name="goalforge_due_date" id="goalforge_due_date" value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($project->due_date))); ?>"></td>
                </tr>
                <tr>
                <th><label for="project-status">Status</label></th>
                <td>
                    <select id="project-status" name="goalforge_status" >
                        <option value="Planning" <?php selected($project->status, 'Planning'); ?>>Planning</option>
                        <option value="Active" <?php selected($project->status, 'Active'); ?>>Active</option>
                        <option value="Completed" <?php selected($project->status, 'Completed'); ?>>Completed</option>
                    </select>
                </td>
            </tr>
                <tr>
                    <th><label for="project_reminder_time">Reminder Time</label></th>
                    <td>
                        <select id="project_reminder_time" name="project_reminder_time">
                            <option value="">-- None --</option>
                            <?php
                            $reminders = [
                                'on_due_date' => 'On due date',
                                '5_minutes_before' => '5 minutes before',
                                '10_minutes_before' => '10 minutes before',
                                '15_minutes_before' => '15 minutes before',
                                '1_hour_before' => '1 hour before',
                                '2_hours_before' => '2 hours before',
                                '1_day_before' => '1 day before',
                                '2_days_before' => '2 days before',
                            ];
                            foreach ($reminders as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '" ' . selected($project->reminder_time, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php wp_nonce_field('goalforge_update_project_action', 'goalforge_nonce'); ?>
            <p>
            <button type="submit" class="button button-primary">Update Project</button>
            <a href="<?php echo admin_url('admin.php?page=create-project'); ?>" class="button button-secondary">← Back to Project List</a>
        </p>    
        </form>
    </div>
    <?php
    echo ob_get_clean();
}

    public static function renderAssignCollaborators()
    {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="notice notice-error"><p>Invalid project ID.</p></div>';
    return;
    }

    $projectId = intval($_GET['id']);
    $project = ProjectController::getProjectById($projectId);
    if (!$project) {
        echo '<div class="notice notice-error"><p>Project not found.</p></div>';
        return;
    }

    // Get all users (you can filter by role if needed)
    $users = get_users(['fields' => ['ID', 'display_name']]);

    // Get already assigned user IDs
    $assignedUserIds = ProjectController::getCollaboratorsByProjectId($projectId); // ← You implement this
    if (!is_array($assignedUserIds)) {
    $assignedUserIds = [];
    }

    // Status message
    if (isset($_GET['assign_status'])) {
        if ($_GET['assign_status'] === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>Collaborators updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to update collaborators.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Assign Collaborators for: <?php echo esc_html($project->title); ?></h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="goalforge_assign_collaborators">
            <input type="hidden" name="project_id" value="<?php echo esc_attr($projectId); ?>">

        <table class="form-table">
                <tr>
                    <th><label for="collaborators">Select Collaborators</label></th>
                    <td>
                        <select name="collaborators[]" id="collaborators" multiple size="10" style="width: 300px;">
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo esc_attr($user->ID); ?>"
                                    <?php selected(in_array($user->ID, $assignedUserIds)); ?>>
                                    <?php echo esc_html($user->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Hold down Ctrl (Windows) or Command (Mac) to select multiple users.</p>
                    </td>
                </tr>
            </table>

            <?php wp_nonce_field('goalforge_assign_collaborators_nonce'); ?>
            <p>
                <button type="submit" class="button button-primary">Assign Collaborators</button>
                <a href="<?php echo admin_url('admin.php?page=create-project'); ?>" class="button button-secondary">← Back to Project List</a>
            </p>
        </form>

        <h2>Currently Assigned</h2>
        <ul>
            <?php if (!empty($assignedUserIds)): ?>
                <?php foreach ($assignedUserIds as $uid): $user = get_userdata($uid); ?>
                    <li><?php echo esc_html($user ? $user->display_name : "User #$uid"); ?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No collaborators assigned yet.</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php

    }

     public static function handleLinkTaskToProject() {
        if (
        !current_user_can('edit_posts') ||
        !isset($_POST['goalforge_link_task_nonce']) ||
        !wp_verify_nonce($_POST['goalforge_link_task_nonce'], 'goalforge_link_task_action')
        ) {
        wp_die('Unauthorized request');        }

                  

        $task_id = intval($_POST['task_id']);
        $project_id = intval($_POST['project_id']);

        if (!$task_id || !$project_id) {
            wp_redirect(add_query_arg('error', 1, wp_get_referer()));
            exit;
        }

        $controller = new \BemaGoalForge\TaskManagement\TaskController(); 
        $result = $controller->linkTaskToProject($task_id, $project_id);


        if ($result !== false) {
            wp_redirect(add_query_arg('success', 1, wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 1, wp_get_referer()));
        }
        exit;

    }

      public static function handleUpdateTask()
        {
            if (!current_user_can('edit_posts') || !check_admin_referer('goalforge_edit_task_nonce')) {
                wp_die('Unauthorized request');
            }

            

            $task_id = intval($_POST['task_id']);

            $data = [

            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'due_date' => sanitize_text_field($_POST['due_date']),
            'status' => sanitize_text_field($_POST['task_status']),
            'project_id'=> intval($_POST['project_id']),
            'milestone_id'=> intval($_POST['milestone_id'])
            ];


            $controller = new TaskController();

            $updated = $controller->updateTask($task_id, $data);       

            if ($updated !== false) {
                wp_redirect(admin_url('admin.php?page=goalforge_create_task&updated=1'));
                exit;
            } else {
                wp_redirect(admin_url('admin.php?page=goalforge_create_task&error=1'));
                exit;
            }
        }
}

// Initialize the Admin Dashboard Controller
AdminDashboardController::init();
