document.addEventListener("DOMContentLoaded", function () {
    const container = document.getElementById("goalforge-dashboard-grid");
    if (!container || !window.goalforgeData) return;

    const { tasks, collaborators } = goalforgeData;

    const groupByProject = (items, key) => {
        return items.reduce((acc, item) => {
            const groupKey = item[key] || 'Unassigned';
            if (!acc[groupKey]) acc[groupKey] = [];
            acc[groupKey].push(item);
            return acc;
        }, {});
    };

    const tasksByProject = groupByProject(tasks, 'project_title');
    const collaboratorsByProject = groupByProject(collaborators, 'project_title');

    const grid = document.createElement("div");
    grid.className = "goalforge-grid";
    grid.style.display = "grid";
    grid.style.gridTemplateColumns = "1fr 1fr";
    grid.style.gap = "2rem";

    for (const project in tasksByProject) {
        const taskList = tasksByProject[project] || [];
        const collabList = collaboratorsByProject[project] || [];

        const card = document.createElement("div");
        card.className = "project-card";
        card.style.border = "1px solid #ddd";
        card.style.borderRadius = "10px";
        card.style.padding = "1rem";
        card.style.backgroundColor = "#fff";

        const title = document.createElement("h2");
        title.textContent = project;
        title.style.marginBottom = "0.5rem";

        const taskSection = document.createElement("div");
        taskSection.innerHTML = "<strong>Tasks:</strong><ul>" +
            taskList.map(t => `<li>${t.title} (Due: ${new Date(t.due_date).toLocaleDateString()})</li>`).join('') +
            "</ul>";

        const collabSection = document.createElement("div");
        collabSection.innerHTML = "<strong>Collaborators:</strong><ul>" +
            collabList.map(c => `<li>${c.display_name}</li>`).join('') +
            "</ul>";

        card.appendChild(title);
        card.appendChild(taskSection);
        card.appendChild(collabSection);

        grid.appendChild(card);
    }

    container.appendChild(grid);
});
