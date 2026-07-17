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

    /**
     * Telefon-Links auf der Gaststätten-Seite: vor dem Wählen kurz nachfragen,
     * damit ein Klick nicht sofort den Anruf startet.
     */
    function initCallConfirm() {
        var links = document.querySelectorAll('.tgs-gs a[href^="tel:"]');
        links.forEach(function (a) {
            a.addEventListener('click', function (e) {
                var num = a.getAttribute('href').replace('tel:', '').replace(/^\+49/, '0');
                if (!window.confirm('Möchtest du die Gaststätte anrufen?\n' + num)) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Imagefilm: lädt YouTube (nocookie) erst beim Klick – vorher keine
     * Datenübertragung an YouTube.
     */
    function initVideoFacade() {
        var boxes = document.querySelectorAll('.tgs-gs-video[data-video]');
        boxes.forEach(function (box) {
            function load() {
                var id = box.getAttribute('data-video');
                if (!id) return;
                var iframe = document.createElement('iframe');
                iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id) + '?autoplay=1&rel=0';
                iframe.title = 'Imagefilm';
                iframe.setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
                iframe.setAttribute('allowfullscreen', '');
                iframe.setAttribute('frameborder', '0');
                box.innerHTML = '';
                box.appendChild(iframe);
                box.classList.add('is-playing');
            }
            box.addEventListener('click', load);
            box.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); load(); }
            });
        });
    }

    /**
     * Crawler-geschützte Namen (Trainer/Kursleitung) einsetzen.
     * Der Name steht base64-kodiert in data-n; Klartext erst per JS.
     */
    function initGuardNames() {
        document.querySelectorAll('.tgs-guard-name[data-n]').forEach(function (el) {
            try {
                el.textContent = decodeURIComponent(escape(atob(el.getAttribute('data-n'))));
                el.removeAttribute('data-n');
            } catch (e) { /* Fallback-Text bleibt stehen */ }
        });
    }

    /**
     * Kalender-Abo: Feed-Link in die Zwischenablage.
     * Für alle, bei denen der webcal:-Tipp nichts öffnet (Desktop, Google
     * Kalender) — dort fügt man die URL von Hand als Abo ein.
     */
    function initAboCopy() {
        document.querySelectorAll('.tgs-abo-copy[data-url]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-url');
                var done = function () {
                    var alt = btn.textContent;
                    btn.textContent = 'Kopiert ✓';
                    btn.classList.add('is-done');
                    setTimeout(function () {
                        btn.textContent = alt;
                        btn.classList.remove('is-done');
                    }, 2000);
                };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url).then(done, function () { window.prompt('Link kopieren:', url); });
                } else {
                    window.prompt('Link kopieren:', url);
                }
            });
        });
    }

    // Init on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        initFilterChips();
        initCallConfirm();
        initVideoFacade();
        initGuardNames();
        initAboCopy();
    });
})();
