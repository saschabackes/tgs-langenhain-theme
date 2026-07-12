# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

## [0.2.0] — 2026-07-12

### Geändert — Visual Redesign (Entwurf C „Emblem")
- **Header** invertiert auf Dunkelgrün (#2A3F2C) mit weißem Wappen als Markenträger; Topbar entfernt, Navigation von 12 px auf 16 px vergrößert, aktiver Menüpunkt weiß/fett
- **Startseiten-Hero** von schwerem grünem Block auf hellen Creme-Hero umgestellt: schwarzes Wappen groß als „These" links, Eyebrow + Serifen-Schlagzeile + zwei Buttons rechts
- **Luftigere Typo-Skala durchgängig**: Body 14 → 15 px, Section-Titel als Serife 22 px, Kurstabelle 12 → 15 px, Chips 10 → 13 px, mehr Zeilenabstand und Padding
- **Inhaltsbreite zentriert** (max. 1180 px) statt vollflächig gestreckt — via `padding-inline: max(...)` ohne Wrapper-Divs
- **Innere Seiten-Heroes** (Page-Hero, Abteilungs-Hero) auf hell umgestellt, damit unter dem dunklen Header kein „grün auf grün" stapelt; Abteilungs-Hero-Logo weiß → schwarz
- Karten (Abteilungen, Kontakt, Sponsoren, Sportstätten) mit größerem Radius, mehr Padding, Hover-Lift
- Responsive-Breakpoints an neue Skala angepasst (900/768/600/480 px)
- Version auf 0.2.0 (bustet CSS-Cache via `TGS_VERSION`)

## [0.1.0] — 2026-07-11

### Hinzugefügt
- Theme-Grundgerüst: `style.css`, `theme.json`, `functions.php`
- Designsystem in `theme.json`: Waldgrün-Palette, Inter + Libre Baskerville, Spacing-Scale
- Custom Post Type `tgs_kurs` mit Taxonomy `tgs_kurs_kategorie` und allen Meta-Feldern
- Custom Post Type `tgs_sportstaette` mit Adress- und Ausstattungs-Feldern
- Custom Post Type `tgs_abteilung` mit Leitungs-Feldern
- Custom Post Type `tgs_anmeldung` (intern) für Kursanmeldungen
- Kursanmeldung-Shortcode `[tgs_anmeldung]` mit Wartelisten-Logik und E-Mail-Benachrichtigungen
- Admin-Spalten für Kurse und Anmeldungen im Backend
- Meta-Box für Kursdetails im Classic Editor
- Block-Template `index.html` (Grundgerüst)
- Template Parts: `header.html`, `footer.html`, `sponsor-bar.html`, `mitglied-cta.html`
- Logo in schwarz (für helle Hintergründe) und weiß (für dunkle Hintergründe)
- README mit Projektdokumentation
