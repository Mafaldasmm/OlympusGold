(function () {
    const searchInput = document.getElementById('class-analytics-search');
    const typeFilter = document.getElementById('class-analytics-filter-type');
    const dayFilter = document.getElementById('class-analytics-filter-day');
    const trainerFilter = document.getElementById('class-analytics-filter-trainer');
    const clearBtn = document.getElementById('class-analytics-filter-clear');
    const countEl = document.getElementById('class-analytics-filter-count');
    const emptyEl = document.querySelector('.classes-filter-empty');
    const rows = document.querySelectorAll('#class-analytics-table tbody tr[data-class-row]');

    if (!rows.length) {
        return;
    }

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function filterClasses() {
        const query = normalize(searchInput ? searchInput.value : '');
        const type = typeFilter ? typeFilter.value : '';
        const day = dayFilter ? dayFilter.value : '';
        const trainer = trainerFilter ? trainerFilter.value : '';
        let visible = 0;

        rows.forEach(function (row) {
            const haystack = normalize(row.getAttribute('data-search') || '');
            const rowType = row.getAttribute('data-type') || '';
            const rowDay = row.getAttribute('data-day') || '';
            const rowTrainer = row.getAttribute('data-trainer') || '';

            const matchesQuery = query === '' || haystack.includes(query);
            const matchesType = type === '' || rowType === type;
            const matchesDay = day === '' || rowDay === day;
            const matchesTrainer = trainer === '' || rowTrainer === trainer;

            const show = matchesQuery && matchesType && matchesDay && matchesTrainer;
            row.hidden = !show;
            if (show) {
                visible += 1;
            }
        });

        if (countEl) {
            countEl.textContent = visible + ' de ' + rows.length + ' aulas';
        }

        if (emptyEl) {
            emptyEl.hidden = visible > 0;
        }
    }

    function clearFilters() {
        if (searchInput) {
            searchInput.value = '';
        }
        if (typeFilter) {
            typeFilter.value = '';
        }
        if (dayFilter) {
            dayFilter.value = '';
        }
        if (trainerFilter) {
            trainerFilter.value = '';
        }
        filterClasses();
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterClasses);
    }
    if (typeFilter) {
        typeFilter.addEventListener('change', filterClasses);
    }
    if (dayFilter) {
        dayFilter.addEventListener('change', filterClasses);
    }
    if (trainerFilter) {
        trainerFilter.addEventListener('change', filterClasses);
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', clearFilters);
    }

    filterClasses();
})();
