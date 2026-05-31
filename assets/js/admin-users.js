(function () {
    const userSearch = document.getElementById('user-search');
    const userRoleFilter = document.getElementById('user-filter-role');
    const userActiveFilter = document.getElementById('user-filter-active');
    const userClearBtn = document.getElementById('user-filter-clear');
    const userCountEl = document.getElementById('user-filter-count');
    const userRows = document.querySelectorAll('#users-table tbody tr[data-user-row]');

    const reviewSearch = document.getElementById('review-user-search');
    const reviewUserSelect = document.getElementById('review-user-select');
    const reviewClearBtn = document.getElementById('review-filter-clear');
    const reviewCountEl = document.getElementById('review-filter-count');
    const reviewRows = document.querySelectorAll('#reviews-table tbody tr[data-review-row]');
    const reviewsEmptyFiltered = document.querySelector('.reviews-filter-empty');
    const usersEmptyFiltered = document.querySelector('.users-filter-empty');

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function filterUsers() {
        if (!userRows.length) {
            return;
        }

        const query = normalize(userSearch ? userSearch.value : '');
        const role = userRoleFilter ? userRoleFilter.value : '';
        const active = userActiveFilter ? userActiveFilter.value : '';
        let visible = 0;

        userRows.forEach(function (row) {
            const haystack = normalize(row.getAttribute('data-search') || '');
            const rowRole = row.getAttribute('data-role') || '';
            const rowActive = row.getAttribute('data-active') || '';

            const matchesQuery = query === '' || haystack.includes(query);
            const matchesRole = role === '' || rowRole === role;
            const matchesActive = active === '' || rowActive === active;

            const show = matchesQuery && matchesRole && matchesActive;
            row.hidden = !show;
            if (show) {
                visible += 1;
            }
        });

        if (userCountEl) {
            userCountEl.textContent = visible + ' de ' + userRows.length + ' utilizadores';
        }

        if (usersEmptyFiltered) {
            usersEmptyFiltered.hidden = visible > 0;
        }
    }

    function filterReviews() {
        if (!reviewRows.length) {
            return;
        }

        const query = normalize(reviewSearch ? reviewSearch.value : '');
        const userId = reviewUserSelect ? reviewUserSelect.value : '';
        let visible = 0;

        reviewRows.forEach(function (row) {
            const haystack = normalize(row.getAttribute('data-search') || '');
            const rowUserId = row.getAttribute('data-user-id') || '';

            const matchesQuery = query === '' || haystack.includes(query);
            const matchesUser = userId === '' || rowUserId === userId;

            const show = matchesQuery && matchesUser;
            row.hidden = !show;
            if (show) {
                visible += 1;
            }
        });

        if (reviewCountEl) {
            reviewCountEl.textContent = visible + ' de ' + reviewRows.length + ' avaliações';
        }

        if (reviewsEmptyFiltered) {
            reviewsEmptyFiltered.hidden = visible > 0;
        }
    }

    function clearUserFilters() {
        if (userSearch) {
            userSearch.value = '';
        }
        if (userRoleFilter) {
            userRoleFilter.value = '';
        }
        if (userActiveFilter) {
            userActiveFilter.value = '';
        }
        filterUsers();
    }

    function clearReviewFilters() {
        if (reviewSearch) {
            reviewSearch.value = '';
        }
        if (reviewUserSelect) {
            reviewUserSelect.value = '';
        }
        filterReviews();
    }

    if (userSearch) {
        userSearch.addEventListener('input', filterUsers);
    }
    if (userRoleFilter) {
        userRoleFilter.addEventListener('change', filterUsers);
    }
    if (userActiveFilter) {
        userActiveFilter.addEventListener('change', filterUsers);
    }
    if (userClearBtn) {
        userClearBtn.addEventListener('click', clearUserFilters);
    }

    if (reviewSearch) {
        reviewSearch.addEventListener('input', filterReviews);
    }
    if (reviewUserSelect) {
        reviewUserSelect.addEventListener('change', function () {
            const option = reviewUserSelect.selectedOptions[0];
            if (reviewSearch && option && option.dataset.label) {
                reviewSearch.value = option.dataset.label;
            }
            filterReviews();
        });
    }
    if (reviewClearBtn) {
        reviewClearBtn.addEventListener('click', clearReviewFilters);
    }

    document.querySelectorAll('[data-filter-user]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            const userId = link.getAttribute('data-filter-user') || '';
            const label = link.getAttribute('data-filter-label') || '';

            const reviewsDetails = document.getElementById('reviews-section');
            if (reviewsDetails && typeof reviewsDetails.open !== 'undefined') {
                reviewsDetails.open = true;
                reviewsDetails.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            if (reviewUserSelect) {
                reviewUserSelect.value = userId;
            }
            if (reviewSearch) {
                reviewSearch.value = label;
            }
            filterReviews();
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    const userParam = urlParams.get('user');
    if (userParam && reviewUserSelect) {
        reviewUserSelect.value = userParam;
        const option = reviewUserSelect.querySelector('option[value="' + userParam + '"]');
        if (reviewSearch && option) {
            reviewSearch.value = option.textContent.trim();
        }
        const reviewsDetails = document.getElementById('reviews-section');
        if (reviewsDetails) {
            reviewsDetails.open = true;
        }
    }

    function syncRoleApplyConfirm(form) {
        const select = form.querySelector('[name="new_role"]');
        if (!select) {
            return;
        }

        if (select.value === 'admin') {
            const userName = form.getAttribute('data-user-name') || 'este utilizador';
            form.setAttribute(
                'data-confirm',
                'Tens a certeza que queres atribuir o papel de Administrador a ' + userName
                    + '? Terá acesso total à gestão de utilizadores, aulas e avaliações.'
            );
            form.setAttribute('data-confirm-title', 'Atribuir papel Admin');
            form.setAttribute('data-confirm-ok', 'Aplicar');
        } else {
            form.removeAttribute('data-confirm');
            form.removeAttribute('data-confirm-title');
            form.removeAttribute('data-confirm-ok');
        }
    }

    document.querySelectorAll('.role-apply-form').forEach(function (form) {
        const select = form.querySelector('[name="new_role"]');
        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            syncRoleApplyConfirm(form);
        });
        syncRoleApplyConfirm(form);

        form.addEventListener('submit', function () {
            if (form.dataset.confirmBypass === '1') {
                form.removeAttribute('data-confirm');
                form.removeAttribute('data-confirm-title');
                form.removeAttribute('data-confirm-ok');
            }
        });
    });

    filterUsers();
    filterReviews();
})();
