jQuery(document).ready(function ($) {
  // Encapsulate functions in an object for modularity
  const TaskLinkingHandler = {
    init: function () {
      // Bind event listeners
      $("#link-task-form").on("submit", this.handleTaskLinkSubmit);
    },

    // Handle form submission
    handleTaskLinkSubmit: function (e) {
      e.preventDefault();

      const taskId = $("#task-id").val();
      const projectId = $("#project-id").val();
      const nonce = TaskLinking.nonce;

      if (!taskId || !projectId) {
        TaskLinkingHandler.displayError(
          "Please select both a task and a project."
        );
        return;
      }

      // Send AJAX request
      $.post(TaskLinking.ajax_url, {
        action: "link_task_to_project",
        task_id: taskId,
        project_id: projectId,
        _wpnonce: nonce,
      })
        .done(function (response) {
          if (response.success) {
            TaskLinkingHandler.updateUIOnLinkSuccess(
              "Task linked successfully!"
            );
          } else {
            TaskLinkingHandler.displayError(
              "Failed to link task: " + response.message
            );
          }
        })
        .fail(function () {
          TaskLinkingHandler.displayError(
            "AJAX request failed. Please try again."
          );
        });
    },

    // Update the UI on successful linking
    updateUIOnLinkSuccess: function (message) {
      $("#link-result").html(`<div style="color: green;">${message}</div>`);
      $("#link-task-form")[0].reset(); // Reset the form
    },

    // Display error messages
    displayError: function (errorMessage) {
      $("#link-result").html(`<div style="color: red;">${errorMessage}</div>`);
    },
  };

  // Initialize the handler
  TaskLinkingHandler.init();
});
