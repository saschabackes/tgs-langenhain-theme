# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

## [0.5.0] — 2026-07-12

### Hinzugefügt — Kurs-Bewertungen (optional, moderiert)
- Neue Kursdetails-Schalter: „Bewertungen" (an/aus) und „Bewertungen öffentlich zeigen" (ja/nein).
- **Bewerten nur für verifizierte Teilnehmer** (bestätigte Anmeldung): Zugang über „Meine Kurse"/persönlichen Link (Anmeldungs-Token = Nachweis). Sterne 1–5 + optionaler Kommentar, **Name optional (leer = Anonym)**, eine Bewertung pro Teilnehmer & Kurs.
- **Moderation**: Jede Bewertung ist zunächst unsichtbar; Freigabe/Verbergen/Löschen über die Box „Bewertungen zu diesem Kurs" im Kurs-Editor.
- **Öffentliche Anzeige** auf der Kursseite (wenn aktiv + freigegeben): Ø-Sterne, Anzahl, freigegebene Bewertungen (Name/„Anonym" + Kommentar).
- Neues Modul inc/kurs-bewertung.php, CPT `tgs_bewertung` (intern), Shortcode `[tgs_kurs_bewertungen]`.

## [0.4.4] — 2026-07-12

### Hinzugefügt — Offene Kurse (ohne Anmeldung)
- Neues Feld „Anmeldung" in den Kursdetails: „Anmeldung erforderlich" (Standard) oder **„Offener Kurs – keine Anmeldung nötig"** (Drop-in).
- Offener Kurs: Kursseite zeigt statt Formular einen „einfach vorbeikommen"-Hinweis; kein „Jetzt anmelden"-Button; Status „✓ Offen"; im Backend keine Teilnehmerverwaltung (Hinweis in der Anmeldungs-Box).

## [0.4.3] — 2026-07-12

### Hinzugefügt — Stiller Teilnehmer-Import (laufende Kurse)
- In der Kurs-Box „Anmeldungen zu diesem Kurs" → Button **„＋ Teilnehmer manuell hinzufügen (ohne E-Mail)"**: Öffnet eine Seite, auf der bestehende Teilnehmer (je Zeile „Name, E-Mail" — E-Mail optional) direkt als angemeldet oder auf Warteliste eingetragen werden. **Kein Double-Opt-In, keine E-Mails** — ideal für bereits laufende Kurse.
- Manuell importierte Teilnehmer (`_tgs_anm_manuell`) erhalten auch bei Entfernen/Nachrücken **keine** automatischen Mails (offline-verwaltet). Online-Neuanmeldungen laufen unverändert mit Bestätigungslink.

## [0.4.2] — 2026-07-12

### Hinzugefügt — Teilnehmerverwaltung
- **Kursleiter-Buttons im Backend**: In der Box „Anmeldungen zu diesem Kurs" je Person „Entfernen" (rückt Warteliste automatisch nach + Info-Mail) und bei Wartelisten-Einträgen „In Kurs aufnehmen" (setzt auf angemeldet + Mail). Ein Klick, via admin-post + Nonce.
- **„Meine Kurse" für Teilnehmer (ohne Konto)**: Die persönliche Status-Seite zeigt jetzt **alle** Anmeldungen derselben E-Mail (Status, Wartelisten-Platz, Abmelden je Kurs). Zusätzlich E-Mail-Zugangsformular → Magic-Link (7 Tage gültig) zur Übersicht.

## [0.4.1] — 2026-07-12

### Hinzugefügt / Geändert — Backend-Verwaltung
- **Anmeldungs-Übersicht direkt am Kurs**: Neue Meta-Box „Anmeldungen zu diesem Kurs" im Kurs-Editor — zeigt Belegung (X / Max), die Angemeldeten und die Warteliste (in Reihenfolge, nummeriert) mit Name/E-Mail/Telefon/Datum.
- Manuelles „Status"-Feld aus den Kursdetails entfernt (wird jetzt automatisch aus der Kapazität berechnet). „Max. Teilnehmer" mit Hinweis „leer = unbegrenzt".

## [0.4.0] — 2026-07-12

### Hinzugefügt — Kursanmeldung Phase 1 (Teilnehmer-Kern)
- **Double-Opt-In**: Anmeldung wird erst nach Klick auf den Bestätigungslink in der E-Mail gültig; Platz zählt erst ab Bestätigung. Zustände: unbestaetigt → bestaetigt | warteliste → storniert.
- **Warteliste mit Auto-Nachrücken**: Wird ein bestätigter Platz frei (Abmeldung), rückt automatisch die erste Person der Warteliste nach und wird benachrichtigt.
- **Status-/Abmelde-Link ohne Login**: Jede Anmeldung enthält einen persönlichen Link (Seite „Kurs-Status", Shortcode `[tgs_kurs_status]`) — Status (angemeldet / Warteliste + Position) jederzeit einsehbar, Selbst-Abmeldung möglich.
- **E-Mails**: Opt-in-Bestätigung, Anmelde-/Wartelisten-Bestätigung, Nachrück-Info an Teilnehmer; Benachrichtigungen an den Kursleiter (`_tgs_ansprechpartner_email`).
- Unbegrenzte Kurse (max leer/0): keine Warteliste, direkt angemeldet. Kurs-Meta `_tgs_status` wird automatisch synchronisiert.
- Seite „Kurs-Status" wird automatisch angelegt (beim nächsten Backend-Aufruf).

### Ausstehend
- Phase 2: Kursleiter-Rolle mit eingeschränktem Backend (nur eigene Kurse/Anmeldungen).

## [0.3.5] — 2026-07-12

### Geändert
- Handball-Icon neu: an das Standard-Handball-Piktogramm angelehnte Wurf-Silhouette (gefüllt) statt des unklaren „Hand+Ball". Icon-Helper unterstützt jetzt gemischte Stile (Linien-Icons + gefüllte Silhouette) mit eigener viewBox je Icon.

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
