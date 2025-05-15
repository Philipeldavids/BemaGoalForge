jQuery(document).ready(function ($) {
  $("#create-project-form").on("submit", function (e) {
    e.preventDefault();
    alert("I'm here");
    const projectData = {
      action: "create_project",
      title: $("#project-title").val(),
      description: $("#project-description").val(),
      start_date: $("#project-start-date").val(),
      due_date: $("#project-due-date").val(),
      reminder_time: $("#project-reminder-time").val(),
      _wpnonce: ProjectCreation.nonce,
    };

    // Validation for required fields
    if (
      !projectData.title ||
      !projectData.start_date ||
      !projectData.due_date
    ) {
      alert("Please fill out all required fields.");
      return;
    }

    // Validation for start date and due date
    if (new Date(projectData.start_date) > new Date(projectData.due_date)) {
      alert("Start date cannot be after the due date.");
      return;
    }

    // AJAX request to create the project
    $.post(ProjectCreation.ajax_url, projectData)
      .done(function (response) {
        if (response.success) {
          console.log(response); // temporary. remove when done
          alert("Project created successfully!");
          $("#create-project-result").html(
            '<div style="color: green;">' + response.data.message + "</div>"
          );
          $("#create-project-form")[0].reset();
          location.reload();
        } else {
          console.log(response); //temporary. remove when done
          alert(response.data.message);
          $("#create-project-result").html(
            '<div style="color: red;">' + response.message + "</div>"
          );
        }
      })
      .fail(function () {
        console.log("AJAX request failed."); //temporary. remove when done
        alert("Failed to create project. Please try again.");
        $("#create-project-result").html(
          '<div style="color: red;">AJAX request failed.</div>'
        );
      });
  });
});
