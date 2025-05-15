jQuery(document).ready(function ($) {
  $("#create-task-form").on("submit", function (e) {
    e.preventDefault();

    const taskData = {
      action: "create_task",
      title: $("#task-title").val(),
      description: $("#task-description").val(),
      start_date: $("#task-start-date").val(),
      due_date: $("#task-due-date").val(),
      reminder_time: $("#task-reminder-time").val(),
      project_id: $("#project-id").val(),
      _wpnonce: TaskCreation.nonce,
    };

    $.post(TaskCreation.ajax_url, taskData)
      .done(function (response) {
        if (response.success) {
          alert("Task created successfully!");
          $("#create-task-result").html(
            '<div style="color: green;">' + response.data.message + "</div>"
          );
          $("#create-task-form")[0].reset();
          location.reload();
        } else {
          alert(response.data.message);
          $("#create-task-result").html(
            '<div style="color: red;">' + response.message + "</div>"
          );
        }
      })
      .fail(function () {
        alert("AJAX request failed.");
        $("#create-task-result").html(
          '<div style="color: red;">AJAX request failed.</div>'
        );
      });
  });
});
