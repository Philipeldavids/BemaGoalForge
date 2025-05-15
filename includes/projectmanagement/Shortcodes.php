<?php

namespace BemaGoalForge\ProjectManagement;

use BemaGoalForge\ProjectManagement\ProjectController;

class Shortcodes
{
    public static function renderProjectForm()
    {
        if (!is_user_logged_in() || !current_user_can('administrator')) {
            return '<div class="goalforge-project-form"><p>You do not have permission to view this form.</p></div>';
        }

    
        // Form display
        ob_start(); 
   if (isset($_GET['project_status'])): ?>
    <div class="goalforge-status-message 
        <?php echo $_GET['project_status'] === 'success' ? 'success' : 'error'; ?>">
        <?php
        if ($_GET['project_status'] === 'success') {
            echo '✅ Project created successfully!';
        } else {
            echo '❌ Failed to create project. Please try again.';
        }
        ?>
    </div>
    <?php endif; ?>

        <form class="goalforge-project-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <h2>Create a New Project</h2>
            <input type="hidden" name="action" value="goalforge_add_project">
            <input type="text" name="goalforge_title" placeholder="Project Title" required />
            <textarea name="goalforge_description" placeholder="Project Description"></textarea>
            <input type="date" name="goalforge_start_date" placeholder="Start Date" required />
            <input type="date" name="goalforge_due_date" placeholder="Due Date" required />           
                
            <select id="project-reminder-time" name="project_reminder_time">
                <option value="">Reminder</option>
                <option value="on_due_date">On due date</option>
                <option value="5_minutes_before">5 minutes before</option>
                <option value="10_minutes_before">10 minutes before</option>
                <option value="15_minutes_before">15 minutes before</option>
                <option value="1_hour_before">1 hour before</option>
                <option value="2_hours_before">2 hours before</option>
                <option value="1_day_before">1 day before</option>
                <option value="2_days_before">2 days before</option>
            </select>
              <?php wp_nonce_field('goalforge_create_project_action', 'goalforge_nonce'); ?>      
            <button type="submit" name="goalforge_create_project">Create Project</button>
        </form>
        <?php
        return ob_get_clean();
    }
}
