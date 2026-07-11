<?php
/**
 * TGS Langenhain — Content Import Script
 * 
 * Einmalig im Browser aufrufen:
 * http://staging.tgs-langenhain.de/wp-content/themes/tgs-langenhain-theme/import.php
 * 
 * Legt alle Kurse, Abteilungen und Sportstätten an.
 * Kann mehrfach aufgerufen werden — prüft auf Duplikate.
 * 
 * NACH DEM IMPORT DIESE DATEI LÖSCHEN!
 */

// WordPress laden
require_once dirname( __FILE__ ) . '/../../../wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Bitte zuerst als Admin einloggen.' );
}

echo '<html><head><meta charset="utf-8"><title>TGS Import</title>';
echo '<style>body{font-family:Inter,sans-serif;max-width:700px;margin:2rem auto;font-size:14px;color:#1A2A1E;} 
h1{color:#3D5A40;} h2{color:#3D5A40;margin-top:2rem;border-bottom:1px solid #E4DDD0;padding-bottom:.3rem;} 
.ok{color:#3D5A40;} .skip{color:#999;} .err{color:#C33;} code{background:#F0EDE4;padding:2px 6px;border-radius:3px;font-size:12px;}</style>';
echo '</head><body>';
echo '<h1>🏅 TGS Langenhain — Content Import</h1>';

$created = 0;
$skipped = 0;

// === HELPER ===
function tgs_import_post( $type, $title, $meta = array(), $content = '', $extra = array() ) {
    global $created, $skipped;
    
    // Duplikat-Check
    $existing = get_posts( array(
        'post_type'   => $type,
        'title'       => $title,
        'post_status' => 'publish',
        'numberposts' => 1,
    ) );
    
    if ( ! empty( $existing ) ) {
        echo '<p class="skip">⏭ <code>' . esc_html( $title ) . '</code> existiert bereits</p>';
        $skipped++;
        return $existing[0]->ID;
    }
    
    $post_data = array(
        'post_type'    => $type,
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
    );
    
    if ( isset( $extra['excerpt'] ) ) {
        $post_data['post_excerpt'] = $extra['excerpt'];
    }
    if ( isset( $extra['menu_order'] ) ) {
        $post_data['menu_order'] = $extra['menu_order'];
    }
    
    $post_id = wp_insert_post( $post_data );
    
    if ( is_wp_error( $post_id ) ) {
        echo '<p class="err">✗ Fehler bei <code>' . esc_html( $title ) . '</code>: ' . $post_id->get_error_message() . '</p>';
        return false;
    }
    
    foreach ( $meta as $key => $value ) {
        update_post_meta( $post_id, $key, $value );
    }
    
    // Taxonomy
    if ( isset( $extra['kategorie'] ) && $type === 'tgs_kurs' ) {
        wp_set_object_terms( $post_id, $extra['kategorie'], 'tgs_kurs_kategorie' );
    }
    
    echo '<p class="ok">✓ <code>' . esc_html( $title ) . '</code> angelegt (ID: ' . $post_id . ')</p>';
    $created++;
    return $post_id;
}

// ============================================================
// ABTEILUNGEN
// ============================================================
echo '<h2>Abteilungen</h2>';

tgs_import_post( 'tgs_abteilung', 'Fitness & Turnen', array(
    '_tgs_abt_icon'    => '💪',
    '_tgs_abt_leitung' => 'Abteilungsleitung Fitness',
    '_tgs_abt_email'   => 'fitness@tgs-langenhain.de',
), 'Die Abteilung Fitness & Turnen bietet über 13 Kurse und Trainings für alle Altersklassen — von Hatha Yoga über Kinderturnen bis hin zu Seniorensport.', array(
    'excerpt'    => '13 Kurse & Trainings für alle Altersklassen',
    'menu_order' => 1,
) );

tgs_import_post( 'tgs_abteilung', 'Handball', array(
    '_tgs_abt_icon'    => '🤾',
    '_tgs_abt_leitung' => 'HSG EppLa Jugendwart',
    '_tgs_abt_email'   => 'handball@tgs-langenhain.de',
), 'Die Handballabteilung der TGS ist Teil der HSG EppLa (Eppstein/Langenhain). Jugend- und Aktivenmannschaften trainieren am Sportplatz „Zu den Eichen".', array(
    'excerpt'    => 'HSG EppLa · Jugend & Aktive Mannschaften',
    'menu_order' => 2,
) );

tgs_import_post( 'tgs_abteilung', 'Tischtennis', array(
    '_tgs_abt_icon'    => '🏓',
    '_tgs_abt_leitung' => 'Abteilungsleitung Tischtennis',
    '_tgs_abt_email'   => 'tischtennis@tgs-langenhain.de',
), 'Von Anfängern bis Wettkampfspielern — die Tischtennisabteilung bietet Training für alle Niveaus in der Wilhelm-Busch-Halle.', array(
    'excerpt'    => 'Anfänger bis Wettkampf',
    'menu_order' => 3,
) );

tgs_import_post( 'tgs_abteilung', 'Radsport', array(
    '_tgs_abt_icon'    => '🚵',
    '_tgs_abt_leitung' => 'Olaf Bertko',
    '_tgs_abt_email'   => 'bertko@gmx.de',
    '_tgs_abt_stv'     => 'Eric Völker',
), 'Mountainbike, Gravel & E-MTB — geführte Feierabendtouren jeden Dienstag, Fahrtechnik-Trainings und Kids-on-Bike für Kinder. Seit 2021 eigene Abteilung.', array(
    'excerpt'    => 'MTB · Gravel · E-Bike · Kids-on-Bike',
    'menu_order' => 4,
) );

// ============================================================
// SPORTSTÄTTEN
// ============================================================
echo '<h2>Sportstätten</h2>';

tgs_import_post( 'tgs_sportstaette', 'Wilhelm-Busch-Halle', array(
    '_tgs_adresse'          => 'Turnhalle TGS 1886 e.V. Langenhain',
    '_tgs_plz_ort'          => '65719 Hofheim-Langenhain',
    '_tgs_maps_link'        => 'https://goo.gl/maps/bF4nWg1MZGyhnTMj6',
    '_tgs_ausstattung'      => 'Haupthalle mit Sportboden für alle Indoor-Kurse und Trainings',
    '_tgs_barrierefreiheit' => 'Ebenerdiger Zugang, rollstuhlgerecht',
    '_tgs_parkplaetze'      => 'Kostenlose Parkplätze direkt vor der Halle',
), 'Die Wilhelm-Busch-Halle (WBH) ist die Heimat der meisten TGS-Kurse und -Trainings. Die Turnhalle liegt zentral in Langenhain und bietet Platz für Fitness, Turnen, Tischtennis und vieles mehr.' );

tgs_import_post( 'tgs_sportstaette', 'Sportplatz „Zu den Eichen"', array(
    '_tgs_adresse'          => 'Sportplatzstraße 13',
    '_tgs_plz_ort'          => '65719 Hofheim-Langenhain',
    '_tgs_maps_link'        => 'https://maps.google.com/?q=Sportplatzstraße+13+Hofheim-Langenhain',
    '_tgs_ausstattung'      => 'Rasenplatz, Laufbahn, Handball-Feld',
    '_tgs_barrierefreiheit' => 'Zugang über Sportplatzstraße',
    '_tgs_parkplaetze'      => 'Parkplatz am Sportplatz',
), 'Der Sportplatz „Zu den Eichen" ist die Heimat der Handballabteilung (HSG EppLa) und Treffpunkt der Radsportler. Direkt am Sportplatz befindet sich die Vereinsgaststätte „Da Luca".' );

// ============================================================
// KURSE — Fitness-Kurse
// ============================================================
echo '<h2>Fitness-Kurse</h2>';

tgs_import_post( 'tgs_kurs', 'Hatha Yoga', array(
    '_tgs_wochentag'             => 'Di',
    '_tgs_uhrzeit'               => '19:00',
    '_tgs_uhrzeit_ende'          => '20:00',
    '_tgs_ort'                   => 'Wilhelm-Busch-Halle',
    '_tgs_status'                => 'frei',
    '_tgs_max_teilnehmer'        => '20',
    '_tgs_zielgruppe'            => 'Erwachsene',
    '_tgs_ansprechpartner'       => 'Kursleitung Yoga',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
    '_tgs_mitbringen'            => 'Yogamatte, bequeme Kleidung',
), 'Hatha Yoga verbindet Körperhaltungen (Asanas), Atemübungen (Pranayama) und Meditation zu einer ganzheitlichen Praxis. Der Kurs ist für Anfänger und Fortgeschrittene gleichermaßen geeignet.

Bitte bringe eine eigene Yogamatte und bequeme Kleidung mit. Der Raum wird auf angenehme Temperatur beheizt.

Voraussetzung: Mitgliedschaft in der TGS Langenhain.', array( 'kategorie' => 'fitness-kurse' ) );

tgs_import_post( 'tgs_kurs', 'Pilates', array(
    '_tgs_wochentag'             => 'Mo',
    '_tgs_uhrzeit'               => '18:30',
    '_tgs_uhrzeit_ende'          => '19:30',
    '_tgs_ort'                   => 'Wilhelm-Busch-Halle',
    '_tgs_status'                => 'warteliste',
    '_tgs_max_teilnehmer'        => '15',
    '_tgs_zielgruppe'            => 'Erwachsene',
    '_tgs_ansprechpartner'       => 'Kursleitung Pilates',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
    '_tgs_mitbringen'            => 'Matte, Handtuch',
), 'Pilates stärkt die Tiefenmuskulatur, verbessert die Körperhaltung und fördert die Beweglichkeit. Geeignet für alle Fitnesslevel.', array( 'kategorie' => 'fitness-kurse' ) );

tgs_import_post( 'tgs_kurs', 'Zumba® Fitness', array(
    '_tgs_wochentag'             => 'Mi',
    '_tgs_uhrzeit'               => '19:30',
    '_tgs_uhrzeit_ende'          => '20:30',
    '_tgs_ort'                   => 'Wilhelm-Busch-Halle',
    '_tgs_status'                => 'frei',
    '_tgs_zielgruppe'            => 'Frauen',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
    '_tgs_mitbringen'            => 'Sportkleidung, Hallenschuhe, Getränk',
), 'Tanz-Fitness zu lateinamerikanischer und internationaler Musik. Spaß und Bewegung stehen im Vordergrund — keine Vorkenntnisse nötig.', array( 'kategorie' => 'fitness-kurse' ) );

tgs_import_post( 'tgs_kurs', 'Body Complete', array(
    '_tgs_wochentag'             => 'Do',
    '_tgs_uhrzeit'               => '18:00',
    '_tgs_uhrzeit_ende'          => '19:00',
    '_tgs_ort'                   => 'Wilhelm-Busch-Halle',
    '_tgs_status'                => 'frei',
    '_tgs_zielgruppe'            => 'Frauen',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Rücken- und Bauchtraining für Frauen. Kräftigung der gesamten Rumpfmuskulatur mit gezielten Übungen.', array( 'kategorie' => 'fitness-kurse' ) );

tgs_import_post( 'tgs_kurs', 'Elements Training', array(
    '_tgs_wochentag'             => 'Do',
    '_tgs_uhrzeit'               => '19:30',
    '_tgs_uhrzeit_ende'          => '20:30',
    '_tgs_ort'                   => 'Wilhelm-Busch-Halle',
    '_tgs_status'                => 'frei',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Funktionelles Ganzkörpertraining mit Elementen aus verschiedenen Fitness-Disziplinen.', array( 'kategorie' => 'fitness-kurse' ) );

tgs_import_post( 'tgs_kurs', 'Best of Fitness', array(
    '_tgs_wochentag'             => 'Mi',
    '_tgs_uhrzeit'               => '09:00',
    '_tgs_uhrzeit_ende'          => '10:00',
    '_tgs_ort'                   => 'Wilhelm-Busch-Halle',
    '_tgs_status'                => 'frei',
    '_tgs_zielgruppe'            => 'Frauen',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Abwechslungsreiches Fitnesstraining am Vormittag — das Beste aus verschiedenen Kursformaten.', array( 'kategorie' => 'fitness-kurse' ) );

tgs_import_post( 'tgs_kurs', 'Rückenfreundlich für alle', array(
    '_tgs_wochentag'             => 'Mo',
    '_tgs_uhrzeit'               => '09:30',
    '_tgs_uhrzeit_ende'          => '10:30',
    '_tgs_ort'                   => 'Wilhelm-Busch-Halle',
    '_tgs_status'                => 'frei',
    '_tgs_zielgruppe'            => 'Alle',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Sanftes Training zur Stärkung der Rückenmuskulatur. Für alle Altersklassen und Fitnesslevel geeignet.', array( 'kategorie' => 'fitness-kurse' ) );

tgs_import_post( 'tgs_kurs', 'Männer-Fit', array(
    '_tgs_wochentag'             => 'Fr',
    '_tgs_uhrzeit'               => '18:00',
    '_tgs_uhrzeit_ende'          => '19:30',
    '_tgs_ort'                   => 'Wilhelm-Busch-Halle',
    '_tgs_status'                => 'frei',
    '_tgs_zielgruppe'            => 'Männer',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Fitness-Training speziell für Männer — Kraft, Ausdauer und Beweglichkeit.', array( 'kategorie' => 'fitness-kurse' ) );

// ============================================================
// KURSE — Fitness-Trainings
// ============================================================
echo '<h2>Fitness-Trainings</h2>';

tgs_import_post( 'tgs_kurs', 'Allgemeine Gymnastik Frauen', array(
    '_tgs_wochentag' => 'Mi', '_tgs_uhrzeit' => '20:00', '_tgs_uhrzeit_ende' => '21:00',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei', '_tgs_zielgruppe' => 'Frauen',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Gymnastiktraining für Frauen — Ausdauer, Kräftigung und Dehnung in geselliger Runde.', array( 'kategorie' => 'fitness-training' ) );

tgs_import_post( 'tgs_kurs', 'Herren-Turnen „Die Borzeler"', array(
    '_tgs_wochentag' => 'Di', '_tgs_uhrzeit' => '20:00', '_tgs_uhrzeit_ende' => '22:00',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei', '_tgs_zielgruppe' => 'Männer',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Die traditionsreiche Herrensportgruppe der TGS — Turnen, Fitness und Kameradschaft seit Jahrzehnten.', array( 'kategorie' => 'fitness-training' ) );

tgs_import_post( 'tgs_kurs', 'Power Frauen', array(
    '_tgs_wochentag' => 'Do', '_tgs_uhrzeit' => '20:00', '_tgs_uhrzeit_ende' => '21:00',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei', '_tgs_zielgruppe' => 'Frauen',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Power-Gymnastik für Frauen — intensives Training für Kraft und Ausdauer.', array( 'kategorie' => 'fitness-training' ) );

tgs_import_post( 'tgs_kurs', 'Nordic Walking', array(
    '_tgs_wochentag' => 'Di', '_tgs_uhrzeit' => '17:00', '_tgs_uhrzeit_ende' => '18:30',
    '_tgs_ort' => 'Treffpunkt Wilhelm-Busch-Halle', '_tgs_status' => 'frei', '_tgs_zielgruppe' => 'Alle',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Nordic Walking in der Natur rund um Langenhain. Treffpunkt an der Wilhelm-Busch-Halle.', array( 'kategorie' => 'fitness-training' ) );

// ============================================================
// KURSE — Kinder & Jugend
// ============================================================
echo '<h2>Kinder & Jugend</h2>';

tgs_import_post( 'tgs_kurs', 'Kinderturnen 3–6 Jahre', array(
    '_tgs_wochentag' => 'Fr', '_tgs_uhrzeit' => '15:30', '_tgs_uhrzeit_ende' => '16:30',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei', '_tgs_max_teilnehmer' => '20',
    '_tgs_zielgruppe' => 'Kinder 3–6 Jahre', '_tgs_mitbringen' => 'Sportkleidung, Hallenschuhe, Getränk',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Turnen, Spielen, Toben — altersgerechte Bewegungsangebote für Kinder von 3 bis 6 Jahren.', array( 'kategorie' => 'kinder-jugend' ) );

tgs_import_post( 'tgs_kurs', 'Eltern-Kind Turnen', array(
    '_tgs_wochentag' => 'Mi', '_tgs_uhrzeit' => '09:30', '_tgs_uhrzeit_ende' => '10:15',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei',
    '_tgs_zielgruppe' => 'Kinder 1–4 Jahre mit Elternteil',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Gemeinsam turnen, klettern und spielen — für Kinder von 1 bis 4 Jahren mit einem Elternteil.', array( 'kategorie' => 'kinder-jugend' ) );

tgs_import_post( 'tgs_kurs', 'Powerhour Kids', array(
    '_tgs_wochentag' => 'Fr', '_tgs_uhrzeit' => '15:00', '_tgs_uhrzeit_ende' => '15:30',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei',
    '_tgs_zielgruppe' => 'Kinder',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Kurze, intensive Bewegungseinheit für Kinder — Action und Spaß.', array( 'kategorie' => 'kinder-jugend' ) );

tgs_import_post( 'tgs_kurs', 'Zappelgruppe', array(
    '_tgs_wochentag' => 'Mi', '_tgs_uhrzeit' => '10:15', '_tgs_uhrzeit_ende' => '11:00',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei',
    '_tgs_zielgruppe' => 'Kleinkinder',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Bewegungsangebot für die Kleinsten — krabbeln, toben, entdecken.', array( 'kategorie' => 'kinder-jugend' ) );

tgs_import_post( 'tgs_kurs', 'Kids-on-Bike MTB', array(
    '_tgs_wochentag' => 'Sa', '_tgs_uhrzeit' => '10:00', '_tgs_uhrzeit_ende' => '12:00',
    '_tgs_ort' => 'Langenhainer Wald', '_tgs_status' => 'frei',
    '_tgs_zielgruppe' => 'Kinder ab 6 Jahre',
    '_tgs_ansprechpartner' => 'Olaf Bertko',
    '_tgs_ansprechpartner_email' => 'bertko@gmx.de',
    '_tgs_ansprechpartner_tel' => '0173 8966223',
    '_tgs_mitbringen' => 'MTB, Helm, Getränk',
), 'Mountainbike-Kursangebot für Kinder — spielerisch Fahrtechnik lernen, gemeinsam Trails entdecken im Langenhainer Wald.', array( 'kategorie' => 'kinder-jugend' ) );

// ============================================================
// KURSE — Senioren
// ============================================================
echo '<h2>Senioren</h2>';

tgs_import_post( 'tgs_kurs', 'Aktiv bis 100', array(
    '_tgs_wochentag' => 'Di', '_tgs_uhrzeit' => '10:00', '_tgs_uhrzeit_ende' => '11:00',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei',
    '_tgs_zielgruppe' => 'Senioren',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Sanftes Bewegungsprogramm für ältere Teilnehmer — Mobilität, Balance und Koordination erhalten.', array( 'kategorie' => 'senioren' ) );

tgs_import_post( 'tgs_kurs', 'Herren-Turnen „Die Oldies"', array(
    '_tgs_wochentag' => 'Do', '_tgs_uhrzeit' => '19:00', '_tgs_uhrzeit_ende' => '20:00',
    '_tgs_ort' => 'Wilhelm-Busch-Halle', '_tgs_status' => 'frei',
    '_tgs_zielgruppe' => 'Senioren / Männer',
    '_tgs_ansprechpartner_email' => 'fitness@tgs-langenhain.de',
), 'Herren-Turnen für die erfahrene Generation — Fitness und Kameradschaft.', array( 'kategorie' => 'senioren' ) );

// ============================================================
// NEWS-BEITRÄGE
// ============================================================
echo '<h2>News-Beiträge</h2>';

tgs_import_post( 'post', '55. Langenhainer Rennewäms-Cup – Jugend-Edition 2026', array(), 
'Das Wochenende gehörte dem Nachwuchs: Beim <strong>55. Rennewäms-Cup</strong> traten Mannschaften aus dem gesamten Rhein-Main-Gebiet an der Wilhelm-Busch-Halle an. Drei Altersklassen, volles Programm, strahlende Gesichter.

<blockquote>„Die U10 hat einfach alles gegeben — und das hat man gesehen." — Trainer HSG EppLa</blockquote>

Die U10 der HSG EppLa gewann ihr erstes Heimturnier seit vier Jahren. Ein starkes Signal für die hervorragende Jugendarbeit, die Trainer und Eltern gemeinsam leisten. Alle Fotos und Ergebnisse im Mitgliederbereich.

<strong>Ansprechpartner:</strong> HSG EppLa Jugendwart — <a href="mailto:handball@tgs-langenhain.de">handball@tgs-langenhain.de</a>' );

tgs_import_post( 'post', 'Kindeswohl in der TGS Langenhain — unser Konzept', array(),
'Die TGS 1886 Langenhain e.V. nimmt ihre Schutzpflicht gegenüber allen Kindern und Jugendlichen im Verein sehr ernst. In Zusammenarbeit mit dem Landessportbund Hessen haben wir ein umfassendes Kindeswohl-Konzept erarbeitet.

Alle Übungsleiter und Trainer, die mit Kindern und Jugendlichen arbeiten, legen ein erweitertes Führungszeugnis vor und nehmen regelmäßig an Schulungen teil.

Bei Fragen oder Anliegen wenden Sie sich an den Vorstand: <a href="mailto:vorstand@tgs-langenhain.de">vorstand@tgs-langenhain.de</a>' );

tgs_import_post( 'post', 'Kids-on-Bike Saison 2026 — Anmeldung läuft', array(),
'Die MTB-Saison 2026 für Kinder und Jugendliche startet! Ab sofort können sich Kinder ab 6 Jahren für das <strong>Kids-on-Bike</strong> Programm anmelden.

Jeden Samstag von 10:00 bis 12:00 Uhr im Langenhainer Wald — spielerisch Fahrtechnik lernen, gemeinsam Trails entdecken und Spaß haben.

<strong>Voraussetzungen:</strong> Eigenes Mountainbike, Helm, Mitgliedschaft in der TGS.

<strong>Anmeldung:</strong> Olaf Bertko, <a href="mailto:bertko@gmx.de">bertko@gmx.de</a>, Tel. 0173 8966223' );

// ============================================================
// ZUSAMMENFASSUNG
// ============================================================
echo '<h2>Fertig!</h2>';
echo '<p><strong>' . $created . ' Einträge erstellt</strong>, ' . $skipped . ' übersprungen (bereits vorhanden).</p>';
echo '<p>👉 <a href="' . home_url() . '">Zur Startseite</a></p>';
echo '<p>👉 <a href="' . admin_url( 'edit.php?post_type=tgs_kurs' ) . '">Kurse im Backend</a></p>';
echo '<p style="color:#C07020;margin-top:2rem;">⚠ <strong>Diese Datei nach dem Import löschen!</strong><br><code>wp-content/themes/tgs-langenhain-theme/import.php</code></p>';
echo '</body></html>';
