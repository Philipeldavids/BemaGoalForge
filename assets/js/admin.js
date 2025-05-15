jQuery(document).ready(function ($) {
  // Admin Dashboard Tabs Navigation
  $(".admin-tabs a").on("click", function (e) {
    e.preventDefault();
    const tabId = $(this).attr("href");
    $(".tab-content").hide();
    $(tabId).show();
    $(".admin-tabs a").removeClass("active");
    $(this).addClass("active");
  });

  // Confirmation Before Deleting a Project
  $(".delete-project-btn").on("click", function (e) {
    if (
      !confirm(
        "Are you sure you want to delete this project? This action cannot be undone."
      )
    ) {
      e.preventDefault();
    }
  });

  // Ajax Call for Saving Settings
  $("#save-settings-btn").on("click", function (e) {
    e.preventDefault();
    const settingsData = $("#settings-form").serialize();

    $.post(GoalForge.ajaxurl, {
      action: "save_admin_settings",
      settings: settingsData,
      _wpnonce: GoalForge.nonce,
    })
      .done(function (response) {
        if (response.success) {
          alert("Settings saved successfully.");
        } else {
          alert("Failed to save settings: " + response.data.message);
        }
      })
      .fail(function () {
        alert("An error occurred while saving settings.");
      });
  });

  // Dynamic Project Filtering
  $("#department-filter").on("change", function () {
    const department = $(this).val();
    $(".project-row").hide();
    $(`.project-row[data-department="${department}"]`).show();
  });
});
