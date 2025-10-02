document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const exportForm = document.getElementById('exportForm');

    if (selectAllBtn && deselectAllBtn && exportForm) {
        const checkboxes = exportForm.querySelectorAll('input[type="checkbox"][name="event_ids[]"]');

        selectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });

        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    }
});

