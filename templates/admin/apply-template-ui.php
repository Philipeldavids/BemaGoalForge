<div class="wrap">
    <h1>Create Task from Template</h1>
    <form id="apply-template-form">
        <label for="template-select">Select Template:</label>
        <select id="template-select">
            <option value="">-- Select a Template --</option>
            <!-- Populate this with actual templates -->
            <option value="1">Template: Design Landing Page</option>
            <option value="2">Template: Write Blog Post</option>
        </select>

        <input type="hidden" id="apply-template-nonce" value="<?php echo wp_create_nonce('apply_template_nonce'); ?>" />

        <button id="apply-template-btn" class="button button-primary">Create Task</button>
    </form>

    <div id="new-task-result"></div>
</div>

<div class="wrap">
    <h1>Manage Projects</h1>
    <form id="create-project-form">
        <label for="project-title">Title:</label>
        <input type="text" id="project-title" name="title" required />

        <label for="project-description">Description:</label>
        <textarea id="project-description" name="description"></textarea>

        <label for="project-start-date">Start Date:</label>
        <input type="date" id="project-start-date" name="start_date" required />

        <label for="project-end-date">End Date:</label>
        <input type="date" id="project-end-date" name="end_date" required />

        <button type="submit" class="button button-primary">Create Project</button>
    </form>
    <div id="project-result"></div>
</div>
