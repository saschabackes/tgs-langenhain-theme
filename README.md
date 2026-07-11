# TGS Langenhain — WordPress Block Theme

Modernes Block Theme für die **TGS 1886 Langenhain e.V.** — Sportverein im Taunus.

## Design

- **Farbwelt:** Waldgrün (#3D5A40) + Creme (#F8F6F0)
- **Schriften:** Inter (Body) + Libre Baskerville (Überschriften)
- **Ansatz:** Nutzwert-fokussiert — Kurse auf einen Blick, Ansprechpartner finden, Mitglied werden, Sponsoren sichtbar

## Seitentypen

| Seite | Template | Beschreibung |
|-------|----------|--------------|
| Startseite | `front-page.html` | Hero, Kurstabelle, Abteilungen, News, Ansprechpartner, Sponsoren |
| Kursübersicht | `archive-tgs_kurs.html` | Alle Kurse sortiert nach Kategorien mit Filterchips |
| Kurs-Detail | `single-tgs_kurs.html` | Steckbrief, Beschreibung, Anmeldeformular, verwandte Kurse |
| Sportstätte | `single-tgs_sportstaette.html` | Adresse, Ausstattung, Belegungsplan, verwandte Kurse |
| Abteilung | `single-tgs_abteilung.html` | Abteilungsseite (z.B. Radsport) |
| Newsartikel | `single.html` | Standard-Blogpost mit Ansprechpartner + Sponsoren |

## Custom Post Types

### `tgs_kurs` — Kurse & Trainings
Meta-Felder: Wochentag, Uhrzeit (Start/Ende), Ort, Max. Teilnehmer, Status (frei/Warteliste), Zielgruppe, Ansprechpartner (Name/E-Mail/Tel), Mitbringen.

Taxonomy: `tgs_kurs_kategorie` (Fitness-Kurse, Fitness-Trainings, Kinder & Jugend, Senioren, Radsport).

### `tgs_sportstaette` — Sportstätten
Meta-Felder: Adresse, PLZ/Ort, Maps-Link, Ausstattung, Barrierefreiheit, Parkplätze.

### `tgs_abteilung` — Abteilungen
Meta-Felder: Icon (Emoji), Abteilungsleitung, E-Mail, Stellvertreter.

### `tgs_anmeldung` — Kursanmeldungen (intern)
Meta-Felder: Kurs-ID, Name, E-Mail, Telefon, Nachricht, Status, Datum.
Nicht öffentlich — nur im Admin sichtbar.

## Kursanmeldung

Shortcode `[tgs_anmeldung kurs_id="123"]` oder automatisch auf Kurs-Detailseiten.

**Ablauf:**
1. Besucher füllt Formular aus (Name, E-Mail, opt. Telefon + Nachricht)
2. System prüft Kapazität (max. Teilnehmer)
3. Bei freien Plätzen: Status = `bestaetigt`, Bestätigungsmail an Teilnehmer + Kursleiter
4. Bei ausgebucht: Status = `warteliste`, Wartelisten-Mail an Teilnehmer + Info an Kursleiter
5. Kurs-Status wird automatisch auf "Warteliste" gesetzt wenn voll
6. Duplikat-Check: gleiche E-Mail + gleicher Kurs = Ablehnung

## Installation

1. Theme-Ordner nach `wp-content/themes/tgs-langenhain-theme/` kopieren
2. Im WordPress-Backend unter *Design → Themes* aktivieren
3. Unter *Design → Editor* (Full Site Editing) Header/Footer anpassen
4. Logo unter *Einstellungen → Allgemein → Website-Logo* hochladen
5. Navigation unter *Design → Editor → Navigation* erstellen

### Voraussetzungen
- WordPress 6.4+
- PHP 8.0+
- Kein Plugin nötig — alles im Theme enthalten

## Entwicklung

```bash
# Repository klonen
git clone https://github.com/saschabackes/tgs-langenhain-theme.git

# In WordPress-Theme-Verzeichnis verlinken (Entwicklung)
ln -s /pfad/zum/repo /pfad/zu/wordpress/wp-content/themes/tgs-langenhain-theme
```

### Branching
- `main` — Produktions-Branch, nur getestete Änderungen
- `develop` — Entwicklungs-Branch für neue Features
- Feature-Branches: `feature/kursanmeldung`, `fix/logo-rendering`, etc.

### Releases
- Versionierung nach SemVer (0.1.0, 0.2.0, 1.0.0)
- CHANGELOG.md pflegen bei jedem Release
- GitHub Releases mit ZIP-Download erstellen

## Roadmap

- [x] Theme-Grundgerüst (theme.json, CPTs, Meta-Felder)
- [x] Kursanmeldung mit Warteliste + E-Mail
- [ ] Block Patterns für alle Seitentypen
- [ ] Frontend CSS (theme.css) mit Entwurf-P-Design
- [ ] Responsive / Mobile Optimierung
- [ ] Content-Migration (bestehende Kurse/Seiten übernehmen)
- [ ] Sponsoren als eigener CPT oder Widget
- [ ] Staging-Umgebung aufsetzen
- [ ] Go-Live

## Lizenz

GPL-2.0-or-later
