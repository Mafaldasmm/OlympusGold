(function () {
    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function initTableFilters() {
        const searchInput = document.getElementById('equipment-manage-search');
        const typeFilter = document.getElementById('equipment-manage-filter-type');
        const statusFilter = document.getElementById('equipment-manage-filter-status');
        const clearBtn = document.getElementById('equipment-manage-filter-clear');
        const countEl = document.getElementById('equipment-manage-filter-count');
        const emptyEl = document.getElementById('equipment-manage-filter-empty');
        const rows = document.querySelectorAll('#equipment-manage-table tbody tr[data-equipment-row]');

        if (!rows.length) {
            return;
        }

        function filterRows() {
            const query = normalize(searchInput ? searchInput.value : '');
            const type = typeFilter ? typeFilter.value : '';
            const status = statusFilter ? statusFilter.value : '';
            let visible = 0;

            rows.forEach(function (row) {
                const haystack = normalize(row.getAttribute('data-search') || '');
                const rowType = row.getAttribute('data-type') || '';
                const rowStatus = row.getAttribute('data-status') || '';

                const matchesQuery = query === '' || haystack.includes(query);
                const matchesType = type === '' || rowType === type;
                const matchesStatus = status === '' || rowStatus === status;
                const show = matchesQuery && matchesType && matchesStatus;

                row.hidden = !show;
                if (show) {
                    visible += 1;
                }
            });

            if (countEl) {
                countEl.textContent = visible + ' de ' + rows.length + ' equipamentos';
            }

            if (emptyEl) {
                emptyEl.hidden = visible > 0;
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterRows);
        }
        if (typeFilter) {
            typeFilter.addEventListener('change', filterRows);
        }
        if (statusFilter) {
            statusFilter.addEventListener('change', filterRows);
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                }
                if (typeFilter) {
                    typeFilter.value = '';
                }
                if (statusFilter) {
                    statusFilter.value = '';
                }
                filterRows();
            });
        }

        filterRows();
    }

    function initAvailabilityFilters() {
        const searchInput = document.getElementById('equipment-availability-search');
        const typeFilter = document.getElementById('equipment-availability-filter-type');
        const statusFilter = document.getElementById('equipment-availability-filter-status');
        const clearBtn = document.getElementById('equipment-availability-filter-clear');
        const countEl = document.getElementById('equipment-availability-filter-count');
        const emptyEl = document.getElementById('equipment-availability-filter-empty');
        const items = document.querySelectorAll('#equipment-availability-list [data-equipment-item]');

        if (!items.length) {
            return;
        }

        function filterItems() {
            const query = normalize(searchInput ? searchInput.value : '');
            const type = typeFilter ? typeFilter.value : '';
            const status = statusFilter ? statusFilter.value : '';
            let visible = 0;

            items.forEach(function (item) {
                const haystack = normalize(item.getAttribute('data-search') || '');
                const rowType = item.getAttribute('data-type') || '';
                const rowStatus = item.getAttribute('data-status') || '';

                const matchesQuery = query === '' || haystack.includes(query);
                const matchesType = type === '' || rowType === type;
                const matchesStatus = status === '' || rowStatus === status;
                const show = matchesQuery && matchesType && matchesStatus;

                item.hidden = !show;
                if (show) {
                    visible += 1;
                }
            });

            if (countEl) {
                countEl.textContent = visible + ' de ' + items.length + ' equipamentos';
            }

            if (emptyEl) {
                emptyEl.hidden = visible > 0;
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterItems);
        }
        if (typeFilter) {
            typeFilter.addEventListener('change', filterItems);
        }
        if (statusFilter) {
            statusFilter.addEventListener('change', filterItems);
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                }
                if (typeFilter) {
                    typeFilter.value = '';
                }
                if (statusFilter) {
                    statusFilter.value = '';
                }
                filterItems();
            });
        }

        filterItems();
    }

    initTableFilters();
    initAvailabilityFilters();
})();
