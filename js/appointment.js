document.addEventListener("DOMContentLoaded", () => {
  const table = document.getElementById("appointmentsTable");
  const rows = Array.from(table.querySelectorAll("tbody tr"));

  // Sort by date descending (latest first)
  rows.sort((a, b) => {
    const dateA = new Date(a.children[3].textContent);
    const dateB = new Date(b.children[3].textContent);
    return dateB - dateA;
  });

  const tbody = table.querySelector("tbody");
  tbody.innerHTML = "";  // Clear current rows
  rows.forEach(row => tbody.appendChild(row));  // Append sorted rows
});
