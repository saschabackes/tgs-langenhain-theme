/**
 * TGS Langenhain — Theme JavaScript
 */

(function () {
    'use strict';

    /**
     * Filter Chips für die Kursübersicht.
     * Unterstützt mehrere unabhängige Filtergruppen (z.B. Kategorie UND
     * Zielgruppe), die per UND verknüpft werden. Jede Chip-Reihe trägt
     * data-filter-group; jede Kurszeile ein data-<group> mit space-separaten
     * Slugs. Eine Zeile ist sichtbar, wenn sie in jeder Gruppe zum aktiven
     * Chip passt ("alle" = kein Filter).
     */
    function initFilterChips() {
        const rows = document.querySelectorAll('.tgs-kurs-row');
        if (!rows.length) return;

        const groups = document.querySelectorAll('.tgs-chip-row[data-filter-group]');
        if (!groups.length) return;

        const active = {};
        groups.forEach(function (g) { active[g.dataset.filterGroup] = 'alle'; });

        function apply() {
            rows.forEach(function (row) {
                let show = true;
                for (const group in active) {
                    const val = active[group];
                    if (val === 'alle') continue;
                    const tokens = (row.dataset[group] || '').split(/\s+/);
                    if (tokens.indexOf(val) === -1) { show = false; break; }
                }
                row.style.display = show ? '' : 'none';
            });
        }

        groups.forEach(function (g) {
            const group = g.dataset.filterGroup;
            const chips = g.querySelectorAll('.tgs-chip');
            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    chips.forEach(function (c) { c.classList.remove('active'); });
                    chip.classList.add('active');
                    active[group] = chip.dataset.filter || 'alle';
                    apply();
                });
            });
        });

        // Deep-Link: Filter aus URL-Hash übernehmen (#zielgruppe=frauen / #kategorie=fitness),
        // damit z.B. der Teaser auf der Startseite hierher vorgefiltert verlinken kann.
        const hash = location.hash.replace(/^#/, '');
        if (hash) {
            hash.split('&').forEach(function (pair) {
                const eq = pair.indexOf('=');
                if (eq < 0) return;
                const g = pair.slice(0, eq);
                const v = pair.slice(eq + 1);
                if (!active.hasOwnProperty(g)) return;
                const row = document.querySelector('.tgs-chip-row[data-filter-group="' + g + '"]');
                if (!row) return;
                const chip = [].find.call(row.querySelectorAll('.tgs-chip'), function (c) {
                    return c.dataset.filter === v;
                });
                if (chip) chip.click();
            });
        }
    }

    // Init on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        initFilterChips();
    });
})();
