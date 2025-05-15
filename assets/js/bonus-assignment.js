jQuery(document).ready(function ($) {
  $("#assign-bonus-form").on("submit", function (e) {
    e.preventDefault();

    const projectId = $("#project-id").val();
    const bonusAmount = $("#bonus-amount").val();
    const nonce = BonusAssignment.nonce;

    $.post(BonusAssignment.ajax_url, {
      action: "assign_bonus_to_project",
      project_id: projectId,
      bonus_amount: bonusAmount,
      _wpnonce: nonce,
    })
      .done(function (response) {
        const message = response.success
          ? '<div style="color: green;">Bonus assigned successfully!</div>'
          : '<div style="color: red;">Failed to assign bonus: ' +
            response.message +
            "</div>";

        $("#bonus-result").html(message);
      })
      .fail(function () {
        $("#bonus-result").html(
          '<div style="color: red;">AJAX request failed.</div>'
        );
      });
  });
});
