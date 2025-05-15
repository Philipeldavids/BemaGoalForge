function goalforge_project_form_shortcode() {
    if (!goalforge_user_can_manage_projects()) {
        return '<p>You are not authorized to create projects.</p>';
    }

    // Check for messages in query parameters
    $message = isset($_GET['gf_message']) ? sanitize_text_field($_GET['gf_message']) : '';
    $type = isset($_GET['gf_type']) ? sanitize_text_field($_GET['gf_type']) : '';

    ob_start();
    ?>

    <style>
        .goalforge-form-container {
            max-width: 600px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            margin: 20px auto;
            font-family: sans-serif;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .goalforge-form-container h2 {
            margin-bottom: 15px;
        }
        .goalforge-form-container input,
        .goalforge-form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .goalforge-form-container input[type="submit"] {
            background-color: #0073aa;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .goalforge-form-container input[type="submit"]:hover {
            background-color: #005f8d;
        }
        .goalforge-message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .goalforge-message.success {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .goalforge-message.error {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>

    <div class="goalforge-form-container">
        <h2>Create New Project</h2>

        <?php if (!empty($message)): ?>
            <div class="goalforge-message <?php echo esc_attr($type); ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="goalforge_create_project">
            <?php wp_nonce_field('goalforge_create_project_action', 'goalforge_nonce'); ?>

            <label for="title">Title</label>
            <input type="text" name="title" id="title" required>

            <label for="description">Description</label>
            <textarea name="description" id="description" required></textarea>

            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" id="start_date" required>

            <label for="due_date">Due Date</label>
            <input type="date" name="due_date" id="due_date" required>

            <input type="submit" value="Create Project">
        </form>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('goalforge_project_form', 'goalforge_project_form_shortcode');
