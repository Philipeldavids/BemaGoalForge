jQuery(document).ready(function ($) {
  // Expand/Collapse Project Details
  $(".project-toggle-btn").on("click", function () {
    const projectId = $(this).data("project-id");
    $(`#project-details-${projectId}`).toggle();
  });

  // User Notification Dismiss
  $(".notification-dismiss-btn").on("click", function () {
    const notificationId = $(this).data("notification-id");

    $.post(GoalForge.ajaxurl, {
      action: "dismiss_notification",
      notification_id: notificationId,
      _wpnonce: GoalForge.nonce,
    })
      .done(function (response) {
        if (response.success) {
          $(`#notification-${notificationId}`).fadeOut();
        } else {
          alert("Failed to dismiss notification.");
        }
      })
      .fail(function () {
        alert("An error occurred while dismissing the notification.");
      });
  });

  // Task Progress Update
  $(".update-task-progress").on("change", function () {
    const taskId = $(this).data("task-id");
    const progress = $(this).val();

    $.post(GoalForge.ajaxurl, {
      action: "update_task_progress",
      task_id: taskId,
      progress: progress,
      _wpnonce: GoalForge.nonce,
    })
      .done(function (response) {
        if (response.success) {
          alert("Task progress updated successfully.");
        } else {
          alert("Failed to update task progress.");
        }
      })
      .fail(function () {
        alert("An error occurred while updating task progress.");
      });
  });

  // Live Search for Tasks/Projects
  $("#search-input").on("input", function () {
    const query = $(this).val().toLowerCase();
    $(".project-task").each(function () {
      const text = $(this).text().toLowerCase();
      $(this).toggle(text.includes(query));
    });
  });

  // Initialize Flatpickr for date range
  flatpickr("#date-range", {
    mode: "range",
    enableTime: true,
    dateFormat: "Y-m-d H:i",
  });

  // Save Task Dates and Reminder
  $("#save-dates").on("click", function (e) {
    e.preventDefault();

    const dateRange = $("#date-range").val();
    const reminder = $("#reminder").val();

    if (!dateRange) {
      alert("Please select a date range.");
      return;
    }

    const [startDate, endDate] = dateRange.split(" to ");
    if (!startDate || !endDate) {
      alert("Please select both start and end dates.");
      return;
    }

    // AJAX Request to Save Dates
    $.post(GoalForge.ajaxurl, {
      action: "save_task_dates",
      start_date: startDate,
      end_date: endDate,
      reminder: reminder,
      _wpnonce: GoalForge.nonce,
    })
      .done(function (response) {
        if (response.success) {
          $("#save-result").html(`<p>Dates saved successfully!</p>`);
        } else {
          alert(response.data.message || "Failed to save dates.");
        }
      })
      .fail(function () {
        alert("An error occurred while saving dates.");
      });
  });

  // Remove Task Dates
  $("#remove-dates").on("click", function (e) {
    e.preventDefault();

    // AJAX Request to Remove Dates
    $.post(GoalForge.ajaxurl, {
      action: "remove_task_dates",
      _wpnonce: GoalForge.nonce,
    })
      .done(function (response) {
        if (response.success) {
          $("#date-range").val("");
          $("#reminder").val("on_time");
          $("#save-result").html(`<p>Dates removed successfully!</p>`);
        } else {
          alert(response.data.message || "Failed to remove dates.");
        }
      })
      .fail(function () {
        alert("An error occurred while removing dates.");
      });
  });
});
