# CLAUDE.md — Projektkontext für Claude Code

## Projekt
WordPress Block Theme für die **TGS 1886 Langenhain e.V.** — Sportverein im Taunus (Hofheim-Langenhain, MTK, Hessen). Vereinswebsite-Redesign von Grund auf.

**Repo:** https://github.com/saschabackes/tgs-langenhain-theme
**Staging:** http://staging.tgs-langenhain.de
**Live (alt):** https://tgs-langenhain.de/home/
**Projektleitung:** Sascha Backes (GitHub: saschabackes)

## Technischer Stack
- WordPress 7.0.1, Block Theme (Full Site Editing)
- PHP 7.4 (Einschränkung wegen alter Nextcloud-Instanz auf demselben Webspace)
- MariaDB 10.6
- Hosting: DomainFactory
- Keine externen Plugins — alles im Theme

## Design-Entscheidungen (Entwurf P)
- **Farbwelt:** Waldgrün (#3D5A40) + Creme (#F8F6F0)
- **Schriften:** Inter (Body, 14px) + Libre Baskerville (Überschriften)
- **Designphilosophie:** Nutzwert-fokussiert, nicht Image-Broschüre
  - Kurse sofort sichtbar (Tabelle mit Filter, nicht Karten)
  - Ansprechpartner auf der Startseite
  - "Mitglied werden" prominent aber nicht überall (Nav-Button + 1x CTA-Block)
  - Sponsoren sichtbar auf jeder Seite
  - Keine "140+ Jahre / 4 Abteilungen"-Statistik-Blöcke (zu nichtssagend für ~5000 Einzugsgebiet)
- **Logo:** TGS-Wappen (Schildform), schwarz auf weiß und weiß auf dunkel. PNG in assets/images/. EPS-Quelle vorhanden. SVG-Konvertierung ausstehend.

## Architektur

### Custom Post Types
- `tgs_kurs` — Kurse & Trainings (Taxonomy: `tgs_kurs_kategorie`)
- `tgs_sportstaette` — Sportstätten (WBH, Sportplatz)
- `tgs_abteilung` — Abteilungen (Fitness, Handball, Tischtennis, Radsport)
- `tgs_anmeldung` — Kursanmeldungen (intern, nicht öffentlich)

### Meta-Felder (Kurse)
`_tgs_wochentag`, `_tgs_uhrzeit`, `_tgs_uhrzeit_ende`, `_tgs_ort`, `_tgs_status` (frei|warteliste), `_tgs_max_teilnehmer`, `_tgs_zielgruppe`, `_tgs_ansprechpartner`, `_tgs_ansprechpartner_email`, `_tgs_ansprechpartner_tel`, `_tgs_mitbringen`

### Shortcodes (inc/shortcodes.php)
- `[tgs_kurstabelle]` — Kurstabelle mit Filterchips (limit, kategorie, kompakt)
- `[tgs_abteilungen]` — Abteilungen-Grid (4 Karten)
- `[tgs_ansprechpartner]` — Kontaktkarten aus Abteilungsdaten
- `[tgs_sponsoren]` — Sponsorenleiste (aktuell hardcoded)
- `[tgs_kurs_detail]` — Kurs-Steckbrief (Header + 2-Spalten-Body + verwandte Kurse)
- `[tgs_kurse_in_ort ort="..."]` — Belegungsplan für Sportstätte
- `[tgs_anmeldung]` — Anmeldeformular mit Warteliste
- `[tgs_logo color="black|white" height="44"]` — Logo-Rendering
- `[tgs_navigation]` — Hauptnavigation aus WP-Menü
- `[tgs_sportstaette_detail]` — Sportstätten-Detailseite
- `[tgs_sportstaetten_liste]` — Sportstätten-Übersicht
- `[tgs_abteilung_detail]` — Abteilungs-Detailseite (Hero ohne Logo, Icon-Badge, Sidebar sticky)
- `[tgs_abteilungen_detail_liste]` — Abteilungen-Übersicht (2-spaltiges Karten-Raster)

### Content-Bausteine (inc/content-blocks.php, ab v0.3.0)
Wiederverwendbar in Beitragsinhalten (Abteilungen etc.), wpautop-sicher:
- `[tgs_chips]A | B | C[/tgs_chips]` — Fakten-Chip-Reihe
- `[tgs_infobox][tgs_infospalte titel="" gross=""]…[/tgs_infospalte]…[/tgs_infobox]` — mehrspaltige Info-Box
- `[tgs_gruppen][tgs_gruppe name="" grad="" hinweis=""]…[/tgs_gruppe]…[/tgs_gruppen]` — Karten-Raster mit Schwierigkeits-Chip
- `[tgs_cta_box titel="" text="" button="" url="" farbe="gruen|whatsapp"]` — hervorgehobene Aktions-Box
- `[tgs_whatsapp titel="" text="{mitglieder}…" mitglieder="" tel="49…" nachricht="" link="" qr="datei.png" button=""]` — WhatsApp-Community-Karte (wa.me oder Gruppenlink) mit optionalem QR-Code (QR-PNGs in assets/images/, mit `segno` erzeugt)
- Inhalte je Container-Shortcode auf **einer Zeile** schreiben (wpautop). Container nutzen `tgs_clean_shortcode_content()`.

### Templates (templates/)
- `front-page.html` — Startseite
- `archive-tgs_kurs.html` — Kursübersicht
- `single-tgs_kurs.html` — Kurs-Detail + Anmeldeformular
- `single-tgs_sportstaette.html` — Sportstätte mit Belegungsplan
- `archive-tgs_sportstaette.html` — Sportstätten-Übersicht
- `single-tgs_abteilung.html` — Abteilungsseite
- `archive-tgs_abteilung.html` — Abteilungen-Übersicht
- `single.html` — Newsartikel
- `page.html` — Standardseiten
- `index.html` — Fallback

### Template Parts (parts/)
- `header.html` — Topbar + Nav (Logo, Menü, CTA)
- `footer.html` — Footer mit Logo + Links
- `mitglied-cta.html` — "Mitglied werden"-Block
- `sponsor-bar.html` — Sponsorenleiste (wird durch Shortcode ersetzt)

## Bekannte Probleme / Workarounds

### wpautop
WordPress fügt automatisch `<p>`-Tags in Shortcode-Output ein. Unsere Shortcodes bauen deshalb HTML als String (nicht ob_start/Template-PHP). Für Elemente innerhalb von `<a>`-Tags verwenden wir `<span>` statt `<div>` und `<div onclick>` statt `<a>`. Siehe Fix in functions.php (`tgs_fix_shortcode_wpautop`).

### Block-Template-Caching
WordPress speichert Template Parts nach dem ersten Laden in der Datenbank. Bei Theme-Updates muss man im Site Editor unter Vorlagen → Header → "Zurücksetzen" klicken, damit die neue Datei greift.

### PHP 7.4
Der Server läuft auf PHP 7.4 wegen einer alten Nextcloud 20-Instanz. Theme-Code darf keine PHP 8+ Features verwenden. `style.css` deklariert `Requires PHP: 7.4`.

## Deployment

### Aktuell (manuell)
1. Theme als ZIP packen
2. Im WP-Backend: Design → Themes → altes löschen → neues ZIP hochladen → aktivieren

### Deploy-Script (deploy.sh)
```bash
brew install lftp  # einmalig
./deploy.sh        # synct per FTP, nur geänderte Dateien
```

### FTP-Zugangsdaten
- Host: ftp.tgs-langenhain.de
- User: siehe `.env` (nicht im Repo)
- Pfad: /wordpress/wp-content/themes/tgs-langenhain-theme

### GitHub Token
Siehe `.env` oder `gh auth status` lokal. Nicht im Repo speichern.

## Echter Content

### Sponsoren
Mobau Braun, KP International Immobilien, Domotec Dienstleistungen, Salon Dörr & Neumann, Optik Waller, Simon Haustechnik

### Sportstätten
- Wilhelm-Busch-Halle (WBH) — Turnhalle, 65719 Hofheim-Langenhain
- Sportplatz "Zu den Eichen" — Sportplatzstraße 13
- Vereinsgaststätte "Da Luca" — Pizza & Pasta, am Sportplatz

### Abteilungen
1. Fitness & Turnen (13 Kurse)
2. Handball (HSG EppLa)
3. Tischtennis
4. Radsport (Leitung: Olaf Bertko, bertko@gmx.de, 0173 8966223)

### Kurse (alle 20 sind auf Staging importiert)
**Fitness-Kurse:** Hatha Yoga (Di 19:00), Pilates (Mo 18:30, Warteliste), Zumba (Mi 19:30), Body Complete (Do 18:00), Elements Training (Do 19:30), Best of Fitness (Mi 09:00), Rückenfreundlich (Mo 09:30), Männer-Fit (Fr 18:00)
**Fitness-Trainings:** Allg. Gymnastik Frauen (Mi 20:00), Die Borzeler (Di 20:00), Power Frauen (Do 20:00), Nordic Walking (Di 17:00)
**Kinder:** Kinderturnen 3-6 (Fr 15:30), Eltern-Kind (Mi 09:30), Powerhour Kids (Fr 15:00), Zappelgruppe (Mi 10:15), Kids-on-Bike MTB (Sa 10:00)
**Senioren:** Aktiv bis 100 (Di 10:00), Die Oldies (Do 19:00)

## Design-Mockups
HTML-Mockups aus der Designphase (Referenz, nicht Code):
- `tgs_entwurf_P_komplett.html` — Finaler Entwurf mit 5 Seitentypen
- `tgs_abteilung_radsport.html` — Radsport-Abteilungsseite als Vorlage

## Offene Issues
Siehe https://github.com/saschabackes/tgs-langenhain-theme/issues

Priorität:
1. Visual Polish — Abstände, Schriftgrößen, Hover-States an Mockups angleichen
2. Responsive testen und fixen (CSS ist gebaut aber nicht getestet)
3. Kursanmeldung end-to-end testen (E-Mail-Versand, Warteliste)
4. Sponsoren-Logos statt nur Text
5. Logo als SVG konvertieren
6. Staging mit .htpasswd schützen

## Konventionen
- Commits: `feat:`, `fix:`, `chore:`, `docs:`, `content:` Prefix
- Issues referenzieren: `Closes #1` in Commit-Message
- CSS-Klassen: Prefix `tgs-` (z.B. `tgs-hero`, `tgs-kurs-tabelle`)
- PHP-Funktionen: Prefix `tgs_` (z.B. `tgs_shortcode_kurstabelle`)
- Shortcodes: `[tgs_...]`
- Meta-Felder: `_tgs_...` (mit Unterstrich-Prefix für private meta)
