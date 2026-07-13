## [0.19.0] — 2026-07-13

### Geändert — Datenschutz (DSGVO)
- Schriften (Inter, Libre Baskerville) werden jetzt **selbst gehostet** statt vom Google-CDN geladen. Damit wird beim Seitenaufruf keine Besucher-IP mehr an Google übertragen – die Datenschutzerklärung stimmt jetzt mit dem tatsächlichen Verhalten überein. woff2-Subsets (latin + latin-ext) liegen in `assets/fonts/`, eingebunden über `assets/css/fonts.css` (font-display:swap). Google-CDN-Einbindung in functions.php entfernt (Frontend + Editor).
- functions.php Doc-Header von veralteter 0.2.0 auf aktuelle Version korrigiert.

## [0.18.4] — 2026-07-13

### Behoben — Beitragsbild / Hero
- `add_theme_support('post-thumbnails')` ergänzt. Ohne diesen Theme-Support blendete der Block-Editor das „Beitragsbild"-Feld komplett aus – dadurch ließ sich weder für Sportstätten (Foto-Hero) noch für News-Beiträge ein Titelbild setzen. Das Feld erscheint jetzt in der Editor-Seitenleiste.

## [0.18.3] — 2026-07-13

### Behoben — Kurs-Detailseite & Abteilungs-Detailseite
- „Weitere Kurse" / „Weitere Abteilungen": Layout war verschoben, weil wpautop aus den Zeilenumbrüchen im Markup `<br>`-Tags in die `display:flex`-Einträge einfügte (Name/Zeit rutschten dadurch). Zwischenraum zwischen den Inline-Tags entfernt – die Einträge sitzen jetzt sauber (Name links, Zeit/Pfeil rechts).

## [0.18.2] — 2026-07-13

### Geändert — Gaststätte
- Zahlungshinweis (Bar/Karte) in die Schnell-Fakten-Tabelle oben übernommen (als 5. Feld „Bezahlung"). Das eigenständige Bezahl-Band darunter entfällt – es hatte allein keinen Mehrwert. Fakten-Grid jetzt 5-spaltig, auf Tablet 2-spaltig (Bezahl-Feld über volle Breite), auf dem Handy gestapelt.

## [0.18.1] — 2026-07-13

### Geändert — Startseite (Hero)
- Medaillon-Scheibe/Ring entfernt: Das Wappen steht jetzt frei (nur dezenter Schatten) und größer (300px). Wirkt cleaner. Responsiv 300→240→190px.

## [0.18.0] — 2026-07-13

### Geändert — Startseite (Hero)
- Hero neu ausbalanciert (Variante „Medaillon"): Statt `justify-content: space-between` (das Text und Logo an die Ränder zog und eine große Lücke in der Mitte ließ) jetzt ein 2-Spalten-Raster mit definiertem Abstand. Das Wappen sitzt vergrößert (220px) auf einer weichen, kreisrunden Medaillon-Scheibe mit dezentem Ring und füllt so seine Spalte, statt mittig zu schweben. Proportionen zwischen Textblock und Wappen stimmen jetzt.
- Responsiv angepasst: Medaillon skaliert mit (340→280→230px), auf dem Handy steht das Wappen zentriert über dem Text.

## [0.17.3] — 2026-07-12

### Behoben — Startseite (und alle Seiten)
- Zwischen den Vollbreiten-Abschnitten lag ein 16px-Abstand (WordPress-Block-Abstand), der den creme Body-Hintergrund als schmalen Streifen unter der Trennlinie zeigte. Die Bänder stoßen jetzt direkt aneinander; der Wechsel weiß/creme ist sauber.

## [0.17.2] — 2026-07-12

### Geändert — Startseite
- Abschnitts-Hintergründe wechseln jetzt sauber ab (weiß/creme): Kurse (weiß) → Abteilungen (creme) → Aktuelles (weiß) → Ansprechpartner (creme). Vorher hatten Abteilungen und Aktuelles denselben Hintergrund.
- Doppelte „Mitglied werden"-Aufforderung entfernt: Der Mitglied-werden-Banner am Seitenende der Startseite ist raus (der Button oben rechts in der Navigation bleibt).

## [0.17.1] — 2026-07-12

### Behoben
- News-Übersicht/Teaser: wpautop fügte zwischen den Karten <br>-Elemente ein (jede Karte endete mit Zeilenumbruch), wodurch das Karten-Raster versetzt/„wild" wirkte. Karten werden jetzt getrimmt und ohne Zwischenraum ausgegeben.

## [0.17.0] — 2026-07-12

### Hinzugefügt — News/Aktuelles neu aufgebaut
- Neues Modul inc/news.php mit drei Bausteinen: **[tgs_news_detail]** (Artikelseite mit Titelbild-Hero, Kategorie-Badge, Datum/Autor, lesbarer Typografie inkl. Zitat/Bild/Listen, „← Zu allen Beiträgen" und verwandten Beiträgen), **[tgs_news_liste]** (Übersicht als Karten) und **[tgs_news_teaser]** (Startseiten-Teaser als Karten).
- Templates verdrahtet: single.html (Beitrag) → schlanke Brotkrume + [tgs_news_detail]; index.html (Aktuelles-Übersicht) → Page-Hero + [tgs_news_liste]; Startseite → [tgs_news_teaser] statt Latest-Posts-Block.
- Brotkrume erkennt Beiträge (Startseite › Aktuelles › Titel). News bleiben normale WP-Beiträge (Gutenberg für den Inhalt); Empfehlung je Beitrag: Beitragsbild, Kategorie, Auszug.

## [0.16.1] — 2026-07-12

### Geändert — Kurs-Layout auf „Klassik+" (A)
- Auf Wunsch: klassischer Kopf mit „Jetzt anmelden"-Button + Status rechts, Inhalt links, „Auf einen Blick"-Box rechts. Anmelde-Karte und Foto-Hero wieder entfernt (einheitlicher Aufbau für alle Kurse). Das feste Template (Kurzbeschreibung, Über den Kurs, Das erwartet dich) bleibt bestehen.

## [0.16.0] — 2026-07-12

### Geändert — Kurs-Detailseite: festes Template + neues Layout
- **Festes Template gegen Wildwuchs:** neue Backend-Box „Kursbeschreibung" mit strukturierten Feldern (Kurzbeschreibung, Über den Kurs, Das erwartet dich). Kurs anlegen = Felder ausfüllen; Aufbau entsteht automatisch und immer gleich. Ist „Über den Kurs" leer, wird der klassische Inhaltsbereich als Fallback genutzt (bestehende Kurse bleiben intakt).
- **Layout B + C:** Sidebar-Anmelde-Karte als Standard (Status, freie Plätze, „Jetzt anmelden"-Button, Hinweis) statt CTA im Kopf; ist ein Beitragsbild gesetzt, erscheint automatisch ein Foto-Hero. Kurzbeschreibung als Lead, „Das erwartet dich" als Häkchen-Liste.
- „Auf einen Blick"-Box: linksbündiges Raster (aus 0.15.1).

## [0.15.1] — 2026-07-12

### Behoben
- Kurs-Detailseite: „Auf einen Blick"-Box – Werte waren rechtsbündig und wirkten bei Umbruch verschoben. Jetzt sauberes linksbündiges 2-Spalten-Raster (Label | Wert).

## [0.15.0] — 2026-07-12

### Hinzugefügt — Spielgemeinschafts-Banner (Handball / HSG EppLa)
- Neuer Shortcode [tgs_hsg]: markanter grüner Banner, der die Spielgemeinschaft visualisiert (TGS Langenhain ✕ TSG Eppstein → HSG EppLa) mit Kurztext und großem Button zur HSG. Attribute für Vereine, Name, Titel, Text, Button und URL. Ersetzt auf der Handball-Seite die generische CTA-Box.

## [0.14.0] — 2026-07-12

### Hinzugefügt — Impressum & Datenschutzerklärung
- Neue Shortcodes [tgs_impressum] und [tgs_datenschutz] für Rechtsseiten, gesetzt und lesbar (.tgs-legal). Impressum mit Vereinsdaten, Vorstand (§26 BGB), Registereintrag (Amtsgericht Frankfurt, VR 4433); Datenschutz auf die neue Seite zugeschnitten (Server-Logs, Kontaktformular, Kursanmeldung inkl. Kinder/Alter, Bewertungen, YouTube-nocookie, Google Fonts, Betroffenenrechte, Aufsichtsbehörde). Stammdaten zentral in inc/rechtstexte.php.
- Hinweis: Vorlage/Entwurf, vor Veröffentlichung prüfen (lassen).

## [0.13.0] — 2026-07-12

### Behoben / Hinzugefügt
- **Sportstätten-Übersicht:** Karten wurden durch wpautop zerlegt (leere „Details"-Boxen rechts). Neu gebaut als saubere Kacheln mit optionalem Foto (Beitragsbild), Typ-Badge und „Details ansehen" im Kartenfuß; wpautop-fest.
- **Mitglied-werden-CTA:** irreführender Hinweis „Ab 5 €/Monat" entfernt; Button verweist weiterhin auf die Info-Seite mit den konkreten Beiträgen.
- **Hauptnavigation:** zeigt jetzt fest auf die aktuellen Theme-Seiten (Kurse, Abteilungen, Sportstätten, Gaststätte, Kontakt) statt auf alte Newsartikel aus einem veralteten WP-Menü. Anpassbar per Filter tgs_nav_items.
- **Kontaktformular:** neuer Shortcode [tgs_kontakt] (Name, E-Mail, Betreff, Nachricht, DSGVO) mit Spam-Honeypot; sendet an die Vereins-/Admin-E-Mail (Filter tgs_kontakt_empfaenger).

## [0.12.2] — 2026-07-12

### Hinzugefügt — Aktionstage auf der Speisekarte
- Neue hervorgehobene Box „Aktionstage" ganz oben auf der Speisekarte (aus dem Flyer): Montag Schnitzeltag (je 18,00 €) und Donnerstag Rumpsteaktag (je 28,00 €), jeweils inkl. Pommes, Beilagensalat und 1 Getränk (außer Wein). Zentral in inc/speisekarte.php pflegbar.

## [0.12.1] — 2026-07-12

### Behoben
- Shortcode-Seiten (Mitglied werden, Gaststätte, Speisekarte): Falls der Shortcode versehentlich als Inline-Code eingefügt wurde, erbte der gesamte Block die monospace-Schrift. Ein umschließendes <code> wird jetzt neutralisiert, sodass wieder die richtigen Schriften (Inter / Libre Baskerville) greifen.

## [0.12.0] — 2026-07-12

### Hinzugefügt / Verbessert — Gaststätte & Speisekarte
- **Rücknavigation:** Die Speisekarten-Seite hat jetzt oben und unten einen „← Zurück zur Gaststätte"-Link (vorher kein Weg zurück).
- **Zahlungshinweise** auf der Gaststätten-Seite: „💶 Barzahlung" und „💳 Kartenzahlung" als Icon-Pills.
- **Imagefilm** (YouTube) datenschutzfreundlich eingebettet: Vorschau mit Play-Button, das Video lädt erst beim Klick über youtube-nocookie (keine Datenübertragung vorher). Video-ID in inc/gaststaette.php pflegbar.

## [0.11.0] — 2026-07-12

### Hinzugefügt — Speisekarte auf eigener Seite
- Neuer Shortcode `[tgs_speisekarte]`: die komplette Karte von „Zu den Eichen – Da Luca" (aus dem offiziellen PDF übernommen) schön gesetzt auf der eigenen Website – Speisen (Antipasti, Suppe, Salate, Pizza, Pasta, Pasta Special, Fisch, Rumpsteak, Schnitzel, Kinder, Dessert) mit Nummern, Beschreibungen und Preisen (inkl. Klein/Groß) sowie alle Getränke. Zweispaltiges Layout, mobil einspaltig.
- Der „Zur Speisekarte"-Button auf der Gaststätten-Seite verlinkt jetzt intern auf diese Seite (kein externer Link mehr). Menüdaten zentral in `inc/speisekarte.php`.

## [0.10.1] — 2026-07-12

### Behoben — Gaststätten-Seite
- **Anruf-Rückfrage:** Klick auf eine Telefonnummer startet nicht mehr sofort den Anruf, sondern fragt vorher nach (Bestätigungsdialog).
- **Leerer Block unter dem Hero** entfernt: Die Schnell-Fakten-Leiste brach in der zentrierten Breite auf zwei Zeilen um — jetzt feste 4 Spalten (mobil 2).
- **Kein externer Speisekarten-Link mehr.** Speisekarte optional als eigenes PDF (Mediathek-URL in inc/gaststaette.php); ohne Eintrag erscheint statt Button ein Hinweis.

## [0.10.0] — 2026-07-12

### Hinzugefügt — Seite „Vereinsgaststätte" (Zu den Eichen – Da Luca)
- Neuer Shortcode `[tgs_gaststaette]`: einladende Seite mit Hero (Google-Bewertung 4,6★ + Live-Status „jetzt geöffnet/geschlossen"), Schnell-Fakten (heute, Telefon zum Anrufen, Adresse zu Maps, Küche), Öffnungszeiten-Tabelle mit Hervorhebung des heutigen Tages und Ruhetag, appetitlich präsentiertem Speisenangebot + Button zur Speisekarte (statt Karten-Scan), Bewertungs-Karte mit Link zu Google, Besonderheiten und Kontakt/Reservierungs-CTA (Klick zum Anrufen).
- Alle Angaben (Öffnungszeiten, Kontakt, Links, Bewertung) zentral in `inc/gaststaette.php` pflegbar.

## [0.9.0] — 2026-07-12

### Hinzugefügt — Altersgrenzen für Kurse
- Neue Kursdetails-Felder **Mindestalter** und **Höchstalter** (Jahre, leer = keine Grenze) — besonders für Kinderkurse.
- Ist eine Grenze gesetzt, fragt das Anmeldeformular ein **Geburtsdatum** ab (bei Kinderkursen „des Kindes"), zeigt die Altersvorgabe an und **prüft bei der Anmeldung** das Alter; außerhalb der Spanne wird abgelehnt.
- Altersvorgabe erscheint auf der Kurs-Detailseite („Auf einen Blick") und Geburtsdatum + Alter im Backend-Teilnehmer-Roster.
- Formular-Feldpaarung (E-Mail/Telefon) auf robuste ID-basierte CSS umgestellt, damit zusätzliche Felder die Spalten nicht verschieben.

## [0.8.1] — 2026-07-12

### Behoben — Mitglied-werden-Seite
- Doppelten Seitentitel ausgeblendet (Template-Titel + Hero-Titel) und doppelte Section-Zentrierung/Padding behoben: `.tgs-mw` ist jetzt der einzige zentrierende Container, die umgebende Seiten-Section wird entrahmt.

# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

## [0.8.0] — 2026-07-12

### Hinzugefügt — Seite „Mitglied werden" mit Kostenübersicht
- Neuer Shortcode `[tgs_mitglied_werden]`: komplette Beitritts-Seite mit Intro, transparenter **Beitragsübersicht** (Grundbeiträge ab 2026 als Karten inkl. grober Monatswerte, Handball-Zusatzbeitrag, Familienregel + Rechenbeispiele), 3-Schritte-Erklärung, gebrandeter CTA zum offiziellen Online-Mitgliedsantrag (SPG-Vereinsportal, öffnet in neuem Tab) und FAQ-Kacheln.
- Zusätzlich `[tgs_beitraege]` für die reine Beitragstabelle. Beitragsdaten und Portal-URL zentral in `inc/mitglied.php` pflegbar (Portal-URL per Filter `tgs_mitglied_portal_url` überschreibbar).
- Bewusst **kein iframe** des externen Portals (DSGVO/Fremd-Skripte + Fremdoptik); stattdessen behält unsere Seite den Kontext und der offizielle Antrag ist ein klarer, gebrandeter Klick.

## [0.7.3] — 2026-07-12

### Behoben / Verbessert — Brotkrumen
- Die Brotkrumen-Leiste war ein großer Block mit 40px Padding oben/unten für eine 12px-Zeile — jetzt eine schmale Leiste (spart ~80px verschenkten Platz).
- Neuer dynamischer `[tgs_breadcrumb]`: die letzte Krume zeigt den **echten Seitentitel** (z.B. „Calisthenics- & Fitnessfläche") statt des generischen „Sportstätte"/„Abteilung". Umgesetzt auf den Sportstätten- und Abteilungs-Detailseiten.

## [0.7.2] — 2026-07-12

### Hinzugefügt — Trainings-App verlinken
- Optionaler **App-Block** pro Sportstätte: Felder für App-Name, Kurzbeschreibung, App-Store- und Google-Play-Link in der Metabox. Bei ausgefüllten Werten erscheint eine dunkle App-Karte mit Store-Buttons auf der Detailseite (z.B. KOMPAN Outdoor Fitness).

## [0.7.1] — 2026-07-12

### Behoben
- Sportstätten-Detailseite: leerer Kasten in der Fakten-Leiste entfernt (durch wpautop eingefügte leere Absätze werden ausgeblendet).

## [0.7.0] — 2026-07-12

### Hinzugefügt — Sportstätten für Außenanlagen ausgebaut
- **Backend-Metabox „Standort & Details"** für Sportstätten (bisher nur über rohe Benutzerdefinierte Felder pflegbar). Neue Felder: **Art der Sportstätte**, **Zugang / Öffnung**, **Kosten / Nutzung**; Ausstattung jetzt als mehrzeiliges Feld (eine Zeile = ein Listenpunkt).
- **Foto-Hero:** Ist ein Beitragsbild gesetzt, wird es als großes Titelbild mit Verlauf und Typ-Badge genutzt (statt der leeren grünen Box). Ohne Bild grüner Standard-Hero.
- **Fakten-Leiste** (Zugang, Kosten, Parkplätze, Barrierefreiheit) und **Ausstattungs-Liste** als sticky Sidebar; Fotos lassen sich als Galerie in den Textbereich einfügen.
- Der Abschnitt „Kurse & Trainings" erscheint nur noch, wenn dort tatsächlich Kurse stattfinden — passend für frei zugängliche Anlagen ohne feste Kurszeiten.
- Sportstätten-Übersichtskarten zeigen Typ-Badge und Öffnung.

## [0.6.3] — 2026-07-12

### Behoben — Zielgruppen-Filter auch von der Startseite
- Der Kurs-Teaser auf der Startseite (begrenzte Tabelle) zeigt die Filter-Chips „Kategorie" und „Für wen?" jetzt als **Links auf die volle Kursseite mit vorausgewähltem Filter**. So findet man z.B. „Frauen"-Kurse direkt von der Startseite — mit vollständigem Ergebnis, nicht nur innerhalb der ersten Teaser-Zeilen.
- Zielgruppen-Chips im Teaser werden über **alle** Kurse gebildet (vorher nur über die angezeigten), sodass keine Zielgruppe fehlt.
- Die Kursübersicht übernimmt einen Filter aus dem URL-Hash (`#zielgruppe=frauen`, `#kategorie=fitness`) und wendet ihn beim Laden an.

## [0.6.2] — 2026-07-12

### Geändert — Zielgruppen standardisiert & filterbar
- Kursfeld **Zielgruppe** ist nicht mehr Freitext, sondern eine **Mehrfachauswahl** aus festen Standards: Kinder, Jugendliche, Erwachsene, Senioren, Frauen, Männer. Ein Kurs kann mehrere Zielgruppen haben. Alter Freitext bleibt als Hinweis im Backend sichtbar, bis er einmal neu angekreuzt wird.
- Neue **Filterreihe „Für wen?"** in der Kursübersicht (`[tgs_kurstabelle]`), zeigt nur tatsächlich vorkommende Zielgruppen und lässt sich **mit dem Kategorie-Filter kombinieren** (UND-Verknüpfung).
- Kurs-Detailseite zeigt die Zielgruppen als lesbare Labels.

## [0.6.1] — 2026-07-12

### Hinzugefügt — Kinderkurse
- Neuer Kursdetails-Schalter „Kurs für Kinder". Bei aktivem Kinderkurs ändert sich das Anmeldeformular: **Name des Kindes** (= Teilnehmer) + **Ansprechpartner (Elternteil)** mit Name/E-Mail (Pflicht) und Telefon, plus **zweiter Ansprechpartner** (optional).
- Alle Benachrichtigungen gehen an die **Eltern-E-Mail**; Anrede/Texte nennen das Kind („die Anmeldung von … ist bestätigt"). Backend-Roster zeigt Kind + Elternkontakt(e). Doppel-Check jetzt auf E-Mail + Name (ein Elternteil kann mehrere Kinder anmelden).

## [0.6.0] — 2026-07-12

### Hinzugefügt — Ausfälle, Pausen & Mitteilungen pro Kurs
- Neue Box „Ausfälle & Mitteilungen" im Kurs-Editor → Seite zum Anlegen von: **Ausfall** (einzelner Termin, Datum + Grund), **Pause** (Zeitraum von–bis) oder **Mitteilung** (Freitext, optional „sichtbar bis").
- Optional **Benachrichtigung aller bestätigten Teilnehmer per E-Mail** (Häkchen).
- **Anzeige überall**: Banner auf der Kursseite (Ausfall/Pause gelb, Info grün) und **Badge in der Kursübersicht** („Fällt aus" / „Pause"). Vergangene Ausfälle/beendete Pausen werden automatisch ausgeblendet.
- Neues Modul inc/kurs-meldungen.php, CPT `tgs_meldung` (intern), Shortcode `[tgs_kurs_meldungen]`.

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
