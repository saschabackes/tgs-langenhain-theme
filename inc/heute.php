<?php
/**
 * „Heute in der TGS" — das Dorf-Dashboard.
 *
 * Beantwortet die Frage, die sich jede Woche stellt: Was läuft heute – und
 * geht's überhaupt? Zieht alles aus Daten, die ohnehin gepflegt werden:
 *  - Kurse (tgs_kurs_termin() – Tag/Zeit/Ort, saisonabhängig)
 *  - Ausfälle/Pausen (tgs_meldung)
 *  - Handball-Spiele der HSG EppLa (handball.net-JSON, serverseitig geholt)
 *
 * Läuft von allein: Wochentag + Uhrzeit kommen vom Server, der Rest aus den
 * Kursdaten. Keine tägliche Handarbeit.
 *
 * Datenschutz: Der Handball-Feed wird SERVERSEITIG geholt und zwischen-
 * gespeichert – der Browser der Besucher spricht nie mit handball.net.
 *
 * Shortcode: [tgs_heute]
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Wochentags-Kürzel (1=Mo … 7=So), passend zu den Kurs-Feldern. */
function tgs_heute_wochentag_code( $n ) {
    $map = array( 1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So' );
    return isset( $map[ $n ] ) ? $map[ $n ] : '';
}

/** "HH:MM" → Minuten seit Mitternacht (für Sortierung/„jetzt"). -1 wenn ungültig. */
function tgs_heute_min( $hhmm ) {
    if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', trim( (string) $hhmm ), $m ) ) return -1;
    return (int) $m[1] * 60 + (int) $m[2];
}

/* =========================================================================
 * Kurse heute
 * ========================================================================= */

/**
 * Kurs-Einträge für einen Wochentag.
 * @param string $code    Wochentags-Kürzel (Mo…So).
 * @param bool   $heute   Wenn true: Status, Ausfälle und „jetzt" berücksichtigen.
 * @return array Liste von Items (sort, zeit, name, url, ort, ort_id, badge …).
 */
function tgs_heute_kurse_am( $code, $heute = false ) {
    if ( $code === '' ) return array();
    $kurse = get_posts( array(
        'post_type' => 'tgs_kurs', 'post_status' => 'publish',
        'numberposts' => -1, 'fields' => 'ids',
    ) );
    $today = current_time( 'Y-m-d' );
    $now   = (int) current_time( 'G' ) * 60 + (int) current_time( 'i' );
    $items = array();

    foreach ( $kurse as $id ) {
        $t = function_exists( 'tgs_kurs_termin' ) ? tgs_kurs_termin( $id ) : array();
        if ( empty( $t['tag'] ) || $t['tag'] !== $code ) continue;
        if ( ! empty( $t['pausiert'] ) ) continue; // saisonale Winterpause
        $start = tgs_heute_min( $t['zeit'] );
        if ( $start < 0 ) continue;               // ohne Uhrzeit nicht auf die Zeitleiste
        $ende  = tgs_heute_min( isset( $t['ende'] ) ? $t['ende'] : '' );

        $item = array(
            'typ'    => 'kurs',
            'sort'   => $start,
            'zeit'   => $t['zeit'],
            'ende'   => isset( $t['ende'] ) ? $t['ende'] : '',
            'name'   => get_the_title( $id ),
            'url'    => get_permalink( $id ),
            'ort'    => isset( $t['ort'] ) ? $t['ort'] : '',
            'ort_id' => isset( $t['ort_id'] ) ? (int) $t['ort_id'] : 0,
            'jetzt'  => false, 'cancelled' => false, 'reason' => '',
            'badge'  => '', 'badge_typ' => 'ok', 'season' => '',
        );

        if ( ! empty( $t['saisonal'] ) ) {
            $item['season'] = $t['aktiv'] === 'winter' ? '❄️ Winter' : '☀️ Sommer';
        }

        if ( $heute ) {
            // Ausfall/Pause heute?
            if ( function_exists( 'tgs_kurs_aktive_meldungen' ) ) {
                foreach ( tgs_kurs_aktive_meldungen( $id ) as $meld ) {
                    $mtyp = get_post_meta( $meld->ID, '_tgs_meld_typ', true );
                    if ( $mtyp === 'ausfall' && get_post_meta( $meld->ID, '_tgs_meld_datum', true ) === $today ) {
                        $item['cancelled'] = true;
                        $item['reason'] = get_post_meta( $meld->ID, '_tgs_meld_text', true );
                    } elseif ( $mtyp === 'pause' ) {
                        $von = get_post_meta( $meld->ID, '_tgs_meld_von', true );
                        $bis = get_post_meta( $meld->ID, '_tgs_meld_bis', true );
                        if ( ( ! $von || $von <= $today ) && $bis >= $today ) {
                            $item['cancelled'] = true;
                            $item['reason'] = get_post_meta( $meld->ID, '_tgs_meld_text', true );
                        }
                    }
                }
            }

            if ( $item['cancelled'] ) {
                $item['badge'] = 'Fällt aus'; $item['badge_typ'] = 'warn';
            } elseif ( function_exists( 'tgs_kurs_ist_offen' ) && tgs_kurs_ist_offen( $id ) ) {
                $item['badge'] = 'Offen'; $item['badge_typ'] = 'ok';
            } elseif ( function_exists( 'tgs_kurs_capacity' ) ) {
                $cap = tgs_kurs_capacity( $id );
                if ( ! empty( $cap['is_full'] ) ) { $item['badge'] = 'Warteliste'; $item['badge_typ'] = 'warn'; }
                else { $item['badge'] = 'Freie Plätze'; $item['badge_typ'] = 'ok'; }
            }

            // „jetzt" nur wenn nicht ausgefallen
            $bis_min = $ende >= 0 ? $ende : $start + 60;
            if ( ! $item['cancelled'] && $now >= $start && $now < $bis_min ) $item['jetzt'] = true;
        }

        $items[] = $item;
    }
    return $items;
}

/** Nächster Tag (1–7 Tage voraus) mit mindestens einem Kurs — für den Leer-Zustand. */
function tgs_heute_naechster_kurstag() {
    $heute_n = (int) current_time( 'N' );
    for ( $off = 1; $off <= 7; $off++ ) {
        $n    = ( ( $heute_n - 1 + $off ) % 7 ) + 1;
        $code = tgs_heute_wochentag_code( $n );
        $items = tgs_heute_kurse_am( $code, false );
        if ( ! empty( $items ) ) {
            usort( $items, function ( $a, $b ) { return $a['sort'] - $b['sort']; } );
            $label = $off === 1 ? 'morgen' : 'am ' . array( 1=>'Montag',2=>'Dienstag',3=>'Mittwoch',4=>'Donnerstag',5=>'Freitag',6=>'Samstag',7=>'Sonntag' )[ $n ];
            return array( 'label' => $label, 'item' => $items[0] );
        }
    }
    return null;
}

/* =========================================================================
 * Handball-Spiele (handball.net-JSON, serverseitig + gecacht)
 * ========================================================================= */

/** Vereins-ID der HSG EppLa auf handball.net (nuLiga-basiert). */
function tgs_handball_club_url() {
    return 'https://www.handball.net/a/sportdata/1/clubs/nuliga.hhv.16173/schedule';
}

/**
 * Spielplan der HSG EppLa – normalisiert und zwischengespeichert.
 * Holt serverseitig, cacht 30 Min.; bei Fehler letzter guter Stand.
 * @return array Spiele: ts, heim, gast, ort, stadt, wbh, heimspiel, state, hg, ag
 */
function tgs_handball_spiele_alle() {
    $key    = 'tgs_hb_feed';
    $cached = get_transient( $key );
    if ( is_array( $cached ) ) return $cached;

    $res  = wp_remote_get( tgs_handball_club_url(), array(
        'timeout' => 6,
        'headers' => array( 'Accept' => 'application/json' ),
        'user-agent' => 'TGS-Langenhain/1.0 (+https://tgs-langenhain.de)',
    ) );

    if ( is_wp_error( $res ) || (int) wp_remote_retrieve_response_code( $res ) !== 200 ) {
        $backup = get_option( 'tgs_hb_backup', array() );
        set_transient( $key, $backup, 15 * MINUTE_IN_SECONDS ); // kurz cachen, nicht hämmern
        return is_array( $backup ) ? $backup : array();
    }

    $data    = json_decode( wp_remote_retrieve_body( $res ), true );
    $matches = ( is_array( $data ) && isset( $data['data'] ) && is_array( $data['data'] ) ) ? $data['data'] : array();

    $clean = array();
    foreach ( $matches as $m ) {
        if ( empty( $m['startsAt'] ) ) continue;
        $heim = isset( $m['homeTeam']['name'] ) ? $m['homeTeam']['name'] : '';
        $gast = isset( $m['awayTeam']['name'] ) ? $m['awayTeam']['name'] : '';
        if ( stripos( $heim, 'EppLa' ) === false && stripos( $gast, 'EppLa' ) === false ) continue;
        $ort  = isset( $m['field']['name'] ) ? $m['field']['name'] : '';
        $clean[] = array(
            'ts'        => (int) floor( $m['startsAt'] / 1000 ),
            'heim'      => $heim,
            'gast'      => $gast,
            'ort'       => $ort,
            'stadt'     => isset( $m['field']['city'] ) ? $m['field']['city'] : '',
            'wbh'       => ( stripos( $ort, 'Wilhelm-Busch' ) !== false ),
            'heimspiel' => ( stripos( $heim, 'EppLa' ) !== false ),
            'state'     => isset( $m['state'] ) ? $m['state'] : '',
            'hg'        => isset( $m['homeGoals'] ) ? $m['homeGoals'] : null,
            'ag'        => isset( $m['awayGoals'] ) ? $m['awayGoals'] : null,
            'liga'      => isset( $m['tournament']['name'] ) ? $m['tournament']['name'] : '',
        );
    }
    usort( $clean, function ( $a, $b ) { return $a['ts'] - $b['ts']; } );

    set_transient( $key, $clean, 30 * MINUTE_IN_SECONDS );
    update_option( 'tgs_hb_backup', $clean, false );
    return $clean;
}

/** Spiele an einem bestimmten Datum (Y-m-d, Vereinszeitzone). */
function tgs_handball_spiele_am( $ymd ) {
    $out = array();
    foreach ( tgs_handball_spiele_alle() as $s ) {
        if ( wp_date( 'Y-m-d', $s['ts'] ) === $ymd ) $out[] = $s;
    }
    return $out;
}

/** Nächstes kommendes Spiel (ab jetzt), oder null. */
function tgs_handball_naechstes() {
    $now = (int) current_time( 'timestamp', true ); // UTC
    foreach ( tgs_handball_spiele_alle() as $s ) {
        if ( $s['ts'] >= $now - 2 * HOUR_IN_SECONDS ) return $s; // laufendes noch mitnehmen
    }
    return null;
}

/** Permalink der Wilhelm-Busch-Halle (Sportstätte), oder '' wenn nicht vorhanden. */
function tgs_wbh_permalink() {
    static $url = null;
    if ( $url !== null ) return $url;
    $url = '';
    $p = get_page_by_path( 'wilhelm-busch-halle', OBJECT, 'tgs_sportstaette' );
    if ( $p && get_post_status( $p->ID ) === 'publish' ) $url = get_permalink( $p->ID );
    return $url;
}

/** Spielart aus dem Turniernamen: Testspiel / Quali / Punktspiel. */
function tgs_handball_art( $liga ) {
    $l = (string) $liga;
    if ( stripos( $l, 'freundschaft' ) !== false || stripos( $l, 'vereins-event' ) !== false || stripos( $l, 'turnier' ) !== false ) return 'Testspiel';
    if ( stripos( $l, 'quali' ) !== false ) return 'Quali';
    return 'Punktspiel';
}

/**
 * URL zur HSG-EppLa-Mannschaftsseite (dynamisch, per Filter erweiterbar).
 * Gender/Alter kommt aus dem Turniernamen, die Nummer aus dem Teamnamen.
 * Standardmäßig verlinkt sind nur die bestätigt funktionierenden Seiten
 * (Herren 1, Damen). Weitere kann die HSG per Filter `tgs_handball_team_links`
 * ergänzen (Schlüssel z. B. „herren-2", „herren-3").
 */
function tgs_handball_team_url( $team_name, $liga ) {
    $women = ( stripos( $liga, 'weiblich' ) !== false || stripos( $liga, 'frauen' ) !== false || stripos( $liga, 'damen' ) !== false );
    $youth = ( stripos( $liga, 'jugend' ) !== false );
    preg_match( '/(\d+)\s*$/', (string) $team_name, $mm );
    $num = isset( $mm[1] ) ? (int) $mm[1] : 1;
    if ( $youth )      $key = '';
    elseif ( $women )  $key = 'damen' . ( $num > 1 ? '-' . $num : '' );
    else               $key = 'herren-' . $num;

    $map = apply_filters( 'tgs_handball_team_links', array(
        'herren-1' => 'https://hsg-eppla.de/list/herren-1',
        'damen'    => 'https://hsg-eppla.de/list/damen',
    ) );
    return ( $key !== '' && isset( $map[ $key ] ) ) ? $map[ $key ] : '';
}

/** Handball-Spiel in ein Zeitleisten-Item übersetzen (mit Links + Spielart). */
function tgs_handball_item( $s ) {
    $art   = tgs_handball_art( $s['liga'] );
    $eppla = $s['heimspiel'] ? $s['heim'] : $s['gast'];
    $url   = tgs_handball_team_url( $eppla, $s['liga'] );

    // Nur das EppLa-Team verlinken (auf die HSG-Seite).
    $team_html = function ( $name ) use ( $eppla, $url ) {
        if ( $name === $eppla && $url ) {
            return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $name ) . '</a>';
        }
        return esc_html( $name );
    };
    $name_html = '🤾 Handball: ' . $team_html( $s['heim'] ) . ' – ' . $team_html( $s['gast'] );

    // Ort – Wilhelm-Busch-Halle auf unsere Sportstätte verlinken.
    if ( $s['wbh'] ) {
        $wbh = tgs_wbh_permalink();
        $ort_html = $wbh ? '<a href="' . esc_url( $wbh ) . '">Wilhelm-Busch-Halle</a>' : 'Wilhelm-Busch-Halle';
        $wo = 'Heimspiel';
    } elseif ( $s['heimspiel'] ) {
        $ort_html = esc_html( trim( $s['ort'] . ( $s['stadt'] ? ', ' . $s['stadt'] : '' ) ) );
        $wo = 'Heimspiel';
    } else {
        $ort_html = esc_html( $s['ort'] ? $s['ort'] : $s['stadt'] );
        $wo = 'Auswärts';
    }
    $meta_html = implode( ' · ', array_filter( array( $ort_html, $wo, esc_html( $art ) ) ) );

    $badge = $s['heimspiel'] ? 'Heimspiel' : 'Auswärts';
    $badge_typ = $s['heimspiel'] ? 'home' : 'away';
    if ( $s['state'] === 'Post' && $s['hg'] !== null && $s['ag'] !== null ) {
        $badge = (int) $s['hg'] . ':' . (int) $s['ag']; $badge_typ = 'result';
    }

    return array(
        'typ'       => 'spiel',
        'sort'      => (int) wp_date( 'G', $s['ts'] ) * 60 + (int) wp_date( 'i', $s['ts'] ),
        'zeit'      => wp_date( 'H:i', $s['ts'] ),
        'name_html' => $name_html,
        'meta_html' => $meta_html,
        'badge'     => $badge, 'badge_typ' => $badge_typ,
    );
}

/* =========================================================================
 * Rendering + Shortcode
 * ========================================================================= */

function tgs_heute_render() {
    $code  = tgs_heute_wochentag_code( (int) current_time( 'N' ) );
    $today = current_time( 'Y-m-d' );

    // Items zusammentragen
    $items = tgs_heute_kurse_am( $code, true );
    foreach ( tgs_handball_spiele_am( $today ) as $s ) $items[] = tgs_handball_item( $s );
    usort( $items, function ( $a, $b ) { return $a['sort'] - $b['sort']; } );

    $datum_lang = wp_date( 'l, j. F', current_time( 'timestamp' ) );
    $stand      = wp_date( 'H:i', current_time( 'timestamp' ) );

    ob_start();
    ?>
    <section class="tgs-heute">
        <div class="tgs-heute-head">
            <span class="tgs-heute-eyebrow">Heute in der TGS</span>
            <span class="tgs-heute-live">Stand <b><?php echo esc_html( $stand ); ?> Uhr</b></span>
            <h2 class="tgs-heute-date"><?php echo esc_html( $datum_lang ); ?></h2>
        </div>

        <?php if ( ! empty( $items ) ) : ?>
        <ul class="tgs-heute-list">
            <?php foreach ( $items as $it ) :
                $cls = 'tgs-heute-item';
                if ( ! empty( $it['jetzt'] ) ) $cls .= ' is-now';
                if ( ! empty( $it['cancelled'] ) ) $cls .= ' is-cancelled';
                if ( $it['typ'] === 'spiel' ) $cls .= ' is-match';
            ?>
            <li class="<?php echo esc_attr( $cls ); ?>">
                <span class="tgs-heute-time"><?php echo esc_html( $it['zeit'] ); ?><?php if ( ! empty( $it['jetzt'] ) ) : ?><small>läuft</small><?php endif; ?></span>
                <span class="tgs-heute-body">
                    <span class="tgs-heute-name"><?php
                        if ( $it['typ'] === 'spiel' ) {
                            echo $it['name_html']; // enthält Icon + geprüfte Links
                        } elseif ( ! empty( $it['url'] ) ) {
                            echo '<a href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['name'] ) . '</a>';
                        } else {
                            echo esc_html( $it['name'] );
                        }
                    ?></span>
                    <span class="tgs-heute-meta"><?php
                        if ( $it['typ'] === 'spiel' ) {
                            echo $it['meta_html'];
                        } elseif ( ! empty( $it['cancelled'] ) && $it['reason'] ) {
                            echo esc_html( 'Fällt heute aus — ' . $it['reason'] );
                        } else {
                            $meta = array();
                            if ( $it['ort'] ) $meta[] = $it['ort'];
                            if ( $it['ende'] ) $meta[] = 'bis ' . $it['ende'] . ' Uhr';
                            if ( $it['season'] ) $meta[] = $it['season'];
                            echo esc_html( implode( ' · ', $meta ) );
                        }
                    ?></span>
                </span>
                <?php if ( ! empty( $it['badge'] ) ) : ?>
                <span class="tgs-heute-badge tgs-heute-badge--<?php echo esc_attr( $it['badge_typ'] ); ?>"><?php echo esc_html( $it['badge'] ); ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else : ?>
            <?php $next = tgs_heute_naechster_kurstag(); ?>
            <p class="tgs-heute-empty">Heute kein reguläres Training.<?php
                if ( $next ) echo ' Das Nächste: <strong>' . esc_html( $next['label'] . ', ' . $next['item']['name'] . ' ' . $next['item']['zeit'] . ' Uhr' ) . '</strong>.';
            ?></p>
        <?php endif; ?>

        <?php
        // Nächstes Handball-Spiel als Teaser, wenn heute keins läuft.
        $hb_heute = tgs_handball_spiele_am( $today );
        if ( empty( $hb_heute ) ) {
            $next_hb = tgs_handball_naechstes();
            if ( $next_hb ) {
                $wo  = $next_hb['wbh'] ? 'in der Wilhelm-Busch-Halle' : ( $next_hb['heimspiel'] ? 'zu Hause' : 'auswärts' );
                $art = tgs_handball_art( $next_hb['liga'] );
                echo '<p class="tgs-heute-next">🤾 <strong>Nächstes Handballspiel:</strong> '
                    . esc_html( wp_date( 'D, j. M · H:i', $next_hb['ts'] ) . ' Uhr — ' . $next_hb['heim'] . ' – ' . $next_hb['gast'] . ' (' . $wo . ', ' . $art . ')' ) . '</p>';
            }
        }
        ?>

        <a class="tgs-heute-more" href="<?php echo esc_url( home_url( '/kurse' ) ); ?>">Alle Kurse ansehen →</a>
    </section>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

function tgs_heute_shortcode() {
    return tgs_heute_render();
}
add_shortcode( 'tgs_heute', 'tgs_heute_shortcode' );

/** Legt die Seite „Heute in der TGS" (/heute) mit dem Shortcode an, falls fehlt. */
function tgs_ensure_heute_page() {
    if ( get_option( 'tgs_heute_page_id' ) ) return;
    $existing = get_page_by_path( 'heute' );
    if ( $existing ) { update_option( 'tgs_heute_page_id', $existing->ID ); return; }
    $id = wp_insert_post( array(
        'post_title'   => 'Heute in der TGS',
        'post_name'    => 'heute',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '[tgs_heute]',
    ) );
    if ( $id && ! is_wp_error( $id ) ) update_option( 'tgs_heute_page_id', $id );
}
add_action( 'admin_init', 'tgs_ensure_heute_page' );
add_action( 'after_switch_theme', 'tgs_ensure_heute_page' );
