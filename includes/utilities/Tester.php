<?php

namespace BemaGoalForge\Utilities;
// Correct namespace for TaskTemplateController
use BemaGoalForge\TaskManagement\TaskTemplateController;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Tester
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerTesterMenu']);
        add_action('admin_post_drop_tables', [$this, 'handleDropTables']);

    }

    /**
     * Register a Tester menu in the WordPress admin dashboard.
     */
    public function registerTesterMenu()
    {
        add_menu_page(
            'GoalForge Tester',         // Page title
            'Tester',                   // Menu title
            'manage_options',           // Capability
            'goalforge-tester',         // Menu slug
            [$this, 'renderTesterPage'], // Callback function
            'dashicons-hammer',         // Icon
            99                          // Position
        );
    }

    /**
     * Render the Tester admin page.
     */
    public function renderTesterPage()
    {
        if (isset($_GET['status']) && $_GET['status'] === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>Tables dropped successfully.</p></div>';
        } elseif (isset($_GET['status']) && $_GET['status'] === 'failure') {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to drop tables. Check the error log for details.</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>GoalForge Tester</h1>
            <p>Click the button below to drop all plugin-related tables.</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('drop_tables_action', 'drop_tables_nonce'); ?>
                <input type="hidden" name="action" value="drop_tables">
                <button class="button button-primary" type="submit">Drop Tables</button>
            </form>
        </div>
        <?php
    }

    /**
     * Handle the Drop Tables action.
     */
    public function handleDropTables()
    {
        // Verify nonce
        if (!isset($_POST['drop_tables_nonce']) || !wp_verify_nonce($_POST['drop_tables_nonce'], 'drop_tables_action')) {
            wp_die('Invalid request.');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        // Call the dropTables method from DatabaseHandler
        if (\BemaGoalForge\Database\DatabaseHandler::dropTables()) {
            wp_redirect(admin_url('admin.php?page=goalforge-tester&status=success'));
        } else {
            wp_redirect(admin_url('admin.php?page=goalforge-tester&status=failure'));
        }

        exit;
    }



 public static function myTest() { 
    $top = 25;
    $top = $top + 0;

    $divstylemsg = 'background-color: gray; border: 1px solid black; color: white; font-size: 20px; width: 500px; height: 50px; margin-left: 100px; display: flex; align-items: center; justify-content: center; position: fixed; top:' .  $top . '; right: 200; z-index: 1000;';
    $divstylepass = 'background-color: orange; border: 1px solid black; color: green; font-size: 20px; width: 500px; height: 50px; display: flex; align-items: center; justify-content: center; position: fixed; top:' .  $top + 50 . '; right: 200; z-index: 1000;';
    $divstylefail = 'background-color: red; border: 1px solid black; color: blue; font-size: 20px; width: 500px; height: 50px; display: flex; align-items: center; justify-content: center; position: fixed; top:' .  $top + 100 . '; right: 200; z-index: 1000;';     
        ?>
      <div style="<?php echo $divstylemsg; ?>">
        <?php echo "Hello MY MY MY World"; ?>
      </div>
      <?php

    // file_put_contents(GOALFORGE_LOG_DIR . 'test.log', "Test log entry\n");
    // error_log('GoalForge: Error message', 3, GOALFORGE_LOG_DIR . 'error.log');
      $templateData = [
        'title' => 'Template Task',
        'description' => 'This is a reusable task template.',
        'due_date' => '2025-01-15 10:00:00',
        'project_id' => 1,
    ];

      $taskTemplateController = new TaskTemplateController(new \BemaGoalForge\TaskManagement\TaskTemplateModel(),
      new \BemaGoalForge\TaskManagement\TaskModel(),
      new \BemaGoalForge\Utilities\Logger());
        // Call createTemplate to save the template
        $result = $taskTemplateController->createTemplate($templateData);

        if ($result) {
            echo '<div style="' . $divstylepass . '">Template successfully created!</div>';
        } else {
            echo '<div style="' . $divstylefail . '">Failed to create template.</div>';
        }
        ?>
      <div style="<?php echo $divstylemsg; ?>">
        <?php echo "Hello taskTemplateController"; ?>
      </div>
      <?php

      //test update
      $top = $top + 50;
        $templateData = [
          'title'       => 'Updated Template Task',
          'description' => 'Updated description for the task template.',
          'due_date'    => '2025-01-20 15:00:00',
          'project_id'=> 1,
        ];

        $result = $taskTemplateController->updateTemplate(1, $templateData);

        if ($result) {
          echo '<div style="' . $divstylepass . '">Template updated successfully!</div>';
        } else {
          echo '<div style="' . $divstylefail . '">Failed to update template.</div>';
        }

        //test creation of task
        $top = $top + 50;
        $taskData = [
          'title'       => 'New Task',
          'description' => 'Description of the new task.',
          'due_date'    => '2025-02-01 10:00:00',
          'project_id'=> 1,
            ];
            
            $taskController = new \BemaGoalForge\TaskManagement\TaskController(new \BemaGoalForge\TaskManagement\TaskModel(), new \BemaGoalForge\Utilities\Logger());
            $result = $taskController->createTask($taskData);
            
            if ($result) {
              echo '<div style="' . $divstylepass . '">Task created successfully!</div>';
            } else {
              echo '<div style="' . $divstylefail . '">Failed to create task.</div>';
            }

  } 
}
