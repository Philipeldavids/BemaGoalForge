jQuery(document).ready(function ($) {
  $("#apply-template-btn").on("click", function (e) {
    e.preventDefault();

    const templateId = $("#template-select").val();
    if (!templateId) {
      alert("Please select a template.");
      return;
    }

    // AJAX Request
    $.post(ajaxurl, {
      action: "apply_template",
      template_id: templateId,
      _wpnonce: $("#apply-template-nonce").val(),
    })
      .done(function (response) {
        if (response.success) {
          // Update UI with new task details
          const task = response.data;
          $("#new-task-result").html(`
                      <div class="task-success">
                          <h3>New Task Created</h3>
                          <p><strong>ID:</strong> ${task.task_id}</p>
                          <p><strong>Title:</strong> ${task.title}</p>
                          <p><strong>Description:</strong> ${task.description}</p>
                          <p><strong>Due Date:</strong> ${task.due_date}</p>
                      </div>
                  `);
        } else {
          alert(response.data.message);
        }
      })
      .fail(function () {
        alert("Failed to create task. Please try again.");
      });
  });
});
