# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

## [0.3.4] — 2026-07-12

### Geändert
- Abteilungs-Hero: Icon-Badge deutlich vergrößert (50→76 px, Icon 42 px) für mehr Präsenz auf den Abteilungsseiten. Kleine Badges in Karten/Listen unverändert.

## [0.3.3] — 2026-07-12

### Geändert — Vereinseigenes Sport-Icon-Set statt Emojis
- Neues Inline-SVG-Icon-Set (inc/sport-icons.php): Hantel (Fitness), Hand+Ball (Handball), Schläger+Ball (Tischtennis), Fahrrad (Radsport) — einheitlicher Linienstil in TGS-Grün, im grün getönten Badge.
- Ersetzt die generischen Emojis überall: Abteilungs-Hero, Startseiten-Grid (jetzt mit Badge), Archiv-Karten und „Weitere Abteilungen"-Listen. Auto-Zuordnung per Slug/Titel; Emoji bleibt Fallback für unbekannte Abteilungen.

## [0.3.2] — 2026-07-12

### Geändert
- **Abteilungs-Hero kompakter**: Icon-Badge jetzt neben Titel + Untertitel (statt darüber gestapelt), weniger Padding, kleinere Schrift — deutlich weniger Höhe bei wenig Inhalt.
- Radsport-Inhalt: redundante `[tgs_chips]`-Zeile entfernt (Treffpunkt-Box zeigt Zeit/Ort bereits).

## [0.3.1] — 2026-07-12

### Hinzugefügt
- `[tgs_whatsapp]` — WhatsApp-Community-Karte mit Ein-Klick-Beitritt (wa.me mit vorformulierter Nachricht oder direktem Gruppenlink) und optionalem QR-Code. Breites Layout mit QR (Content) bzw. kompakt ohne QR (Sidebar). Wiederverwendbar; QR-Bild in assets/images/.
- QR-Code für Radsport erzeugt (`assets/images/tgs-whatsapp-radsport-qr.png`, wa.me an Olaf Bertko).

## [0.3.0] — 2026-07-12

### Hinzugefügt — Wiederverwendbare Content-Bausteine (inc/content-blocks.php)
- `[tgs_chips]A | B | C[/tgs_chips]` — Fakten-Chip-Reihe
- `[tgs_infobox]` + `[tgs_infospalte titel="" gross=""]` — mehrspaltige Info-Box (z. B. Treffpunkt & Ausrüstung)
- `[tgs_gruppen]` + `[tgs_gruppe name="" grad="" hinweis=""]` — Karten-Raster für Gruppen/Angebote mit Schwierigkeits-Chip
- `[tgs_cta_box titel="" text="" button="" url="" farbe="gruen|whatsapp"]` — hervorgehobene Aktions-Box (z. B. WhatsApp-Community)
- Alle mit wpautop-sicherer Inhaltsbereinigung; in Fix-Liste aufgenommen

### Geändert — Abteilungs-Detailseite
- Hero: redundantes Logo entfernt, Icon im grün getönten Badge, linksbündig, Untertitel breiter
- Layout: Sidebar sticky, Content-Spalte mit Serifen-Zwischenüberschriften (h2/h3) und größerem Lead-Absatz für reichen Content (Vorbild: Radsport)

## [0.2.5] — 2026-07-12

### Geändert — Kurs-Detailseite (Layout „Option C")
- **Ausrichtungs-Bug behoben**: leeres `<p></p>` von wpautop im Kopf und Body ausgeblendet — der „Jetzt anmelden"-Button fluchtet jetzt sauber rechts mit der Sidebar (vorher in die Mitte geschoben).
- **Anmeldeformular in die Content-Spalte integriert** statt als schmale, linksbündige Insel unten: `[tgs_anmeldung]` wird jetzt aus `[tgs_kurs_detail]` in der Content-Spalte gerendert (volle Breite, 2-spaltige Felder). `static`-Guard im Anmelde-Shortcode verhindert Doppel-Ausgabe; `[tgs_anmeldung]`-Sektion aus `single-tgs_kurs.html` entfernt.
- **Sidebar sticky** (bleibt beim Scrollen sichtbar), Body-Spalten leicht großzügiger (280px, 2.5rem gap).

## [0.2.4] — 2026-07-12

### Geändert
- **Logo im Seitenkopf komplett entfernt.** Wasserzeichen (0.2.3) wieder raus; zusätzlich das per `[tgs_logo]`-Shortcode gerenderte Logo aus den Archiv-Vorlagen (Abteilungen, Sportstätten) und das `wp:site-logo` aus dem Kurse-Archiv entfernt. CSS blendet sicherheitshalber jedes Bild im Seitenkopf aus (`.tgs-page-hero img`), damit es auch ohne Vorlagen-Reset sofort greift. Seitenkopf zeigt jetzt nur Titel + Untertitel.

## [0.2.3] — 2026-07-12

### Geändert
- **Seitenkopf (page-hero)**: kleines, redundantes zweites Logo rechts entfernt. Stattdessen das Wappen als dezentes Wasserzeichen (~6 % Deckkraft) hinter dem Titel, am rechten Rand auslaufend. Logo bleibt allein Header + Startseiten-Hero vorbehalten. Reines CSS, greift ohne Vorlagen-Reset.

## [0.2.2] — 2026-07-12

### Geändert
- **Abteilungen-Übersicht neu gestaltet**: bildschirmbreite Listenzeilen → 2-spaltiges Karten-Raster (`[tgs_abteilungen_detail_liste]`). Icon im grün getönten Badge, Name als Serife, Beschreibung, Fuß mit Ansprechpartner + „Zur Abteilung →". Kein toter Raum mehr, Hover-Lift. Mobile einspaltig.

## [0.2.1] — 2026-07-12

### Geändert
- **Hero-Layout „Option A"**: Claim/Text jetzt linksbündig als These, Wappen als Anker rechts (`justify-content: space-between` + `order`), damit die vorher freie Fläche rechts natürlich gefüllt wird. Auf Mobile weiterhin Wappen oben, Text darunter.

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
