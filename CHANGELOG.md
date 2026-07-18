## [0.33.0] — 2026-07-18

### Neu — Abteilungsseite: „Verein in Zahlen" + lebendige Karten
- Die Abteilungsübersicht (`/abteilungen/`) war ein reines Menü. Jetzt: oben ein **„Verein in Zahlen"-Streifen** (Abteilungen, Kurse, gegründet 1886, Altersspanne) und **lebendige Kacheln** mit einem echten, aktuellen Signal je Sparte:
  - **Handball:** nächstes (Heim-)Spiel live aus dem handball.net-Feed (WBH hervorgehoben).
  - **Radsport:** Anzahl Taunus-Touren mit GPX (automatisch).
  - **Fitness & Turnen:** Kurszahl + Altersbreite.
  - **Tischtennis:** Ligabetrieb-Hinweis (bis click-TT dran ist).
- Signal je Abteilung aus dem Titel abgeleitet (Handball/Tischtennis/Radsport/Fitness); manuell übersteuerbar per Meta `_tgs_abt_signal`, Zahlen per Filter `tgs_verein_zahlen`.
- Passendes Emoji je Sparte (🤸🤾🏓🚴 — die Tischtennis-Lupe ist weg), **Ansprechpartner von der Kachel entfernt** (stehen auf der Abteilungs-Detailseite). Ganze Karte verlinkt.
## [0.32.1] — 2026-07-18

### Behoben — „Heute": falsche Uhrzeit (doppelter Zeitzonen-Offset)
- Die „Stand HH:MM Uhr"-Anzeige lag 2 Std. daneben: `wp_date()` bekam `current_time('timestamp')` übergeben — das ist schon lokale Zeit, `wp_date()` addierte den Zeitzonen-Offset ein zweites Mal. Betraf auch das Datum (falscher Tag um Mitternacht).
- Fix: `wp_date()` ohne Timestamp (= jetzt, korrekt lokalisiert). Die minutengenaue „Stand"-Zeile ist entfallen — sie brachte keinen Mehrwert (Seite wird gecacht) und war irreführend. Das Datum („Samstag, 18. Juli") bleibt.
## [0.32.0] — 2026-07-18

### Ergänzt — „Heute" auf der Startseite, Quellen-Hinweis, Link-Verwaltung
- **`[tgs_heute]` als Sektion auf der Startseite** (direkt unter dem Hero) — der Wiederkehr-Anker ganz oben.
- **Quellen-Hinweis** unter den Spielen: „Handball-Spielplan: handball.net (Hessischer Handball-Verband)" mit Link auf die offizielle Vereinsseite. Erscheint nur, wenn Handball-Inhalte gezeigt werden.
- **Einstellungsseite „Heute & Handball"** (unter Einstellungen): Hier pflegt der Verein die Links zu den HSG-Mannschaftsseiten (Herren 1/2/3, Damen) selbst — kein Code-Edit nötig. Leeres Feld = kein Link. Standardwerte (Herren 1, Damen) sind vorbelegt; Option `tgs_handball_links` überlagert die Defaults, Filter `tgs_handball_team_links` bleibt für Entwickler.
## [0.31.1] — 2026-07-18

### Ergänzt — „Heute": Handball-Spiele mit Team-Link, Hallen-Link, Spielart
- **Team verlinkt:** Der EppLa-Teamname im Spiel führt auf die HSG-Mannschaftsseite (z. B. Herren 1 → `hsg-eppla.de/list/herren-1`). Gender/Alter aus dem Turniernamen, Nummer aus dem Teamnamen. Standardmäßig verlinkt sind die bestätigt funktionierenden Seiten (Herren 1, Damen); weitere per Filter `tgs_handball_team_links` ergänzbar — keine kaputten Links.
- **Wilhelm-Busch-Halle verlinkt:** Bei Heimspielen in der WBH führt der Ort auf unsere eigene Sportstätten-Seite.
- **Spielart wird angezeigt:** aus dem Turniernamen abgeleitet — **Testspiel** (Freundschaftsspiel/Vereins-Event/Turnier), **Quali** (Qualifikation) oder **Punktspiel** (Liga). Erscheint in der Spielzeile und im „nächstes Spiel"-Teaser.
- Verifiziert am echten Feed: EppLa-1-Heimspiel in der WBH als „Testspiel" korrekt erkannt und verlinkt.
## [0.31.0] — 2026-07-18

### Neu — „Heute in der TGS" (Kurse + Handball) · Dorf-Dashboard
- **Shortcode `[tgs_heute]`** + automatisch angelegte Seite **`/heute`**: zeigt, was heute läuft — als Zeitleiste nach Uhrzeit sortiert. Der Wiederkehr-Motor der Seite; läuft komplett von allein (Wochentag/Uhrzeit vom Server, Rest aus den Kursdaten).
- **Kurse heute:** Tag/Zeit/Ort aus `tgs_kurs_termin()` (saisonabhängig), Status als Badge — **Jetzt läuft** (grün), Freie Plätze, Warteliste, Offen, ☀️/❄️ Saison und **Fällt aus** (mit Grund, durchgestrichen) direkt aus dem Meldungs-System.
- **Handball-Spiele der HSG EppLa:** serverseitig aus der handball.net-JSON-API geholt (Verein 16173), 30 Min. gecacht, bei Fehler letzter guter Stand. **Heimspiele in der Wilhelm-Busch-Halle werden als solche erkannt und hervorgehoben.** Ergebnis erscheint bei gespielten Partien. Verifiziert am echten Feed (2 Spiele geparst, Zeitzone/WBH/Heim korrekt).
- **DSGVO:** Der Feed wird **serverseitig** geholt — der Browser der Besucher spricht nie mit handball.net. Kein iframe, kein Widget.
- **Leer-Zustand** freundlich: „Heute kein Training — das Nächste: …" plus, wenn heute kein Spiel ist, ein Teaser aufs **nächste Handballspiel**.
- Vorbereitet für **Tischtennis** als dritte Quelle (TGS spielt 1./3. Kreisklasse Main-Taunus über click-TT/HTTV) — Feinschliff als Folgeschritt.
## [0.30.2] — 2026-07-17

### Neu — Telefonnummer der Kursleitung crawler-geschützt
- Neuer Helfer `tgs_tel_link()` – **analog zu `tgs_mail_link()`** (dasselbe `antispambot()`): die Nummer steht nicht mehr im Klartext im Quelltext, sondern als zufällige HTML-Entities. Anzeige, Copy-&-Paste und Screenreader funktionieren normal; zusätzlich als `tel:`-Link (Antippen zum Anrufen).
- In der Kursleitung-Karte ersetzt `tgs_tel_link( $tel )` die bisherige Klartext-Ausgabe. Damit sind jetzt **alle drei** Kontaktangaben geschützt: Name (base64 + JS), E-Mail und Telefon (beide antispambot).
- Schutzniveau wie bei der E-Mail: stoppt naive Harvester, kein absoluter Schutz gegen rendernde Scraper. Die Vereins-Hauptnummer (Kontaktseite) bleibt bewusst offen – die ist wie bei einem Betrieb öffentlich; geschützt werden die persönlichen Nummern der Kursleitungen.
## [0.30.0] — 2026-07-17

### Geändert — Kursleitungen als eigener Bereich (statt pro Kurs pflegen)
- **Neuer CPT „Kursleitungen"** (`tgs_trainer`): Name (Titel), Foto (Beitragsbild), kurze Vorstellung, E-Mail, Telefon — **einmal** gepflegt. Wer mehrere Kurse leitet, wird nur einmal angelegt.
- **Im Kurs nur noch eine Auswahl** („Kursleitung"-Box, Mehrfachauswahl) statt der bisherigen Inline-Felder. Der Kurs-Editor ist dadurch aufgeräumter.
- **Datenschutz konsequent:** Der CPT ist **nicht öffentlich** — keine eigene Profilseite. Sonst wäre eine Seite mit Name + Foto + allen Kursen genau das leicht abgreifbare Profil, das der Trainer-Schutz vermeiden soll. Die Kursleitung erscheint nur eingebettet auf den Kursseiten (Name crawler-geschützt, Foto neutrales „alt").
- **Kein Datenverlust:** Kurse ohne verknüpfte Kursleitung fallen automatisch auf die alten Inline-Daten (`_tgs_ansprechpartner*`) zurück. Sobald eine Kursleitung verknüpft ist, gilt diese.
- **Mehrere Kursleitungen pro Kurs** möglich (Karten werden gestapelt). Layout-Feinschliff: Foto oben-bündig bei langer Vorstellung, mittig bei kurzer — kein Leerraum mehr zwischen Text und Kontakt.
## [0.29.0] — 2026-07-17

### Neu — Moderne Kursleitung-Vorstellung (optional, kein Steckbrief)
- Neues Feld **„Kurze Vorstellung (optional)"** im Kurs-Editor (Textarea). Ein, zwei Sätze in warmem Ton – bewusst **kein** Steckbrief-Faktengitter.
- Die bisherige „Kursleitung"-Karte ist zu einer editorialen Vorstellung geworden: Foto (rund, größer), Name in Serifenschrift, Vorstellungstext, Kontakt – dezent und modern.
- **Passt sich sauber an, was vorhanden ist:**
  - Foto + Vorstellung → volle, schicke Karte.
  - **Kein Foto → kein Platzhalter/Avatar-Lücke:** der Text steht mit dezentem grünem Akzent für sich.
  - Nur Foto + Name → funktioniert ohne leere Textzeile.
  - **Nichts gesetzt (Kursleitung möchte nicht) → der Bereich erscheint gar nicht** auf der Kursseite; man sieht ihr das nicht an.
- Der Name bleibt crawler-geschützt (base64 + JS wie bisher); das Foto behält das neutrale `alt`. Hinweis: Steht der Name im frei geschriebenen Vorstellungstext, ist er dort naturgemäß im Klartext – das ist die redaktionelle Entscheidung der Kursleitung.
## [0.28.1] — 2026-07-17

### Ergänzt — Nahkauf Box: optionaler Google-Maps-Link
- Neues optionales Feld **„Nahkauf Box: Google-Maps-Link"** je Sportstätte. Ist er gesetzt, zeigt die Karte einen **„Standort & Öffnungszeiten ↗"**-Button (zur Google-Maps-Seite der konkreten Box) plus einen dezenten „Was ist das?"-Link zum Konzept.
- Bewusst nur **Link, kein eingebettetes Google-Maps-iframe**: ein Embed würde beim Seitenaufruf Google-Skripte laden und IP/Cookies übertragen — der Klick-Link tut das erst, wenn der Nutzer ihn aktiv anklickt (konsistent mit der DSGVO-Linie: selbstgehostete Fonts, Video-Fassade etc.).
## [0.28.0] — 2026-07-17

### Neu — „Nahkauf Box in der Nähe" auf Sportstätten-Seiten
- Neues Feld **„Nahkauf Box in der Nähe"** je Sportstätte (Text = Entfernung/Hinweis, z. B. „direkt am Platz"; leer = ausgeblendet). Gilt für Calisthenics-Fläche, Sportplatz und Wilhelm-Busch-Halle — alle in direkter Nachbarschaft.
- Rendert eine dezente Karte (amber-Akzent, grenzt sie vom Vereinsgrün ab): 24/7-SB-Markt mit Lebensmitteln, Getränken, Snacks, bargeldlos per Karte — echter Standortvorteil an einer Sportanlage.
- **Ehrlich & DSGVO-konform:** klar als „Gute Nachbarschaft / nicht vom Verein betrieben" gekennzeichnet; nur Text + Klick-Link zu nahkauf.de, **kein eingebettetes Widget, kein Fremd-Logo, kein Tracking**.
## [0.27.3] — 2026-07-17

### Behoben — Sportstätten-Hero-Inhalt lief zu breit
- Der Hero der Sportstätten-Seiten hatte fixe 2rem-Ränder statt der Spalten-Ausrichtung (`--tgs-wrap` 1180px) wie Kurs- und News-Heros. Dadurch zog sich der Hero-**Inhalt** (Titel/Badge) auf breiten Monitoren immer weiter mit der Fensterbreite und wirkte „seitenfüllend" gegenüber dem 1180px-Body darunter. Jetzt richtet sich der Hero-Inhalt auf dieselbe Spalte aus; der Hintergrund/das Foto bleibt full-bleed (wie bei Kursen/News). Body war bereits korrekt bei 1180px.
## [0.27.2] — 2026-07-17

### Behoben — Gaststätte/Speisekarte/Mitglied-werden waren zu schmal
- Diese drei Seiten sind normale WordPress-Seiten und laufen über `page.html`, das den Inhalt in ein `constrained`-Layout rahmt (theme.json `contentSize: 720px`). Dadurch waren sie mit **720px** deutlich schmaler als die CPT-Seiten (Kurse, Sportstätten …) mit **1180px** — beim Seitenwechsel sprang das ins Auge. Das bestehende Breakout-CSS nullte nur das Padding, nicht die `max-width` des `.wp-block-post-content`. Jetzt zusätzlich `max-width: none` → volle Breite wie überall, Zentrierung über das eigene `padding-inline` (`--tgs-wrap`).
## [0.27.1] — 2026-07-17

### Geändert — Layout-Feinschliff
- **Teilen-Button aus dem Kurs-Header entfernt.** Er machte die rechte CTA-Spalte höher als die Titel-/Info-Spalte links → sichtbare Unwucht mit viel Leerraum bis zum Trennstrich. Der Button sitzt jetzt dezent am **Ende der Inhaltsspalte** („Kurs weiterempfehlen") — der natürliche Ort zum Teilen.
- **Unterer „Mitglied werden"-Block durch die Sponsoren-Leiste ersetzt** (auf allen Inhalts- und Archivseiten). Grund: „Mitglied werden" ist dauerhaft im Menü — der große grüne Block am Seitenende war doppelt. Die Sponsoren-Leiste (`parts/sponsor-bar.html`) existierte bereits, wurde aber nirgends eingebunden; sie dient jetzt als dezenter Begrenzer über dem Footer und gibt den Sponsoren durchgehend Sichtbarkeit. Mitgliedschaft bleibt über das Menü und die Startseite präsent (Homepage unverändert).
## [0.27.0] — 2026-07-17

### Neu — Inhalte teilen (Open Graph + datenschutzfreundlicher Button) · Issue #20
- **Open-Graph-/Twitter-Meta im `<head>`** für Kurse, Beiträge, Touren, Sportstätten, Guides, Seiten und die Startseite. Ein geteilter Link „entfaltet" sich damit auf WhatsApp, Facebook, iMessage & Co. zu einer Karte mit **Foto + Titel + Text** statt als nackte URL.
  - Beschreibung automatisch je Typ: Kurs = Kurzbeschreibung; **Tour = Eckdaten** („37,7 km · ▲ 711 m · MTB"); sonst Auszug/Textanfang. Bild = Beitragsbild, sonst Vereinslogo als Fallback.
  - **Guard gegen Doppel-Tags:** Läuft später ein SEO-Plugin (Yoast/RankMath/AIOSEO/SEO Framework), hält sich unser OG-Code automatisch zurück.
- **Teilen-Button ohne Fremd-Widgets** auf Kurs-, Tour- und Beitragsseiten:
  - Native **Web-Share-API** — auf dem Handy ein Tipp, dann öffnet der System-Teilen-Dialog (WhatsApp, alles).
  - Am Desktop ein Aufklapp-Menü mit **Link kopieren · WhatsApp · E-Mail** — alles echte Links/Bordmittel, funktionieren auch ohne JavaScript.
  - **Bewusst kein Facebook-/Twitter-Widget**: die tracken schon beim Laden der Seite. Hier wird **nichts** übertragen, bis der Nutzer aktiv teilt. Kein Skript, kein Pixel, DSGVO-sauber.
- Shortcode `[tgs_teilen]` (optional `label="…"`).
## [0.26.0] — 2026-07-17

### Neu — Rückfragen abschaltbar + „Einfach vorbeikommen"-Karte für offene Kurse
- **Neuer Schalter „Rückfragen" in den Kursdetails** (Ja/Nein, Standard Ja), unabhängig von der Anmeldung. Damit sind alle Kombinationen möglich: offener Kurs ohne jede Interaktion, offener Kurs mit Nachfrage-Möglichkeit, oder Pflichtanmeldung ohne Frage-Funktion. Ist er auf „Nein", verschwinden Frage-Button, Hinweis und Formular überall auf der Kursseite.
- **Offene Kurse zeigen statt eines winzigen Hinweises eine „Einfach vorbeikommen"-Karte**, die die frei werdende Fläche mit echtem Nutzen füllt statt mit Leerraum:
  - **„Termin in meinen Kalender"** — der bestehende .ics-Abo-Button (v0.23.0), damit man den wiederkehrenden Termin nicht vergisst (nur, wenn der Kurs eine regelmäßige Serie hat).
  - **Wann/Wo** als klare Kacheln, **Anfahrt** mit Link zur Sportstätte + Maps-Route.
  - Optional eine **aufklappbare Kurzfrage** an die Kursleitung (nur wenn Rückfragen erlaubt) — nutzt das bestehende `tgs_frage`-System, kein zweites Formular.
- Verifiziert: Toggle blendet die Frage-Funktion in beiden Ansichten (volles Formular + Offen-Karte) sauber aus; Offen-Karte rendert 520 px (statt 647 px Formular) und füllt die Content-Spalte sinnvoll.
## [0.25.0] — 2026-07-17

### Neu — Guides & wechselnder Tourentipp
- **Neuer CPT `tgs_guide`** (Name, Foto, kurze Vorstellung) mit eigener Seite `/guides/<name>/`, die alle Lieblingstouren des Guides zeigt.
- **Tour-Felder „Empfohlen von" (Guide-Picker) und „Warum diese Runde?"** — ein Satz in der Ich-Form. Das ist der Mehrwert gegenüber jeder Tourenplattform: nicht eine Linie auf der Karte, sondern die Empfehlung eines Nachbarn. Erscheint als Zitat-Karte oben auf der Tourseite, bewusst **vor** den Zahlen.
- **`[tgs_tour_tipp]` — „Lieblingstouren von …", wechselt automatisch jede Woche.** Attribute: `guide` (feste ID statt Rotation), `touren` (Anzahl).
  - **Rein rechnerische Rotation, kein Cronjob, kein gespeicherter Zustand.** Alles, was wöchentliche Handarbeit braucht, schläft in einem Verein nach drei Wochen ein.
  - Gezählt wird eine **fortlaufende Wochennummer seit 1970**, nicht Jahr+Kalenderwoche: Ein Jahr hat mal 52, mal 53 Wochen — mit Jahr+KW blieb der Tipp am Jahreswechsel bei 2 Guides zwei Wochen lang derselbe (im Test nachgewiesen und behoben). Wechsel erfolgt montags.
  - **Die Lieblingstour eines Guides rotiert über die „Runde"** (wie oft war er schon dran), nicht über die Woche. Sonst liefen beide Rotationen im Gleichschritt und jeder Guide zeigte für immer dieselbe Tour — im Test nachgewiesen und behoben. Verifiziert über 156 Wochen: jede Guide-Anzahl wechselt in **jeder** Woche, gleichverteilt, und jeder Guide durchläuft **alle** seine Touren.
  - **Ehrlich beschriftet:** Das Label „Tourentipp der Woche" und „Wechselt jede Woche" erscheinen nur, wenn es tatsächlich etwas zu rotieren gibt (mehr als ein Guide bzw. mehr als eine Tour). Sonst stünde dort ein Versprechen, das nicht eingelöst wird.
- **Guide-Namen laufen durch denselben Crawler-Schutz wie die Kursleitungen** (`tgs_trainer_name_html()`, Fallback „unserem Guide"). Ein Guide mit Foto, Name UND seinen Hausrunden wäre sonst ein fertiges Bewegungsprofil.
## [0.24.0] — 2026-07-17

### Neu — Touren (GPX-basiert) · Issue #15
- **Neuer CPT `tgs_tour`** mit Archiv `/touren/`, Detailseite und Liste `[tgs_touren]` (Attribute: `art`, `level`, `anzahl`, `filter`) — z. B. für die Radsport-Seite.
- **Die GPX gehört dem Verein.** Sie liegt in der Mediathek und ist die Quelle; ein Komoot-Link ist optional und nur ein zusätzlicher Kanal. WordPress erlaubt `.gpx` normalerweise nicht — Upload-Freigabe via `upload_mimes` + `wp_check_filetype_and_ext`.
- **GPX hochladen genügt — der Rest rechnet sich selbst** (einmal beim Speichern, nicht bei jedem Aufruf): Distanz (Haversine), Höhenmeter, Min/Max-Höhe, Rundkurs-Erkennung, Streckenzug und Höhenprofil.
  - **Höhenmeter werden geglättet** (gleitender Mittelwert + 2-m-Schwelle). Ohne das summiert sich GPS-Rauschen auf: Verifiziert an einer Testtour mit exakt 200 echten Höhenmetern → **roh 3.002 m, geglättet 194 m**. Die rohe Zahl wäre unbrauchbar.
  - **Streckenzug wird ausgedünnt** (Douglas-Peucker): 3.001 → 77 Punkte (−97 %) bei 0,04 % Abweichung in der Distanz. Die Original-GPX bleibt für den Download unangetastet.
- **Höhenprofil als SVG**, selbst gerendert in Vereinsfarben — kein Drittanbieter, skaliert scharf.
- **Karte hinter einer Klick-Fassade** (wie beim Imagefilm): Vorher steht dort der selbst gerenderte Streckenverlauf als SVG — null externe Requests, funktioniert ohne JavaScript. Erst auf Klick werden **Leaflet (selbst gehostet im Theme, v1.9.4, BSD-2)** und die Kartenkacheln von OpenStreetMap geladen; der Hinweis nennt den IP-Transfer ausdrücklich.
- **Tour ↔ Kurs verknüpfbar** — der Mehrwert, den Komoot strukturell nicht hat: aus einer Datei wird eine Einladung („Diese Runde fahren wir gemeinsam" → zur Gruppe, mit Anmeldung). Dazu Startpunkt als Sportstätte (Parkplätze, Treffpunkt) und Einkehr-Hinweis.
- **GPX-Download** — läuft auf Garmin, Wahoo, Komoot & Co.
- **Privatsphäre:** Feld „Anfang/Ende kürzen" (Meter). Aufgezeichnete Touren starten oft an der Haustür der aufzeichnenden Person; die Adresse stünde sonst maschinenlesbar in der GPX.

### Neu — Bewertungen & Kommentare für Touren
- Nutzt denselben CPT `tgs_bewertung` wie die Kurse (nur `_tgs_bew_tour_id` statt `_tgs_bew_kurs_id`), damit es **ein** Bewertungs- und Moderationskonzept gibt. Moderation direkt an der Tour; der Freigabe-Handler kennt jetzt beide Ziele.
- **Ohne Anmelde-Anker braucht es Spamschutz.** Kursbewertungen sind über den Anmelde-Token abgesichert — eine Tour kann jeder fahren. Statt eines Captchas (reCAPTCHA wäre Google) greifen ineinander: Honeypot, Mindest-Ausfüllzeit, Rate-Limit (gespeichert wird ein **Hash**, nie die IP), Nonce — und **Moderation**: nichts erscheint ohne Freigabe. Ehrlich: Letzteres bedeutet Arbeit; ohne geht es nicht.

### Geändert
- Filter-Chips funktionieren jetzt auch außerhalb der Kurstabelle (`.tgs-filter-item`) — dasselbe Bauteil, zwei Anwendungen.
## [0.23.0] — 2026-07-17

### Neu — Abonnierbarer Kurskalender (.ics) · Issue #19
- **Live erzeugte iCal-Feeds** statt statischer Download-Datei: Wer abonniert, bekommt jede Änderung (Zeit, Ort, Saisonwechsel, Ausfall) automatisch, sobald sein Kalender nachfragt. Es wird nichts gespeichert – der Feed entsteht bei jedem Abruf aus den Kursdaten.
  - `/kalender/kurse.ics` — alle Kurse
  - `/kalender/kurs-<ID>.ics` — ein einzelner Kurs
  - `/kalender/sportstaette-<ID>.ics` — Belegung einer Sportstätte (nur die Serien, die dort stattfinden)
- **Echte Serientermine** (`RRULE`) statt hunderter Einzeleinträge — der Feed bleibt winzig und läuft unbegrenzt weiter.
- **Saisonkurse** ergeben zwei Serien (Sommer/Winter) über `BYMONTH`; eine Winterpause lässt die Winter-Serie weg. Nutzt die bestehende `tgs_kurs_termin()`-Logik.
- **Ausfälle & Pausen** aus dem Meldungs-System wirken direkt im Kalender: der Termin wird per `EXDATE` aus der Serie genommen **und** als eigener Eintrag „Fällt aus: …" bzw. „Pause: …" mit Grund sichtbar gemacht — sonst würde er kommentarlos verschwinden.
- **`VTIMEZONE` Europe/Berlin**: „19:30" bleibt 19:30, auch über die Zeitumstellung hinweg.
- **`SEQUENCE`-Zähler** pro Kurs (`_tgs_ical_seq`), erhöht bei jeder Kursänderung und bei jedem angelegten/gelöschten Ausfall — damit Kalender eine Aktualisierung als solche erkennen.
- **ETag + 304**: unveränderte Feeds werden nicht neu übertragen; stündlich fragende Abonnenten kosten fast nichts.
- **Abo-Box** in der Kurs-Sidebar („In deinen Kalender") plus Shortcode `[tgs_kalender_abo]` (Attribute: `kurs`, `sportstaette`, `label`, `hint`). `webcal://`-Link abonniert per Tipp direkt; „Link kopieren" als Fallback für Desktop/Google.
- **Datenschutz**: im Feed stehen bewusst **keine** Namen von Kursleitungen und keine E-Mail-Adressen — ein Feed ist öffentlich und maschinenlesbar. Kein Drittanbieter, alles selbst gehostet.
- **Ehrliche Grenze**: Wie schnell „sofort" ist, entscheidet der Kalender-Client. Apple fragt zuverlässig nach (`REFRESH-INTERVAL`/`X-PUBLISHED-TTL` = 1 h werden mitgesendet), **Google Kalender aktualisiert externe ICS-Feeds notorisch träge (teils 12–24 h)** und ignoriert die Hinweise. Für die kurzfristige Absage bleibt daher die bestehende E-Mail-Benachrichtigung an bestätigte Teilnehmer der schnelle Kanal; der Feed ist die verlässliche Basis.

## [0.22.0] — 2026-07-13

### Neu — Geschützte Kursleitung (Trainer-Block, crawler-sicher)
- Neues Feld „Kursleitung-Foto" im Kurs-Editor (WordPress-Mediathek-Picker). Der „Ansprechpartner"-Block auf der Kursseite ist jetzt eine „Kursleitung"-Karte mit rundem Foto + Name + Kontakt.
- Schutz gegen automatisches Abgreifen/Verknüpfen von Name+Foto zu einem Profil:
  - **Name** steht nicht im HTML-Quelltext: base64-kodiert im data-Attribut, per JavaScript eingesetzt (Helper `tgs_trainer_name_html()` + `initGuardNames()`); ohne JS erscheint „Kursleitung".
  - **Foto** mit neutralem `alt="Kursleitung"` (kein Name), `loading=lazy` → keine Name↔Bild-Kopplung im Markup.
  - **E-Mail** als „E-Mail schreiben"-Link (Adresse via antispambot verschleiert, kein Klartext).
- Ehrliche Grenze: Perfekter Schutz für öffentlich Sichtbares ist nicht möglich (JS-/OCR-Crawler); dies wehrt das einfache, automatische Harvesting zuverlässig ab.
## [0.21.0] — 2026-07-13

### Neu — Frage zum Kurs (ein Formular, zwei Aktionen) · Issue #16 (MVP)
- Das Kurs-Anmeldeformular hat jetzt zwei Aktionen: **„Anmeldung absenden"** oder **„Frage stellen"** – kein zweites Kontaktformular. „Frage stellen" (mit `formnovalidate`) braucht nur Name, E-Mail und die Frage; die Anmelde-Pflichtfelder werden übersprungen.
- Fragen werden als CPT `tgs_frage` im Kurs-Kontext gespeichert (Basis für spätere FAQ) und der Kursleitung per E-Mail gemeldet – mit **`Reply-To` = fragende Person**, sodass ein direktes „Antworten" genügt. Die Kursleiter-Adresse steht nirgends öffentlich.
- Backend: Box „Fragen zu diesem Kurs" im Kurs-Editor (Datum, Absender, Frage).
- Formular-Titel „… – oder kurz nachfragen", Feld „Nachricht / Frage", Hinweis-Kasten. Antwort-Loop + öffentliche FAQ folgen als spätere Ausbaustufe.
## [0.20.5] — 2026-07-13

### Geändert — Spam-Schutz für E-Mail-Adressen
- Öffentlich angezeigte E-Mail-Adressen (Ansprechpartner auf Startseite, Kurs-Detail, Abteilungs-Detail, Impressum, Datenschutz) sind jetzt gegen Crawler verschleiert. Zentraler Helper `tgs_mail_link()` mit WordPress' `antispambot()` (zufällige HTML-Entities) – ohne JavaScript, Copy-&-Paste und Screenreader funktionieren weiter. Backend-Listen (nur im wp-admin) bleiben unverändert.
## [0.20.4] — 2026-07-13

### Behoben — Mobile Header
- Der „Mitglied werden"-Button im Header wurde auf dem Handy nicht ausgeblendet (WordPress' `.wp-block-buttons`-Regel überschrieb das `display:none`). Dadurch wurde der Vereinsname gequetscht und brach um. Fix mit höherer Spezifität + `!important` — jetzt sauber: Logo + Titel links, „☰ Menü" rechts; „Mitglied werden" ausschließlich im Dropdown (hervorgehoben).
## [0.20.3] — 2026-07-13

### Geändert — Mobile Navigation
- Hamburger nicht mehr auf eigener Zeile rechts, sondern im üblichen Muster: **Logo links, „☰ Menü" rechts in einer Zeile**. „Mitglied werden" ist auf dem Handy in das Dropdown gewandert (als hervorgehobener Button unten), statt zusätzlich oben rechts zu stehen. Dropdown öffnet full-width unter dem Header (CSS-only, kein JS).
## [0.20.2] — 2026-07-13

### Behoben — Layout (Kursseite, Kontakt, Mobil)
- „Auf einen Blick": die Winter-Zeile stand mitten zwischen Ort und Zielgruppe → jetzt sauber am Ende der Box; leeres wpautop-`<p>` in der Sidebar ausgeblendet.
- Ort-Verlinkung konsistent: Ort wird überall (Meta-Zeile, „Auf einen Blick", Saison-Callout, Winter-Hinweis) mit der Sportstätte verlinkt, sofern verknüpft (zentraler Helper `tgs_ort_html`).
- „Weitere Kurse/Trainings": lange Namen brechen jetzt sauber um, Zeit bleibt rechtsbündig (align-items:baseline, Zeit nowrap).
- Kontaktseite rutschte gegenüber anderen Seiten leicht nach rechts (Scrollbar) → `scrollbar-gutter: stable` reserviert den Platz immer (Issue #17).
- Mobile Hauptnavigation: statt umbrechender Links jetzt ein CSS-only **Hamburger-Menü** („☰ Menü" → Dropdown), kein JavaScript, kein Template-Reset nötig.
## [0.20.1] — 2026-07-13

### Neu — Ort per Sportstätte wählen (statt tippen)
- Im Kurs-Editor ist „Ort / Halle" jetzt ein Picker: **Sportstätte aus einer Auswahl** ODER weiterhin **Freitext** (Feld darunter). Bei Auswahl wird der Ort-Text automatisch aus der Sportstätte gesetzt und die verknüpfte ID gespeichert. Gilt für Sommer- und Winter-Ort.
- Auf der Kursseite wird der Ort in „Auf einen Blick" bei verknüpfter Sportstätte automatisch auf deren Seite verlinkt.
- (Das ID-Feld `_tgs_ort_id` war längst registriert, hatte nur nie eine Oberfläche.)
## [0.20.0] — 2026-07-13

### Neu — Saisonale Kurse (Sommer/Winter)
- Kurse können pro Kurs saisonabhängig sein: im Kurs-Editor der Schalter „Saisonabhängig?" blendet Winter-Felder ein (Wochentag, Uhrzeit Start/Ende, Ort, „Winter pausiert", Wintermonate von–bis, Standard Okt–März).
- Anzeige schaltet automatisch nach Datum um: Kurstabelle, „Auf einen Blick" und die Meta-Zeile zeigen die aktuell gültige Saison; Badge „☀️/❄️ aktuell: …", ein Callout „Wann & wo wir trainieren" mit beiden Saisons (die aktive hervorgehoben) und eine „andere Saison"-Zeile in der Info-Box. Winterpause wird als Hinweis dargestellt. Kleiner ❄️/☀️-Marker in der Kurstabelle.
- Nebenbei: numerische Select-Werte in der Kurs-Metabox werden jetzt korrekt gespeichert (die Ja/Nein-Felder nutzen String-Keys).
## [0.19.3] — 2026-07-13

### Behoben — „Weitere Sportstätten"-Karten (wpautop, echte Ursache)
- Ursache war nicht der Whitespace, sondern dass die Karte ein `<a>` mit Block-`<div>`-Kindern war → wpautop zerlegte das (leere `<p>`, aufgespaltene Links). Die inneren Elemente sind jetzt inline-`<span>` mit `display:block` im CSS (Layout unverändert) — das `<a>` enthält nur noch Inline-Elemente, wpautop lässt es in Ruhe.

## [0.19.2] — 2026-07-13

### Behoben — Anmeldeformular (Rest-`<br>`)
- DSGVO-Zustimmung: Checkbox und Text standen auf getrennten Zeilen → wpautop machte daraus ein `<br>` (Checkbox über dem Text). Auf eine Zeile gezogen (Checkbox inline vor dem Text). `/datenschutz`-Link auf `home_url()` umgestellt.

## [0.19.1] — 2026-07-13

### Behoben — wpautop (Layout)
- Zentrale Helper-Funktion `tgs_strip_ws()` eingeführt (entfernt Zwischenraum zwischen Tags in Shortcode-Ausgaben).
- **Kurs-Anmeldeformular** (`inc/kurs-anmeldung.php`): Ausgabe wird jetzt gestrippt – vorher fügte wpautop ein `<br>` zwischen Label und Eingabefeld ein (Feld rutschte unter das Label). Betraf jede Kursseite.
- **„Weitere Sportstätten"-Karten** (`inc/shortcodes.php`): Karten-Schleife zwischenraumfrei umgebaut (kein wpautop-`<br>` mehr); `get_permalink()` zusätzlich mit `esc_url()` gehärtet.

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
