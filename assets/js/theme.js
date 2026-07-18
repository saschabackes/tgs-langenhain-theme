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
        // .tgs-kurs-row = Kurstabelle, .tgs-filter-item = alles andere
        // (z. B. Tour-Karten) — dasselbe Bauteil, zwei Anwendungen.
        const rows = document.querySelectorAll('.tgs-kurs-row, .tgs-filter-item');
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

    /**
     * Tour-Karte: lädt Leaflet (aus dem Theme) und die Kartenkacheln (von
     * OpenStreetMap) erst auf Klick. Vorher steht dort der selbst gerenderte
     * SVG-Streckenverlauf — kein externer Request, kein IP-Transfer.
     */
    function initTourMap() {
        var boxes = document.querySelectorAll('.tgs-tour-map[data-track]');
        if (!boxes.length) return;

        function loadAsset(tag, attrs) {
            return new Promise(function (resolve, reject) {
                var el = document.createElement(tag);
                Object.keys(attrs).forEach(function (k) { el.setAttribute(k, attrs[k]); });
                el.onload = resolve;
                el.onerror = reject;
                document.head.appendChild(el);
            });
        }

        function json(box, name, fallback) {
            try { return JSON.parse(box.getAttribute(name)); } catch (e) { return fallback; }
        }

        boxes.forEach(function (box) {
            var btn = box.querySelector('.tgs-tour-map-load');
            if (!btn) return;

            btn.addEventListener('click', function () {
                var track = json(box, 'data-track', []);
                if (!track.length) return;

                btn.disabled = true;
                btn.textContent = 'Karte wird geladen …';

                var css = window.L ? Promise.resolve() : loadAsset('link', {
                    rel: 'stylesheet', href: box.getAttribute('data-css')
                });
                var js = window.L ? Promise.resolve() : loadAsset('script', {
                    src: box.getAttribute('data-js')
                });

                Promise.all([css, js]).then(function () {
                    var canvas = document.createElement('div');
                    canvas.className = 'tgs-tour-map-canvas';
                    box.innerHTML = '';
                    box.appendChild(canvas);
                    box.classList.add('is-loaded');

                    var map = L.map(canvas, { scrollWheelZoom: false });
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>-Mitwirkende'
                    }).addTo(map);

                    var line = L.polyline(track, { color: '#3D5A40', weight: 4, opacity: .9 }).addTo(map);
                    map.fitBounds(line.getBounds(), { padding: [24, 24] });

                    var start = json(box, 'data-start', null);
                    var ende  = json(box, 'data-ende', null);
                    var rund  = box.getAttribute('data-rund') === '1';
                    var titel = box.getAttribute('data-title') || 'Tour';

                    if (start && start.length === 2) {
                        L.circleMarker(start, {
                            radius: 7, color: '#fff', weight: 2, fillColor: '#c8873f', fillOpacity: 1
                        }).addTo(map).bindPopup(rund ? 'Start & Ziel: ' + titel : 'Start: ' + titel);
                    }
                    if (!rund && ende && ende.length === 2) {
                        L.circleMarker(ende, {
                            radius: 7, color: '#fff', weight: 2, fillColor: '#3D5A40', fillOpacity: 1
                        }).addTo(map).bindPopup('Ziel');
                    }
                }).catch(function () {
                    btn.disabled = false;
                    btn.textContent = 'Karte konnte nicht geladen werden — nochmal versuchen';
                });
            });
        });
    }

    /**
     * Teilen: native Web-Share-API (Handy) oder Aufklapp-Menü (Desktop).
     * Kein Fremd-Skript, keine Datenübertragung beim Laden — erst wenn der
     * Nutzer aktiv teilt.
     */
    function initTeilen() {
        var boxes = document.querySelectorAll('.tgs-teilen');
        if (!boxes.length) return;
        var canShare = typeof navigator.share === 'function';

        boxes.forEach(function (box) {
            var toggle = box.querySelector('.tgs-teilen-toggle');
            var menu = box.querySelector('.tgs-teilen-menu');
            if (!toggle || !menu) return;

            function closeMenu() {
                menu.setAttribute('hidden', '');
                toggle.setAttribute('aria-expanded', 'false');
            }
            function openMenu() {
                menu.removeAttribute('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            }

            toggle.addEventListener('click', function (e) {
                if (canShare) {
                    e.preventDefault();
                    navigator.share({
                        title: box.getAttribute('data-title') || document.title,
                        text: box.getAttribute('data-text') || '',
                        url: box.getAttribute('data-url') || location.href
                    }).catch(function () { /* Nutzer hat abgebrochen – ok */ });
                } else {
                    menu.hasAttribute('hidden') ? openMenu() : closeMenu();
                }
            });

            var copy = box.querySelector('.tgs-teilen-copy');
            if (copy) {
                copy.addEventListener('click', function () {
                    var url = copy.getAttribute('data-url');
                    var done = function () {
                        var alt = copy.textContent;
                        copy.textContent = 'Kopiert ✓';
                        setTimeout(function () { copy.textContent = alt; closeMenu(); }, 1400);
                    };
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(url).then(done, function () { window.prompt('Link kopieren:', url); });
                    } else {
                        window.prompt('Link kopieren:', url);
                    }
                });
            }

            // Klick außerhalb schließt das Menü.
            document.addEventListener('click', function (e) {
                if (!box.contains(e.target)) closeMenu();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeMenu();
            });
        });
    }

    /**
     * Termine-Agenda: nach Typ filtern (Kurse/Handball/…). Leere Tage UND
     * Wochen-Überschriften klappen automatisch weg.
     */
    function initTermineFilter() {
        var box = document.querySelector('.tgs-chip-row[data-termine-filter]');
        if (!box) return;
        var agenda = box.closest('.tgs-agenda');
        if (!agenda) return;
        var chips = box.querySelectorAll('.tgs-chip');

        function apply(f) {
            agenda.querySelectorAll('.tgs-heute-item[data-typ]').forEach(function (it) {
                it.classList.toggle('is-hidden', f !== 'alle' && it.getAttribute('data-typ') !== f);
            });
            // Tage ohne sichtbares Item ausblenden
            agenda.querySelectorAll('.tgs-agenda-day').forEach(function (day) {
                var any = day.querySelector('.tgs-heute-item:not(.is-hidden)');
                day.classList.toggle('is-hidden', !any);
            });
            // Wochen-Label ausblenden, wenn bis zum nächsten Label kein sichtbarer Tag
            agenda.querySelectorAll('.tgs-agenda-week').forEach(function (wk) {
                var n = wk.nextElementSibling, visible = false;
                while (n && !n.classList.contains('tgs-agenda-week')) {
                    if (n.classList.contains('tgs-agenda-day') && !n.classList.contains('is-hidden')) { visible = true; break; }
                    n = n.nextElementSibling;
                }
                wk.classList.toggle('is-hidden', !visible);
            });
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chips.forEach(function (c) { c.classList.remove('active'); });
                chip.classList.add('active');
                apply(chip.getAttribute('data-f') || 'alle');
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
        initTourMap();
        initTeilen();
        initTermineFilter();
    });
})();
