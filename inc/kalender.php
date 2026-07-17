<?php
/**
 * Kurs-Kalender — abonnierbare iCal-Feeds (.ics)
 *
 * Der Feed wird bei jedem Abruf LIVE aus den Kursdaten erzeugt – es gibt keine
 * gespeicherte Datei. Wer abonniert, bekommt damit jede Änderung (Zeit, Ort,
 * Saisonwechsel, Ausfall) automatisch, sobald sein Kalender das nächste Mal
 * nachfragt.
 *
 * Aufbau:
 *  - Pro Kurs entsteht eine Serie (VEVENT + RRULE), nicht hunderte Einzeltermine.
 *  - Saisonkurse ergeben zwei Serien (Sommer/Winter) mit BYMONTH; eine
 *    Winterpause lässt die Winter-Serie einfach weg.
 *  - Ausfälle/Pausen aus tgs_meldung werden per EXDATE aus der Serie genommen
 *    UND als eigener „Fällt aus"-Eintrag sichtbar gemacht (sonst verschwände
 *    der Termin kommentarlos aus dem Kalender).
 *
 * Datenschutz: im Feed stehen bewusst KEINE Namen von Kursleitungen und keine
 * E-Mail-Adressen – ein Feed ist öffentlich und maschinenlesbar.
 *
 * URLs:
 *  /kalender/kurse.ics                – alle Kurse
 *  /kalender/kurs-<ID>.ics            – ein Kurs
 *  /kalender/sportstaette-<ID>.ics    – Belegung einer Sportstätte
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * iCal-Grundlagen (RFC 5545)
 * ========================================================================= */

/** Text für iCal maskieren (Reihenfolge wichtig: Backslash zuerst). */
function tgs_ics_esc( $text ) {
    $text = wp_strip_all_tags( (string) $text );
    // Entities auflösen: Titel enthalten durch wptexturize z. B. „ als &#8220;
    // – im Kalender stünde sonst wörtlich „&#8220;“ statt des Zeichens.
    $text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
    $text = str_replace( array( "\r\n", "\r" ), "\n", $text );
    $text = str_replace( array( '\\', ';', ',' ), array( '\\\\', '\\;', '\\,' ), $text );
    return str_replace( "\n", '\\n', $text );
}

/**
 * Zeile auf 75 Oktette falten (RFC 5545). Faltung erfolgt an Zeichen-, nicht
 * an Byte-Grenzen, damit Umlaute nicht zerrissen werden.
 */
function tgs_ics_fold( $line ) {
    if ( strlen( $line ) <= 73 ) return $line . "\r\n";
    $chars = preg_split( '//u', $line, -1, PREG_SPLIT_NO_EMPTY );
    $out = ''; $len = 0;
    foreach ( $chars as $c ) {
        $cl = strlen( $c );
        if ( $len + $cl > 73 ) { $out .= "\r\n "; $len = 1; }
        $out .= $c; $len += $cl;
    }
    return $out . "\r\n";
}

/** Eine Property-Zeile bauen: NAME;PARAMS:VALUE */
function tgs_ics_line( $name, $value, $params = '' ) {
    return tgs_ics_fold( $name . ( $params ? ';' . $params : '' ) . ':' . $value );
}

/**
 * VTIMEZONE für Europe/Berlin.
 * Nötig, damit „19:30" auch nach der Zeitumstellung 19:30 bleibt – bei reinen
 * UTC-Zeiten würde eine wöchentliche Serie im Winter um eine Stunde springen.
 */
function tgs_ics_vtimezone() {
    $o  = "BEGIN:VTIMEZONE\r\n";
    $o .= "TZID:Europe/Berlin\r\n";
    $o .= "X-LIC-LOCATION:Europe/Berlin\r\n";
    $o .= "BEGIN:DAYLIGHT\r\n";
    $o .= "TZOFFSETFROM:+0100\r\nTZOFFSETTO:+0200\r\nTZNAME:CEST\r\n";
    $o .= "DTSTART:19700329T020000\r\n";
    $o .= "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
    $o .= "END:DAYLIGHT\r\n";
    $o .= "BEGIN:STANDARD\r\n";
    $o .= "TZOFFSETFROM:+0200\r\nTZOFFSETTO:+0100\r\nTZNAME:CET\r\n";
    $o .= "DTSTART:19701025T030000\r\n";
    $o .= "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
    $o .= "END:STANDARD\r\n";
    $o .= "END:VTIMEZONE\r\n";
    return $o;
}

/** Wochentag-Kürzel („Mo") → iCal-BYDAY („MO"). */
function tgs_ics_byday( $tag ) {
    $map = array( 'Mo' => 'MO', 'Di' => 'TU', 'Mi' => 'WE', 'Do' => 'TH', 'Fr' => 'FR', 'Sa' => 'SA', 'So' => 'SU' );
    $tag = substr( trim( (string) $tag ), 0, 2 );
    return isset( $map[ $tag ] ) ? $map[ $tag ] : '';
}

/** Wochentag-Kürzel → ISO-Nummer (Mo=1 … So=7). */
function tgs_ics_dow( $tag ) {
    $map = array( 'Mo' => 1, 'Di' => 2, 'Mi' => 3, 'Do' => 4, 'Fr' => 5, 'Sa' => 6, 'So' => 7 );
    $tag = substr( trim( (string) $tag ), 0, 2 );
    return isset( $map[ $tag ] ) ? $map[ $tag ] : 0;
}

/** Vereins-Zeitzone (Objekt). */
function tgs_ics_tz() {
    static $tz = null;
    if ( $tz === null ) $tz = new DateTimeZone( 'Europe/Berlin' );
    return $tz;
}

/** UID-Domain (ohne Protokoll/Port), z. B. „tgs-langenhain.de". */
function tgs_ics_host() {
    $h = wp_parse_url( home_url(), PHP_URL_HOST );
    return $h ? $h : 'tgs-langenhain.de';
}

/**
 * SEQUENCE-Zähler eines Kurses. Kalender erkennen an einer steigenden Zahl,
 * dass ein Termin AKTUALISIERT wurde (statt ihn als neu/unverändert zu sehen).
 */
function tgs_ics_seq( $post_id ) {
    return (int) get_post_meta( $post_id, '_tgs_ical_seq', true );
}

/** Zähler erhöhen — bei jeder Änderung am Kurs oder an seinen Meldungen. */
function tgs_ics_bump_seq( $kurs_id ) {
    $kurs_id = (int) $kurs_id;
    if ( $kurs_id > 0 ) update_post_meta( $kurs_id, '_tgs_ical_seq', tgs_ics_seq( $kurs_id ) + 1 );
}

function tgs_ics_bump_on_save( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    tgs_ics_bump_seq( $post_id );
}
add_action( 'save_post_tgs_kurs', 'tgs_ics_bump_on_save' );

/* =========================================================================
 * Serien eines Kurses (ganzjährig bzw. Sommer/Winter)
 * ========================================================================= */

/**
 * Liefert die Termin-Serien eines Kurses.
 * Jede Serie: key, tag, zeit, ende, ort, ort_id, monate (null = ganzjährig).
 */
function tgs_ics_kurs_serien( $kurs_id ) {
    $t = tgs_kurs_termin( $kurs_id );
    $mk = function ( $key, $d, $monate ) {
        return array(
            'key'    => $key,
            'tag'    => $d['tag'],
            'zeit'   => $d['zeit'],
            'ende'   => isset( $d['ende'] ) ? $d['ende'] : '',
            'ort'    => isset( $d['ort'] ) ? $d['ort'] : '',
            'ort_id' => isset( $d['ort_id'] ) ? (int) $d['ort_id'] : 0,
            'monate' => $monate,
        );
    };

    if ( empty( $t['saisonal'] ) ) {
        if ( ! $t['tag'] || ! $t['zeit'] ) return array();
        return array( $mk( 'ganzjahr', $t, null ) );
    }

    // Wintermonate (mit Jahreswechsel, z. B. 10 → 3), Sommer = der Rest.
    $von = (int) $t['von']; $bis = (int) $t['bis'];
    $winter_m = array(); $sommer_m = array();
    for ( $m = 1; $m <= 12; $m++ ) {
        $ist = ( $von <= $bis ) ? ( $m >= $von && $m <= $bis ) : ( $m >= $von || $m <= $bis );
        if ( $ist ) $winter_m[] = $m; else $sommer_m[] = $m;
    }

    $serien = array();
    if ( ! empty( $t['sommer']['tag'] ) && ! empty( $t['sommer']['zeit'] ) && $sommer_m ) {
        $serien[] = $mk( 'sommer', $t['sommer'], $sommer_m );
    }
    $w = $t['winter'];
    if ( empty( $w['pause'] ) && ! empty( $w['tag'] ) && ! empty( $w['zeit'] ) && $winter_m ) {
        $serien[] = $mk( 'winter', $w, $winter_m );
    }
    return $serien;
}

/**
 * Stabiler Serienstart: erster passender Termin ab dem Veröffentlichungsdatum
 * des Kurses. Muss stabil sein — ein bei jedem Abruf neu berechneter Start
 * würde die Serie im Kalender ständig verschieben.
 */
function tgs_ics_anchor( $kurs_id, $serie ) {
    $dow = tgs_ics_dow( $serie['tag'] );
    if ( ! $dow || ! preg_match( '/^(\d{1,2}):(\d{2})$/', trim( $serie['zeit'] ), $m ) ) return null;

    $start = get_post_field( 'post_date', $kurs_id );
    $d = DateTime::createFromFormat( 'Y-m-d H:i:s', $start ? $start : '2024-01-01 00:00:00', tgs_ics_tz() );
    if ( ! $d ) return null;
    $d->setTime( (int) $m[1], (int) $m[2], 0 );

    for ( $i = 0; $i < 400; $i++ ) {
        $passt_tag   = ( (int) $d->format( 'N' ) === $dow );
        $passt_monat = ( $serie['monate'] === null || in_array( (int) $d->format( 'n' ), $serie['monate'], true ) );
        if ( $passt_tag && $passt_monat ) return $d;
        $d->modify( '+1 day' );
    }
    return null;
}

/** Dauer in Minuten aus Start/Ende; ohne Ende: 60 Minuten. */
function tgs_ics_dauer( $zeit, $ende ) {
    if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', trim( (string) $ende ), $e ) ) return 60;
    if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', trim( (string) $zeit ), $s ) ) return 60;
    $min = ( (int) $e[1] * 60 + (int) $e[2] ) - ( (int) $s[1] * 60 + (int) $s[2] );
    if ( $min <= 0 ) $min += 24 * 60; // über Mitternacht
    return $min;
}

/** Ortsangabe für LOCATION: Name + Adresse der verknüpften Sportstätte. */
function tgs_ics_location( $ort, $ort_id ) {
    $teile = array();
    if ( $ort ) $teile[] = $ort;
    if ( $ort_id > 0 && get_post_status( $ort_id ) === 'publish' ) {
        $adr = get_post_meta( $ort_id, '_tgs_adresse', true );
        $plz = get_post_meta( $ort_id, '_tgs_plz_ort', true );
        if ( $adr ) $teile[] = $adr;
        if ( $plz ) $teile[] = $plz;
    }
    return implode( ', ', array_unique( array_filter( $teile ) ) );
}

/* =========================================================================
 * VEVENTs eines Kurses
 * ========================================================================= */

/**
 * @param int $kurs_id
 * @param int $only_ort  Nur Serien an dieser Sportstätte (0 = alle).
 */
function tgs_ics_kurs_events( $kurs_id, $only_ort = 0 ) {
    $serien = tgs_ics_kurs_serien( $kurs_id );
    if ( empty( $serien ) ) return '';

    $titel = get_the_title( $kurs_id );
    $url   = get_permalink( $kurs_id );
    $seq   = tgs_ics_seq( $kurs_id );
    $host  = tgs_ics_host();
    $stamp = get_post_time( 'Ymd\THis\Z', true, $kurs_id );
    if ( ! $stamp ) $stamp = gmdate( 'Ymd\THis\Z' );
    $mod   = get_post_modified_time( 'Ymd\THis\Z', true, $kurs_id );
    if ( ! $mod ) $mod = $stamp;

    $desc = get_the_excerpt( $kurs_id );
    if ( ! $desc ) $desc = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $kurs_id ) ), 40 );
    $desc = trim( $desc );
    if ( $url ) $desc = trim( $desc . "\n\nAlle Infos: " . $url );

    $meldungen = tgs_kurs_aktive_meldungen( $kurs_id );
    $out = '';

    foreach ( $serien as $serie ) {
        if ( $only_ort > 0 && (int) $serie['ort_id'] !== $only_ort ) continue;

        $anchor = tgs_ics_anchor( $kurs_id, $serie );
        if ( ! $anchor ) continue;

        $dauer = tgs_ics_dauer( $serie['zeit'], $serie['ende'] );
        $ende  = clone $anchor;
        $ende->modify( '+' . $dauer . ' minutes' );

        $rrule = 'FREQ=WEEKLY;BYDAY=' . tgs_ics_byday( $serie['tag'] );
        if ( $serie['monate'] ) $rrule .= ';BYMONTH=' . implode( ',', $serie['monate'] );

        // Ausgefallene Einzeltermine aus der Serie nehmen
        $exdates = tgs_ics_exdates( $meldungen, $serie, $anchor );

        $out .= "BEGIN:VEVENT\r\n";
        $out .= tgs_ics_line( 'UID', 'tgs-kurs-' . $kurs_id . '-' . $serie['key'] . '@' . $host );
        $out .= tgs_ics_line( 'DTSTAMP', $stamp );
        $out .= tgs_ics_line( 'LAST-MODIFIED', $mod );
        $out .= tgs_ics_line( 'SEQUENCE', (string) $seq );
        $out .= tgs_ics_line( 'DTSTART', $anchor->format( 'Ymd\THis' ), 'TZID=Europe/Berlin' );
        $out .= tgs_ics_line( 'DTEND', $ende->format( 'Ymd\THis' ), 'TZID=Europe/Berlin' );
        $out .= tgs_ics_line( 'RRULE', $rrule );
        if ( $exdates ) {
            $out .= tgs_ics_line( 'EXDATE', implode( ',', $exdates ), 'TZID=Europe/Berlin' );
        }
        $out .= tgs_ics_line( 'SUMMARY', tgs_ics_esc( $titel ) );
        $out .= tgs_ics_line( 'DESCRIPTION', tgs_ics_esc( $desc ) );
        $loc = tgs_ics_location( $serie['ort'], $serie['ort_id'] );
        if ( $loc ) $out .= tgs_ics_line( 'LOCATION', tgs_ics_esc( $loc ) );
        if ( $url ) $out .= tgs_ics_line( 'URL', esc_url_raw( $url ) );
        $out .= tgs_ics_line( 'TRANSP', 'OPAQUE' );
        $out .= "END:VEVENT\r\n";
    }

    // Sichtbare „Fällt aus" / „Pause"-Einträge
    $out .= tgs_ics_meldung_events( $kurs_id, $meldungen, $serien, $only_ort );
    return $out;
}

/**
 * EXDATEs einer Serie aus Ausfall- und Pause-Meldungen.
 * Ein Datum zählt nur, wenn es überhaupt zur Serie gehört (Wochentag + Monat).
 */
function tgs_ics_exdates( $meldungen, $serie, $anchor ) {
    $dow  = tgs_ics_dow( $serie['tag'] );
    $zeit = $anchor->format( 'His' );
    $ex   = array();

    $passt = function ( DateTime $d ) use ( $dow, $serie ) {
        if ( (int) $d->format( 'N' ) !== $dow ) return false;
        if ( $serie['monate'] !== null && ! in_array( (int) $d->format( 'n' ), $serie['monate'], true ) ) return false;
        return true;
    };

    foreach ( $meldungen as $m ) {
        $typ = get_post_meta( $m->ID, '_tgs_meld_typ', true );

        if ( $typ === 'ausfall' ) {
            $datum = get_post_meta( $m->ID, '_tgs_meld_datum', true );
            if ( ! $datum ) continue;
            $d = DateTime::createFromFormat( 'Y-m-d', $datum, tgs_ics_tz() );
            if ( $d && $passt( $d ) ) $ex[] = $d->format( 'Ymd' ) . 'T' . $zeit;

        } elseif ( $typ === 'pause' ) {
            $von = get_post_meta( $m->ID, '_tgs_meld_von', true );
            $bis = get_post_meta( $m->ID, '_tgs_meld_bis', true );
            if ( ! $bis ) continue;
            $d = DateTime::createFromFormat( 'Y-m-d', $von ? $von : current_time( 'Y-m-d' ), tgs_ics_tz() );
            $e = DateTime::createFromFormat( 'Y-m-d', $bis, tgs_ics_tz() );
            if ( ! $d || ! $e ) continue;
            for ( $i = 0; $i < 400 && $d <= $e; $i++ ) {
                if ( $passt( $d ) ) $ex[] = $d->format( 'Ymd' ) . 'T' . $zeit;
                $d->modify( '+1 day' );
            }
        }
    }
    return array_values( array_unique( $ex ) );
}

/**
 * Ausfälle/Pausen zusätzlich als eigene Einträge — damit der Termin nicht
 * wortlos aus dem Kalender verschwindet, sondern man SIEHT, dass er ausfällt.
 */
function tgs_ics_meldung_events( $kurs_id, $meldungen, $serien, $only_ort = 0 ) {
    if ( empty( $meldungen ) || empty( $serien ) ) return '';
    $titel = get_the_title( $kurs_id );
    $url   = get_permalink( $kurs_id );
    $host  = tgs_ics_host();
    $out   = '';

    // Referenz-Serie für Uhrzeit/Ort (die erste passende genügt)
    $ref = null;
    foreach ( $serien as $s ) {
        if ( $only_ort > 0 && (int) $s['ort_id'] !== $only_ort ) continue;
        $ref = $s; break;
    }
    if ( ! $ref ) return '';

    foreach ( $meldungen as $m ) {
        $typ   = get_post_meta( $m->ID, '_tgs_meld_typ', true );
        $grund = trim( (string) get_post_meta( $m->ID, '_tgs_meld_text', true ) );
        $stamp = get_post_modified_time( 'Ymd\THis\Z', true, $m->ID );
        if ( ! $stamp ) $stamp = gmdate( 'Ymd\THis\Z' );

        if ( $typ === 'ausfall' ) {
            $datum = get_post_meta( $m->ID, '_tgs_meld_datum', true );
            if ( ! $datum ) continue;
            $d = DateTime::createFromFormat( 'Y-m-d', $datum, tgs_ics_tz() );
            if ( ! $d ) continue;
            if ( (int) $d->format( 'N' ) !== tgs_ics_dow( $ref['tag'] ) ) continue;
            if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', trim( $ref['zeit'] ), $z ) ) continue;
            $d->setTime( (int) $z[1], (int) $z[2], 0 );
            $e = clone $d;
            $e->modify( '+' . tgs_ics_dauer( $ref['zeit'], $ref['ende'] ) . ' minutes' );

            $desc = $grund ? $grund : 'Dieser Termin fällt aus.';
            if ( $url ) $desc .= "\n\nAlle Infos: " . $url;

            $out .= "BEGIN:VEVENT\r\n";
            $out .= tgs_ics_line( 'UID', 'tgs-ausfall-' . $m->ID . '@' . $host );
            $out .= tgs_ics_line( 'DTSTAMP', $stamp );
            $out .= tgs_ics_line( 'SEQUENCE', '0' );
            $out .= tgs_ics_line( 'DTSTART', $d->format( 'Ymd\THis' ), 'TZID=Europe/Berlin' );
            $out .= tgs_ics_line( 'DTEND', $e->format( 'Ymd\THis' ), 'TZID=Europe/Berlin' );
            $out .= tgs_ics_line( 'SUMMARY', tgs_ics_esc( 'Fällt aus: ' . $titel ) );
            $out .= tgs_ics_line( 'DESCRIPTION', tgs_ics_esc( $desc ) );
            $out .= tgs_ics_line( 'TRANSP', 'TRANSPARENT' );
            $out .= "END:VEVENT\r\n";

        } elseif ( $typ === 'pause' ) {
            $von = get_post_meta( $m->ID, '_tgs_meld_von', true );
            $bis = get_post_meta( $m->ID, '_tgs_meld_bis', true );
            if ( ! $bis ) continue;
            $d = DateTime::createFromFormat( 'Y-m-d', $von ? $von : current_time( 'Y-m-d' ), tgs_ics_tz() );
            $e = DateTime::createFromFormat( 'Y-m-d', $bis, tgs_ics_tz() );
            if ( ! $d || ! $e ) continue;
            $e->modify( '+1 day' ); // DTEND ist bei Ganztags-Terminen exklusiv

            $desc = $grund ? $grund : 'In diesem Zeitraum findet der Kurs nicht statt.';
            if ( $url ) $desc .= "\n\nAlle Infos: " . $url;

            $out .= "BEGIN:VEVENT\r\n";
            $out .= tgs_ics_line( 'UID', 'tgs-pause-' . $m->ID . '@' . $host );
            $out .= tgs_ics_line( 'DTSTAMP', $stamp );
            $out .= tgs_ics_line( 'SEQUENCE', '0' );
            $out .= tgs_ics_line( 'DTSTART', $d->format( 'Ymd' ), 'VALUE=DATE' );
            $out .= tgs_ics_line( 'DTEND', $e->format( 'Ymd' ), 'VALUE=DATE' );
            $out .= tgs_ics_line( 'SUMMARY', tgs_ics_esc( 'Pause: ' . $titel ) );
            $out .= tgs_ics_line( 'DESCRIPTION', tgs_ics_esc( $desc ) );
            $out .= tgs_ics_line( 'TRANSP', 'TRANSPARENT' );
            $out .= "END:VEVENT\r\n";
        }
    }
    return $out;
}

/* =========================================================================
 * Feed-Zusammenbau
 * ========================================================================= */

/** Alle veröffentlichten Kurs-IDs. */
function tgs_ics_alle_kurse() {
    return get_posts( array(
        'post_type' => 'tgs_kurs', 'post_status' => 'publish',
        'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC',
    ) );
}

/**
 * Feed-Definition aus dem Slug.
 * @return array|null  array( kurse => int[], name => string, ort => int )
 */
function tgs_ics_feed( $slug ) {
    $slug = sanitize_key( $slug );
    $club = get_bloginfo( 'name' );

    if ( $slug === 'kurse' ) {
        return array( 'kurse' => tgs_ics_alle_kurse(), 'name' => $club . ' – Kurse', 'ort' => 0 );
    }

    if ( preg_match( '/^kurs-(\d+)$/', $slug, $m ) ) {
        $id = (int) $m[1];
        if ( get_post_type( $id ) !== 'tgs_kurs' || get_post_status( $id ) !== 'publish' ) return null;
        return array( 'kurse' => array( $id ), 'name' => get_the_title( $id ) . ' – ' . $club, 'ort' => 0 );
    }

    if ( preg_match( '/^sportstaette-(\d+)$/', $slug, $m ) ) {
        $id = (int) $m[1];
        if ( get_post_type( $id ) !== 'tgs_sportstaette' || get_post_status( $id ) !== 'publish' ) return null;
        $kurse = array();
        foreach ( tgs_ics_alle_kurse() as $kid ) {
            foreach ( tgs_ics_kurs_serien( $kid ) as $s ) {
                if ( (int) $s['ort_id'] === $id ) { $kurse[] = $kid; break; }
            }
        }
        return array( 'kurse' => $kurse, 'name' => get_the_title( $id ) . ' – Belegung', 'ort' => $id );
    }

    return null;
}

/** Kompletten Kalender als String bauen. */
function tgs_ics_build( $feed ) {
    $o  = "BEGIN:VCALENDAR\r\n";
    $o .= "VERSION:2.0\r\n";
    $o .= tgs_ics_line( 'PRODID', '-//TGS 1886 Langenhain//Kurskalender//DE' );
    $o .= "CALSCALE:GREGORIAN\r\n";
    $o .= tgs_ics_line( 'X-WR-CALNAME', tgs_ics_esc( $feed['name'] ) );
    $o .= tgs_ics_line( 'X-WR-CALDESC', tgs_ics_esc( 'Immer aktuell – Änderungen und Ausfälle erscheinen automatisch.' ) );
    $o .= "X-WR-TIMEZONE:Europe/Berlin\r\n";
    // Bitte an den Kalender: stündlich nachfragen (Apple hält sich dran, Google leider nicht).
    $o .= "REFRESH-INTERVAL;VALUE=DURATION:PT1H\r\n";
    $o .= "X-PUBLISHED-TTL:PT1H\r\n";
    $o .= tgs_ics_vtimezone();
    foreach ( $feed['kurse'] as $kid ) {
        $o .= tgs_ics_kurs_events( $kid, $feed['ort'] );
    }
    $o .= "END:VCALENDAR\r\n";
    return $o;
}

/* =========================================================================
 * Auslieferung
 * ========================================================================= */

function tgs_ics_rewrite() {
    add_rewrite_rule( '^kalender/([a-z0-9\-]+)\.ics$', 'index.php?tgs_ics=$matches[1]', 'top' );
}
add_action( 'init', 'tgs_ics_rewrite' );

function tgs_ics_query_var( $vars ) {
    $vars[] = 'tgs_ics';
    return $vars;
}
add_filter( 'query_vars', 'tgs_ics_query_var' );

/** Rewrite-Regeln einmalig nach einem Theme-Update aktivieren. */
function tgs_ics_maybe_flush() {
    if ( get_option( 'tgs_rewrite_version' ) !== TGS_VERSION ) {
        flush_rewrite_rules( false );
        update_option( 'tgs_rewrite_version', TGS_VERSION );
    }
}
add_action( 'init', 'tgs_ics_maybe_flush', 99 );
add_action( 'after_switch_theme', 'flush_rewrite_rules' );

/**
 * WordPress hängt sonst per Canonical-Redirect einen Schrägstrich an
 * (…/kurse.ics → …/kurse.ics/). Manche Kalender-Clients folgen Redirects
 * nicht sauber, deshalb wird der Feed direkt ausgeliefert.
 */
function tgs_ics_no_canonical( $redirect ) {
    return get_query_var( 'tgs_ics' ) ? false : $redirect;
}
add_filter( 'redirect_canonical', 'tgs_ics_no_canonical' );

function tgs_ics_serve() {
    $slug = get_query_var( 'tgs_ics' );
    if ( ! $slug ) return;

    $feed = tgs_ics_feed( $slug );
    if ( ! $feed ) {
        status_header( 404 );
        header( 'Content-Type: text/plain; charset=utf-8' );
        echo 'Kalender nicht gefunden.';
        exit;
    }

    $body = tgs_ics_build( $feed );
    $etag = '"' . md5( $body ) . '"';

    // Unveränderter Feed → 304 statt Neuübertragung. Spart bei stündlich
    // fragenden Abonnenten spürbar Last.
    header( 'ETag: ' . $etag );
    header( 'Cache-Control: public, max-age=3600' );
    if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && trim( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) === $etag ) {
        status_header( 304 );
        exit;
    }

    status_header( 200 );
    header( 'Content-Type: text/calendar; charset=utf-8' );
    header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $slug ) . '.ics"' );
    header( 'Content-Length: ' . strlen( $body ) );
    echo $body;
    exit;
}
// Priorität 0: vor allen Umleitungen/Weiterleitungen anderer Hooks.
add_action( 'template_redirect', 'tgs_ics_serve', 0 );

/* =========================================================================
 * Frontend: Abo-Button
 * ========================================================================= */

/** Feed-URL (https) für einen Slug. */
function tgs_ics_url( $slug ) {
    if ( get_option( 'permalink_structure' ) ) {
        return home_url( '/kalender/' . $slug . '.ics' );
    }
    return add_query_arg( 'tgs_ics', $slug, home_url( '/' ) );
}

/** webcal://-URL — ein Tipp darauf abonniert direkt (iOS, macOS, Outlook …). */
function tgs_ics_webcal_url( $slug ) {
    return preg_replace( '#^https?://#', 'webcal://', tgs_ics_url( $slug ) );
}

/**
 * Abo-Box: abonnieren + Link kopieren.
 *
 * @param string $slug   z. B. 'kurse' oder 'kurs-123'
 * @param string $label  Beschriftung des Buttons.
 * @param string $hint   Erklärtext darunter.
 */
function tgs_kalender_abo_html( $slug, $label = 'Kalender abonnieren', $hint = '' ) {
    $url = tgs_ics_url( $slug );
    if ( $hint === '' ) {
        $hint = 'Termine landen automatisch in deinem Kalender – inklusive Änderungen und Ausfällen.';
    }
    ob_start();
    ?>
    <div class="tgs-abo">
        <a class="tgs-abo-btn" href="<?php echo esc_attr( tgs_ics_webcal_url( $slug ) ); ?>">
            <span class="tgs-abo-icon" aria-hidden="true">📅</span>
            <span><?php echo esc_html( $label ); ?></span>
        </a>
        <button type="button" class="tgs-abo-copy" data-url="<?php echo esc_attr( $url ); ?>">Link kopieren</button>
        <p class="tgs-abo-hint"><?php echo esc_html( $hint ); ?></p>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

/**
 * [tgs_kalender_abo] – ohne Attribut: alle Kurse.
 * Attribute: kurs="123" | sportstaette="45" | label="…"
 */
function tgs_kalender_abo_shortcode( $atts ) {
    $a = shortcode_atts( array(
        'kurs' => '', 'sportstaette' => '', 'label' => '', 'hint' => '',
    ), $atts );

    if ( $a['kurs'] ) {
        $slug  = 'kurs-' . intval( $a['kurs'] );
        $label = $a['label'] ? $a['label'] : 'Diesen Kurs abonnieren';
    } elseif ( $a['sportstaette'] ) {
        $slug  = 'sportstaette-' . intval( $a['sportstaette'] );
        $label = $a['label'] ? $a['label'] : 'Belegung abonnieren';
    } else {
        $slug  = 'kurse';
        $label = $a['label'] ? $a['label'] : 'Alle Kurse abonnieren';
    }
    return tgs_kalender_abo_html( $slug, $label, $a['hint'] );
}
add_shortcode( 'tgs_kalender_abo', 'tgs_kalender_abo_shortcode' );
