function toggleSelectAll(tableId) {
    const table = document.getElementById(tableId);
    const checkboxes = table.querySelectorAll('input[type="checkbox"]');
    const selectAllCheckbox = document.getElementById('selectAll' + tableId.charAt(0).toUpperCase() + tableId.slice(1));
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}