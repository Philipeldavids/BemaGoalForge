// assets/js/grid-panel-view.js
jQuery(document).ready(function ($) {
  const GridPanelView = {
    /**
     * Displays the grid panel of tasks and collaborators.
     */
    renderGridPanel: function (data) {
      let gridHtml = "";

      data.forEach((item) => {
        gridHtml += `
                  <div class="grid-item">
                      <h3>${item.title}</h3>
                      <p>${item.description}</p>
                  </div>`;
      });

      $("#grid-panel-container").html(gridHtml);
    },

    /**
     * Filters tasks based on user input.
     */
    filterGridPanel: function () {
      const filterCriteria = $("#filter-input").val();

      $.ajax({
        url: TaskLinking.ajax_url,
        method: "POST",
        data: {
          action: "filter_tasks",
          criteria: filterCriteria,
        },
        success: function (response) {
          if (response.success) {
            GridPanelView.renderGridPanel(response.data);
          } else {
            $("#grid-panel-container").html("<p>No results found.</p>");
          }
        },
        error: function () {
          alert("Failed to filter tasks.");
        },
      });
    },
  };

  $("#filter-form").on("submit", function (e) {
    e.preventDefault();
    GridPanelView.filterGridPanel();
  });
});
