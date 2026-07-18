<?php
/**
 * Termine-Agenda — der Blick nach vorn.
 *
 * Zeigt Kurse UND Spiele (Handball; Tischtennis später) über mehrere Wochen,
 * chronologisch gruppiert nach Woche & Tag, filterbar. Leere Tage werden
 * übersprungen. „Heute" (inc/heute.php) ist der Schnappschuss von jetzt, diese
 * Seite die Vorschau.
 *
 * Baut auf den vorhandenen Bausteinen auf: tgs_kurs_termin(), das Meldungs-
 * System und den serverseitigen Handball-Feed.
 *
 * Shortcode: [tgs_termine wochen="4"]
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Kurs-Item für ein bestimmtes Datum (Ausfall/Pause dieses Datums berücksichtigt). */
function tgs_termine_kurs_item( $k, $ymd ) {
    $t     = $k['t'];
    $start = tgs_heute_min( $t['zeit'] );
    if ( $start < 0 ) return null;

    $cancelled = false; $reason = '';
    foreach ( $k['meld'] as $m ) {
        $typ = get_post_meta( $m->ID, '_tgs_meld_typ', true );
        if ( $typ === 'ausfall' && get_post_meta( $m->ID, '_tgs_meld_datum', true ) === $ymd ) {
            $cancelled = true; $reason = get_post_meta( $m->ID, '_tgs_meld_text', true );
        } elseif ( $typ === 'pause' ) {
            $von = get_post_meta( $m->ID, '_tgs_meld_von', true );
            $bis = get_post_meta( $m->ID, '_tgs_meld_bis', true );
            if ( ( ! $von || $von <= $ymd ) && $bis >= $ymd ) {
                $cancelled = true; $reason = get_post_meta( $m->ID, '_tgs_meld_text', true );
            }
        }
    }

    if ( $cancelled )      { $badge = 'Fällt aus'; $btyp = 'warn'; }
    elseif ( $k['offen'] ) { $badge = 'Offen'; $btyp = 'ok'; }
    elseif ( ! empty( $k['cap']['is_full'] ) ) { $badge = 'Warteliste'; $btyp = 'warn'; }
    else                   { $badge = 'Freie Plätze'; $btyp = 'ok'; }

    $season = '';
    if ( ! empty( $t['saisonal'] ) ) $season = $t['aktiv'] === 'winter' ? '❄️ Winter' : '☀️ Sommer';

    return array(
        'typ'       => 'kurs',
        'sort'      => $start,
        'zeit'      => $t['zeit'],
        'ende'      => isset( $t['ende'] ) ? $t['ende'] : '',
        'name'      => $k['title'],
        'url'       => $k['url'],
        'ort'       => isset( $t['ort'] ) ? $t['ort'] : '',
        'season'    => $season,
        'cancelled' => $cancelled,
        'reason'    => $reason,
        'badge'     => $badge, 'badge_typ' => $btyp,
    );
}

/**
 * Agenda-Daten aufbauen: array( 'ymd' => array( ts, items[] ) ), nur Tage mit Terminen.
 */
function tgs_termine_daten( $tage ) {
    // Kurs-Termine EINMAL berechnen, dann über die Tage streuen.
    $kurse   = get_posts( array( 'post_type' => 'tgs_kurs', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
    $ktermin = array();
    foreach ( $kurse as $id ) {
        $t = tgs_kurs_termin( $id );
        if ( empty( $t['tag'] ) || empty( $t['zeit'] ) ) continue;
        $ktermin[ $id ] = array(
            't'     => $t,
            'title' => get_the_title( $id ),
            'url'   => get_permalink( $id ),
            'offen' => function_exists( 'tgs_kurs_ist_offen' ) ? tgs_kurs_ist_offen( $id ) : false,
            'cap'   => function_exists( 'tgs_kurs_capacity' ) ? tgs_kurs_capacity( $id ) : array(),
            'meld'  => function_exists( 'tgs_kurs_aktive_meldungen' ) ? tgs_kurs_aktive_meldungen( $id ) : array(),
        );
    }

    $handball = function_exists( 'tgs_handball_spiele_alle' ) ? tgs_handball_spiele_alle() : array();

    $tz    = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'Europe/Berlin' );
    $start = new DateTime( 'now', $tz ); $start->setTime( 0, 0, 0 );
    $today = $start->format( 'Y-m-d' );

    $days = array();
    for ( $i = 0; $i < $tage; $i++ ) {
        $d   = ( clone $start )->modify( "+$i day" );
        $ymd = $d->format( 'Y-m-d' );
        $wd  = tgs_heute_wochentag_code( (int) $d->format( 'N' ) );
        $items = array();

        foreach ( $ktermin as $k ) {
            if ( $k['t']['tag'] !== $wd ) continue;
            if ( ! empty( $k['t']['pausiert'] ) ) continue; // saisonale Winterpause (Näherung: aktuelle Saison)
            $it = tgs_termine_kurs_item( $k, $ymd );
            if ( $it ) $items[] = $it;
        }

        foreach ( $handball as $s ) {
            if ( wp_date( 'Y-m-d', $s['ts'] ) === $ymd ) $items[] = tgs_handball_item( $s );
        }

        if ( $items ) {
            usort( $items, function ( $a, $b ) { return $a['sort'] - $b['sort']; } );
            $days[ $ymd ] = array( 'ts' => $d->getTimestamp(), 'items' => $items, 'today' => ( $ymd === $today ) );
        }
    }
    return $days;
}

/** Wochen-Label relativ zu heute. */
function tgs_termine_wochenlabel( $day_ts ) {
    $tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'Europe/Berlin' );
    $mon_today = new DateTime( 'now', $tz ); $mon_today->setTime( 0, 0, 0 ); $mon_today->modify( '-' . ( (int) $mon_today->format( 'N' ) - 1 ) . ' day' );
    $mon_day   = ( new DateTime( '@' . $day_ts ) )->setTimezone( $tz ); $mon_day->setTime( 0, 0, 0 ); $mon_day->modify( '-' . ( (int) $mon_day->format( 'N' ) - 1 ) . ' day' );
    $weeks = (int) round( ( $mon_day->getTimestamp() - $mon_today->getTimestamp() ) / ( 7 * 86400 ) );
    if ( $weeks <= 0 ) return 'Diese Woche';
    if ( $weeks === 1 ) return 'Nächste Woche';
    return 'Woche ab ' . wp_date( 'j. F', $mon_day->getTimestamp() );
}

/* =========================================================================
 * Rendering + Shortcode
 * ========================================================================= */

function tgs_termine_render( $wochen = 4 ) {
    $wochen = max( 1, min( 12, (int) $wochen ) );
    $days   = tgs_termine_daten( $wochen * 7 );

    // Welche Typen kommen vor? (für die Filter-Chips)
    $typen = array();
    foreach ( $days as $day ) foreach ( $day['items'] as $it ) $typen[ $it['typ'] ] = true;
    $labels = array( 'kurs' => 'Kurse', 'spiel' => 'Handball', 'tt' => 'Tischtennis' );

    ob_start();
    ?>
    <div class="tgs-agenda">
        <div class="tgs-agenda-head">
            <div>
                <h2 class="tgs-agenda-title">Termine</h2>
                <p class="tgs-agenda-sub">Kurse &amp; Spiele der nächsten <?php echo (int) $wochen; ?> Wochen</p>
            </div>
            <?php if ( function_exists( 'tgs_kalender_abo_html' ) ) : ?>
                <div class="tgs-agenda-abo"><?php echo tgs_kalender_abo_html( 'kurse', 'Kalender abonnieren', 'Alle Kurstermine automatisch in deinem Kalender – inklusive Änderungen und Ausfällen.' ); ?></div>
            <?php endif; ?>
        </div>

        <?php if ( count( $typen ) > 1 ) : ?>
        <div class="tgs-chip-row" data-termine-filter>
            <button type="button" class="tgs-chip active" data-f="alle">Alle</button>
            <?php foreach ( $labels as $key => $lbl ) : if ( empty( $typen[ $key ] ) ) continue; ?>
                <button type="button" class="tgs-chip" data-f="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl ); ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( empty( $days ) ) : ?>
            <p class="tgs-heute-empty">In den nächsten <?php echo (int) $wochen; ?> Wochen sind noch keine Termine hinterlegt.</p>
        <?php else :
            $current_week = null;
            foreach ( $days as $ymd => $day ) :
                $wk = tgs_termine_wochenlabel( $day['ts'] );
                if ( $wk !== $current_week ) { echo '<div class="tgs-agenda-week">' . esc_html( $wk ) . '</div>'; $current_week = $wk; }
                $daylabel = ( $day['today'] ? 'Heute · ' : '' ) . wp_date( 'l, j. F', $day['ts'] );
        ?>
            <div class="tgs-agenda-day">
                <p class="tgs-agenda-daylabel<?php echo $day['today'] ? ' is-today' : ''; ?>"><?php echo esc_html( $daylabel ); ?></p>
                <ul class="tgs-agenda-list">
                    <?php foreach ( $day['items'] as $it ) :
                        $cls = 'tgs-heute-item';
                        if ( ! empty( $it['cancelled'] ) ) $cls .= ' is-cancelled';
                        if ( $it['typ'] === 'spiel' ) $cls .= ' is-match';
                        $dtyp = $it['typ'] === 'spiel' ? 'handball' : $it['typ'];
                    ?>
                    <li class="<?php echo esc_attr( $cls ); ?>" data-typ="<?php echo esc_attr( $dtyp ); ?>">
                        <span class="tgs-heute-time"><?php echo esc_html( $it['zeit'] ); ?></span>
                        <span class="tgs-heute-body">
                            <span class="tgs-heute-name"><?php
                                if ( $it['typ'] === 'spiel' ) {
                                    echo $it['name_html'];
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
                                    echo esc_html( 'Fällt aus — ' . $it['reason'] );
                                } else {
                                    $meta = array();
                                    if ( $it['ort'] )    $meta[] = $it['ort'];
                                    if ( $it['ende'] )   $meta[] = 'bis ' . $it['ende'] . ' Uhr';
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
            </div>
        <?php endforeach; endif; ?>

        <?php if ( ! empty( $typen['spiel'] ) ) : ?>
            <p class="tgs-heute-quelle">Handball-Spielplan: <a href="https://www.handball.net/vereine/nuliga.hhv.16173" target="_blank" rel="noopener noreferrer">handball.net</a> (Hessischer Handball-Verband)</p>
        <?php endif; ?>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

function tgs_termine_shortcode( $atts ) {
    $a = shortcode_atts( array( 'wochen' => 4 ), $atts );
    return tgs_termine_render( $a['wochen'] );
}
add_shortcode( 'tgs_termine', 'tgs_termine_shortcode' );

/** Legt die Seite „Termine" (/termine) mit dem Shortcode an, falls fehlt. */
function tgs_ensure_termine_page() {
    if ( get_option( 'tgs_termine_page_id' ) ) return;
    $existing = get_page_by_path( 'termine' );
    if ( $existing ) { update_option( 'tgs_termine_page_id', $existing->ID ); return; }
    $id = wp_insert_post( array(
        'post_title'   => 'Termine',
        'post_name'    => 'termine',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '[tgs_termine wochen="4"]',
    ) );
    if ( $id && ! is_wp_error( $id ) ) update_option( 'tgs_termine_page_id', $id );
}
add_action( 'admin_init', 'tgs_ensure_termine_page' );
add_action( 'after_switch_theme', 'tgs_ensure_termine_page' );
