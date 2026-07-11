/**
 * TGS Langenhain — Theme JavaScript
 */

(function () {
    'use strict';

    /**
     * Filter Chips for Kursübersicht
     * Toggles visibility of course rows by category
     */
    function initFilterChips() {
        const chips = document.querySelectorAll('.tgs-chip');
        if (!chips.length) return;

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                // Update active state
                chips.forEach(function (c) { c.classList.remove('active'); });
                chip.classList.add('active');

                const filter = chip.dataset.filter || 'alle';
                const rows = document.querySelectorAll('.tgs-kurs-row');

                rows.forEach(function (row) {
                    if (filter === 'alle') {
                        row.style.display = '';
                    } else {
                        row.style.display = row.dataset.kategorie === filter ? '' : 'none';
                    }
                });
            });
        });
    }

    // Init on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        initFilterChips();
    });
})();
