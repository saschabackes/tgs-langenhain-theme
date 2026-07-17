<?php
/**
 * Kursanmeldung — Double-Opt-In, Warteliste mit Auto-Nachrücken,
 * Status-/Abmelde-Link (ohne Login), E-Mail-Benachrichtigungen.
 *
 * Zustände einer Anmeldung (_tgs_anm_status):
 *   unbestaetigt → bestaetigt | warteliste → storniert
 * Ein Platz wird erst mit „bestaetigt" belegt.
 *
 * Shortcodes:
 *   [tgs_anmeldung]    — Anmeldeformular auf der Kursseite
 *   [tgs_kurs_status]  — Bestätigen / Status ansehen / Abmelden (Seite „Kurs-Status")
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * CPT + Meta
 * ========================================================================= */
function tgs_register_cpt_anmeldung() {
    register_post_type( 'tgs_anmeldung', array(
        'labels' => array(
            'name'          => 'Kurs-Anmeldungen',
            'singular_name' => 'Anmeldung',
            'menu_name'     => 'Anmeldungen',
            'all_items'     => 'Alle Anmeldungen',
        ),
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-clipboard',
        'menu_position' => 8,
        'supports'      => array( 'title' ),
        'capabilities'  => array( 'create_posts' => 'do_not_allow' ), // nur systemseitig anlegen
        'map_meta_cap'  => true,
    ) );

    foreach ( array(
        '_tgs_anm_kurs_id'   => 'integer',
        '_tgs_anm_name'      => 'string',
        '_tgs_anm_email'     => 'string',
        '_tgs_anm_telefon'   => 'string',
        '_tgs_anm_nachricht' => 'string',
        '_tgs_anm_status'    => 'string',
        '_tgs_anm_token'     => 'string',
        '_tgs_anm_datum'     => 'string',
        '_tgs_anm_confirmed' => 'string',
    ) as $key => $type ) {
        register_post_meta( 'tgs_anmeldung', $key, array(
            'show_in_rest' => false, 'single' => true, 'type' => $type,
        ) );
    }
}
add_action( 'init', 'tgs_register_cpt_anmeldung' );

/** CPT „Fragen zum Kurs" (vor der Anmeldung gestellte Fragen; Basis für spätere FAQ). */
function tgs_register_cpt_frage() {
    register_post_type( 'tgs_frage', array(
        'labels' => array(
            'name'          => 'Kurs-Fragen',
            'singular_name' => 'Frage',
            'menu_name'     => 'Kurs-Fragen',
            'all_items'     => 'Alle Fragen',
        ),
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-format-chat',
        'menu_position' => 9,
        'supports'      => array( 'title' ),
        'capabilities'  => array( 'create_posts' => 'do_not_allow' ), // nur systemseitig anlegen
        'map_meta_cap'  => true,
    ) );

    foreach ( array(
        '_tgs_frage_kurs_id' => 'integer',
        '_tgs_frage_name'    => 'string',
        '_tgs_frage_email'   => 'string',
        '_tgs_frage_text'    => 'string',
        '_tgs_frage_datum'   => 'string',
        '_tgs_frage_status'  => 'string',   // 'neu' | 'beantwortet'
        '_tgs_frage_antwort' => 'string',   // spätere FAQ-Stufe
        '_tgs_frage_faq'     => 'string',   // '1' = als FAQ freigegeben (spätere Stufe)
    ) as $key => $type ) {
        register_post_meta( 'tgs_frage', $key, array(
            'show_in_rest' => false, 'single' => true, 'type' => $type,
        ) );
    }
}
add_action( 'init', 'tgs_register_cpt_frage' );

/* =========================================================================
 * Kapazität / Helfer
 * ========================================================================= */
function tgs_count_anmeldungen( $kurs_id, $status = 'bestaetigt' ) {
    return count( get_posts( array(
        'post_type'   => 'tgs_anmeldung',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields'      => 'ids',
        'meta_query'  => array( 'relation' => 'AND',
            array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_anm_status', 'value' => $status ),
        ),
    ) ) );
}

function tgs_kurs_capacity( $kurs_id ) {
    $max       = intval( get_post_meta( $kurs_id, '_tgs_max_teilnehmer', true ) );
    $confirmed = tgs_count_anmeldungen( $kurs_id, 'bestaetigt' );
    $unlimited = $max <= 0;
    return array(
        'max'       => $max,
        'confirmed' => $confirmed,
        'unlimited' => $unlimited,
        'is_full'   => ( ! $unlimited && $confirmed >= $max ),
        'free'      => $unlimited ? PHP_INT_MAX : max( 0, $max - $confirmed ),
    );
}

/** Offener Kurs ohne Anmeldung (Drop-in)? */
function tgs_kurs_ist_offen( $kurs_id ) {
    return get_post_meta( $kurs_id, '_tgs_kurs_anmeldung', true ) === 'offen';
}

/** Rückfragen an die Kursleitung erlaubt? Standard: ja (nur 'aus' schaltet ab). */
function tgs_kurs_fragen_erlaubt( $kurs_id ) {
    return get_post_meta( $kurs_id, '_tgs_kurs_fragen', true ) !== 'aus';
}

/** Kinderkurs (Anmeldung mit Kind + Elternkontakt)? */
function tgs_kurs_ist_kinderkurs( $kurs_id ) {
    return get_post_meta( $kurs_id, '_tgs_kurs_kinder', true ) === '1';
}

/** Altersgrenzen eines Kurses: array( 'min' => int|0, 'max' => int|0, 'has' => bool ). */
function tgs_kurs_altersgrenzen( $kurs_id ) {
    $min = (int) get_post_meta( $kurs_id, '_tgs_kurs_alter_min', true );
    $max = (int) get_post_meta( $kurs_id, '_tgs_kurs_alter_max', true );
    return array( 'min' => $min, 'max' => $max, 'has' => ( $min > 0 || $max > 0 ) );
}

/* ---- Saison (Sommer/Winter unterschiedliche Zeit + Ort) ---- */

/** Zeit-String, z. B. "19:30 – 21:00 Uhr" bzw. "19:30 Uhr". Leer ohne Startzeit. */
function tgs_zeit_display( $zeit, $ende = '' ) {
    if ( ! $zeit ) return '';
    return $ende ? ( $zeit . ' – ' . $ende . ' Uhr' ) : ( $zeit . ' Uhr' );
}

/** Ort als Link zur Sportstätte (falls verknüpft & veröffentlicht), sonst reiner Text. Immer HTML-escaped. */
function tgs_ort_html( $ort, $ort_id = 0 ) {
    $ort    = (string) $ort;
    $ort_id = (int) $ort_id;
    if ( $ort !== '' && $ort_id > 0 && get_post_status( $ort_id ) === 'publish' ) {
        return '<a href="' . esc_url( get_permalink( $ort_id ) ) . '">' . esc_html( $ort ) . '</a>';
    }
    return esc_html( $ort );
}

/** Kurzer Monatsname (1–12), z. B. 10 => "Okt". */
function tgs_monat_kurz( $m ) {
    $n = array( 1=>'Jan',2=>'Feb',3=>'März',4=>'Apr',5=>'Mai',6=>'Juni',7=>'Juli',8=>'Aug',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Dez' );
    return isset( $n[ (int) $m ] ) ? $n[ (int) $m ] : '';
}

/**
 * Aktuell gültiger (saisonabhängiger) Termin eines Kurses.
 * Rückgabe enthält die JETZT gültigen tag/zeit/ende/ort/ort_id plus
 * saisonal (bool), aktiv ('sommer'|'winter'|'ganzjahr'), pausiert (bool)
 * und die kompletten Sommer-/Winter-Daten.
 */
function tgs_kurs_termin( $kurs_id ) {
    $sommer = array(
        'tag'    => get_post_meta( $kurs_id, '_tgs_wochentag', true ),
        'zeit'   => get_post_meta( $kurs_id, '_tgs_uhrzeit', true ),
        'ende'   => get_post_meta( $kurs_id, '_tgs_uhrzeit_ende', true ),
        'ort'    => get_post_meta( $kurs_id, '_tgs_ort', true ),
        'ort_id' => (int) get_post_meta( $kurs_id, '_tgs_ort_id', true ),
    );

    if ( get_post_meta( $kurs_id, '_tgs_saison', true ) !== 'ja' ) {
        return array_merge( $sommer, array(
            'saisonal' => false, 'aktiv' => 'ganzjahr', 'pausiert' => false,
            'sommer' => $sommer, 'winter' => null,
        ) );
    }

    $winter = array(
        'tag'    => get_post_meta( $kurs_id, '_tgs_winter_wochentag', true ) ?: $sommer['tag'],
        'zeit'   => get_post_meta( $kurs_id, '_tgs_winter_uhrzeit', true ),
        'ende'   => get_post_meta( $kurs_id, '_tgs_winter_uhrzeit_ende', true ),
        'ort'    => get_post_meta( $kurs_id, '_tgs_winter_ort', true ),
        'ort_id' => (int) get_post_meta( $kurs_id, '_tgs_winter_ort_id', true ),
        'pause'  => ( get_post_meta( $kurs_id, '_tgs_winter_pause', true ) === 'ja' ),
    );

    $von = (int) get_post_meta( $kurs_id, '_tgs_winter_von', true ); if ( $von < 1 || $von > 12 ) $von = 10;
    $bis = (int) get_post_meta( $kurs_id, '_tgs_winter_bis', true ); if ( $bis < 1 || $bis > 12 ) $bis = 3;
    $m   = (int) current_time( 'n' );
    $ist_winter = ( $von <= $bis ) ? ( $m >= $von && $m <= $bis ) : ( $m >= $von || $m <= $bis );

    $aktiv = $ist_winter ? $winter : $sommer;
    return array_merge(
        array( 'tag' => $aktiv['tag'], 'zeit' => $aktiv['zeit'], 'ende' => $aktiv['ende'], 'ort' => $aktiv['ort'], 'ort_id' => $aktiv['ort_id'] ),
        array(
            'saisonal' => true,
            'aktiv'    => $ist_winter ? 'winter' : 'sommer',
            'pausiert' => ( $ist_winter && ! empty( $winter['pause'] ) ),
            'sommer'   => $sommer,
            'winter'   => $winter,
            'von'      => $von, 'bis' => $bis,
        )
    );
}

/** Saison-Callout (zwei Karten Sommer/Winter) für die Kursseite. Leer, wenn nicht saisonal. */
function tgs_kurs_saison_callout( $kurs_id ) {
    $t = tgs_kurs_termin( $kurs_id );
    if ( empty( $t['saisonal'] ) ) return '';

    $card = function ( $emoji, $label, $mod, $d, $active, $pause ) {
        $o  = '<div class="tgs-saison-card tgs-saison-card--' . $mod . ( $active ? ' is-active' : '' ) . '">';
        $o .= '<div class="tgs-saison-card-top">' . $emoji . ' ' . esc_html( $label );
        if ( $active ) $o .= '<span class="tgs-saison-now">läuft gerade</span>';
        $o .= '</div>';
        if ( $pause ) {
            $o .= '<div class="tgs-saison-pause">Kein Training in dieser Saison</div>';
        } else {
            $o .= '<div class="tgs-saison-kv"><span>Tag</span><b>' . esc_html( $d['tag'] ) . '</b></div>';
            $o .= '<div class="tgs-saison-kv"><span>Zeit</span><b>' . esc_html( tgs_zeit_display( $d['zeit'], $d['ende'] ) ) . '</b></div>';
            $o .= '<div class="tgs-saison-kv"><span>Ort</span><b>' . tgs_ort_html( $d['ort'], isset( $d['ort_id'] ) ? $d['ort_id'] : 0 ) . '</b></div>';
        }
        return $o . '</div>';
    };

    $win_range = tgs_monat_kurz( $t['von'] ) . '–' . tgs_monat_kurz( $t['bis'] );
    $html  = '<div class="tgs-saison"><div class="tgs-saison-hd">Wann &amp; wo wir trainieren</div><div class="tgs-saison-body">';
    $html .= $card( '☀️', 'Sommer', 'sommer', $t['sommer'], $t['aktiv'] === 'sommer', false );
    $html .= $card( '❄️', 'Winter · ' . $win_range, 'winter', $t['winter'], $t['aktiv'] === 'winter', ! empty( $t['winter']['pause'] ) );
    $html .= '</div></div>';
    return $html;
}

/** Alter in vollen Jahren aus einem Datum (Y-m-d). 0/false bei ungültig. */
function tgs_alter_aus_geburtsdatum( $ymd ) {
    $ymd = trim( (string) $ymd );
    if ( $ymd === '' ) return false;
    try {
        $geb   = new DateTime( $ymd );
        $heute = new DateTime( current_time( 'Y-m-d' ) );
    } catch ( Exception $e ) {
        return false;
    }
    if ( $geb > $heute ) return false;
    return (int) $geb->diff( $heute )->y;
}

/** Menschlicher Hinweis zur Altersgrenze, z.B. "von 6 bis 10 Jahren". */
function tgs_alter_hinweis( $min, $max ) {
    if ( $min > 0 && $max > 0 ) return sprintf( 'von %d bis %d Jahren', $min, $max );
    if ( $min > 0 )            return sprintf( 'ab %d Jahren', $min );
    if ( $max > 0 )            return sprintf( 'bis %d Jahren', $max );
    return '';
}

/** Anrede für E-Mails (bei Kinderkursen der Elternkontakt). */
function tgs_anm_greet( $anm_id ) {
    $k = get_post_meta( $anm_id, '_tgs_anm_kontakt_name', true );
    return $k !== '' ? $k : get_post_meta( $anm_id, '_tgs_anm_name', true );
}

/** Bezug für E-Mail-Texte: „die Anmeldung von Max" (Kind) bzw. „deine Anmeldung". */
function tgs_anm_bezug( $anm_id ) {
    if ( get_post_meta( $anm_id, '_tgs_anm_kind', true ) ) {
        return 'die Anmeldung von ' . get_post_meta( $anm_id, '_tgs_anm_name', true );
    }
    return 'deine Anmeldung';
}

/** Kurs-Meta _tgs_status (frei|warteliste) an die Kapazität angleichen (für die Kurstabelle). */
function tgs_sync_kurs_status( $kurs_id ) {
    $cap = tgs_kurs_capacity( $kurs_id );
    update_post_meta( $kurs_id, '_tgs_status', $cap['is_full'] ? 'warteliste' : 'frei' );
}

function tgs_get_anmeldung_by_token( $token ) {
    if ( ! $token ) return null;
    $q = get_posts( array(
        'post_type'   => 'tgs_anmeldung',
        'post_status' => 'publish',
        'numberposts' => 1,
        'meta_query'  => array( array( 'key' => '_tgs_anm_token', 'value' => $token ) ),
    ) );
    return $q ? $q[0] : null;
}

/** Position auf der Warteliste (1-basiert), 0 wenn nicht auf Warteliste. */
function tgs_waitlist_position( $anm_id ) {
    $kurs_id = intval( get_post_meta( $anm_id, '_tgs_anm_kurs_id', true ) );
    $ids = get_posts( array(
        'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => -1,
        'fields' => 'ids', 'orderby' => 'date', 'order' => 'ASC',
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_anm_status', 'value' => 'warteliste' ),
        ),
    ) );
    $pos = array_search( $anm_id, $ids, true );
    return $pos === false ? 0 : $pos + 1;
}

/* =========================================================================
 * Status-Seite (Links)
 * ========================================================================= */
/** Legt die Seite „Kurs-Status" mit [tgs_kurs_status] an, falls nicht vorhanden. */
function tgs_ensure_status_page() {
    if ( get_option( 'tgs_status_page_id' ) ) return;
    $existing = get_page_by_path( 'kurs-status' );
    if ( $existing ) { update_option( 'tgs_status_page_id', $existing->ID ); return; }
    $id = wp_insert_post( array(
        'post_title'   => 'Kurs-Status',
        'post_name'    => 'kurs-status',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '[tgs_kurs_status]',
    ) );
    if ( $id && ! is_wp_error( $id ) ) update_option( 'tgs_status_page_id', $id );
}
add_action( 'admin_init', 'tgs_ensure_status_page' );
add_action( 'after_switch_theme', 'tgs_ensure_status_page' );

function tgs_status_url( $token, $aktion = '' ) {
    $page = get_option( 'tgs_status_page_id' );
    $base = $page ? get_permalink( $page ) : home_url( '/' );
    $args = array( 'token' => $token );
    if ( $aktion ) $args['aktion'] = $aktion;
    return add_query_arg( $args, $base );
}

/* =========================================================================
 * Anmeldeformular  [tgs_anmeldung]
 * ========================================================================= */
function tgs_anmeldung_shortcode( $atts ) {
    static $rendered = false;
    if ( $rendered ) return '';
    $rendered = true;

    $atts    = shortcode_atts( array( 'kurs_id' => get_the_ID() ), $atts );
    $kurs_id = intval( $atts['kurs_id'] );
    $kurs    = get_post( $kurs_id );
    if ( ! $kurs || $kurs->post_type !== 'tgs_kurs' ) return '<p>Kurs nicht gefunden.</p>';

    $fragen_an = tgs_kurs_fragen_erlaubt( $kurs_id );

    // Offener Kurs: kein Anmeldeformular. Die frei werdende Fläche wird mit
    // dem gefüllt, was bei „komm einfach vorbei" wirklich hilft — den Termin
    // in den eigenen Kalender holen und die Anfahrt. Optional eine Rückfrage.
    if ( tgs_kurs_ist_offen( $kurs_id ) ) {
        return tgs_offener_kurs_html( $kurs_id, $fragen_an );
    }

    $message = '';
    if ( isset( $_POST['tgs_anm_nonce'] ) && wp_verify_nonce( $_POST['tgs_anm_nonce'], 'tgs_anmeldung' ) ) {
        if ( isset( $_POST['tgs_frage_submit'] ) && $fragen_an ) {
            $message = tgs_create_frage( $kurs_id );          // „Frage stellen"
        } elseif ( isset( $_POST['tgs_anm_submit'] ) ) {
            $message = tgs_create_anmeldung( $kurs_id );        // „Anmeldung absenden"
        }
    }

    $cap     = tgs_kurs_capacity( $kurs_id );
    $is_full = $cap['is_full'];
    $kinder  = tgs_kurs_ist_kinderkurs( $kurs_id );
    $alter   = tgs_kurs_altersgrenzen( $kurs_id );

    ob_start();
    ?>
    <div class="tgs-anmeldung-form" id="tgs-anmeldung">
        <?php if ( $message ) : ?><div class="tgs-anm-message"><?php echo wp_kses_post( $message ); ?></div><?php endif; ?>

        <h3 class="tgs-anm-title"><?php echo $is_full ? 'Auf die Warteliste' : 'Zum Kurs anmelden'; ?><?php if ( $fragen_an ) : ?> <span class="tgs-anm-title-sub">– oder kurz nachfragen</span><?php endif; ?></h3>

        <?php if ( $is_full ) : ?>
            <p class="tgs-anm-info tgs-anm-info--wait">Dieser Kurs ist aktuell voll. Du kannst dich auf die Warteliste setzen — wir benachrichtigen dich automatisch, sobald ein Platz frei wird.</p>
        <?php else : ?>
            <p class="tgs-anm-info">
                <?php if ( ! $cap['unlimited'] ) : ?>Noch <?php echo esc_html( $cap['free'] ); ?> von <?php echo esc_html( $cap['max'] ); ?> Plätzen frei. <?php endif; ?>
                Die Teilnahme ist über deine TGS-Mitgliedschaft abgedeckt.
            </p>
        <?php endif; ?>
        <?php if ( $alter['has'] ) : ?>
            <p class="tgs-anm-info tgs-anm-info--alter">Dieser Kurs ist für Teilnehmer <strong><?php echo esc_html( tgs_alter_hinweis( $alter['min'], $alter['max'] ) ); ?></strong>. Bitte das Geburtsdatum <?php echo $kinder ? 'des Kindes' : ''; ?> angeben.</p>
        <?php endif; ?>

        <form method="post" action="#tgs-anmeldung">
            <?php wp_nonce_field( 'tgs_anmeldung', 'tgs_anm_nonce' ); ?>
            <input type="hidden" name="tgs_anm_kurs_id" value="<?php echo esc_attr( $kurs_id ); ?>">
            <?php if ( $kinder ) : ?>
            <div class="tgs-anm-field"><label for="tgs_anm_kind">Name des Kindes *</label>
                <input type="text" id="tgs_anm_kind" name="tgs_anm_kind" required placeholder="Vor- und Nachname des Kindes"></div>
            <?php if ( $alter['has'] ) : ?>
            <div class="tgs-anm-field"><label for="tgs_anm_geburtsdatum">Geburtsdatum des Kindes *</label>
                <input type="date" id="tgs_anm_geburtsdatum" name="tgs_anm_geburtsdatum" required></div>
            <?php endif; ?>
            <p class="tgs-anm-section">Ansprechpartner (Elternteil) *</p>
            <div class="tgs-anm-field"><label for="tgs_anm_k1_name">Name *</label>
                <input type="text" id="tgs_anm_k1_name" name="tgs_anm_k1_name" required placeholder="Vor- und Nachname"></div>
            <div class="tgs-anm-field"><label for="tgs_anm_k1_email">E-Mail *</label>
                <input type="email" id="tgs_anm_k1_email" name="tgs_anm_k1_email" required placeholder="eltern@email.de"></div>
            <div class="tgs-anm-field"><label for="tgs_anm_k1_tel">Telefon (optional)</label>
                <input type="tel" id="tgs_anm_k1_tel" name="tgs_anm_k1_tel" placeholder="0173 ..."></div>
            <p class="tgs-anm-section">Zweiter Ansprechpartner (optional)</p>
            <div class="tgs-anm-field"><label for="tgs_anm_k2_name">Name</label>
                <input type="text" id="tgs_anm_k2_name" name="tgs_anm_k2_name" placeholder="Vor- und Nachname"></div>
            <div class="tgs-anm-field"><label for="tgs_anm_k2_tel">Telefon</label>
                <input type="tel" id="tgs_anm_k2_tel" name="tgs_anm_k2_tel" placeholder="0173 ..."></div>
            <div class="tgs-anm-field"><label for="tgs_anm_k2_email">E-Mail</label>
                <input type="email" id="tgs_anm_k2_email" name="tgs_anm_k2_email" placeholder="optional"></div>
            <?php else : ?>
            <div class="tgs-anm-field"><label for="tgs_anm_name">Name *</label>
                <input type="text" id="tgs_anm_name" name="tgs_anm_name" required placeholder="Vor- und Nachname"></div>
            <?php if ( $alter['has'] ) : ?>
            <div class="tgs-anm-field"><label for="tgs_anm_geburtsdatum">Geburtsdatum *</label>
                <input type="date" id="tgs_anm_geburtsdatum" name="tgs_anm_geburtsdatum" required></div>
            <?php endif; ?>
            <div class="tgs-anm-field"><label for="tgs_anm_email">E-Mail *</label>
                <input type="email" id="tgs_anm_email" name="tgs_anm_email" required placeholder="deine@email.de"></div>
            <div class="tgs-anm-field"><label for="tgs_anm_telefon">Telefon (optional)</label>
                <input type="tel" id="tgs_anm_telefon" name="tgs_anm_telefon" placeholder="0173 ..."></div>
            <?php endif; ?>
            <div class="tgs-anm-field"><label for="tgs_anm_nachricht">Nachricht<?php echo $fragen_an ? ' / Frage' : ''; ?> (optional)</label>
                <textarea id="tgs_anm_nachricht" name="tgs_anm_nachricht" rows="3" placeholder="z. B. „Kann ich einmal unverbindlich reinschnuppern?“"></textarea></div>
            <div class="tgs-anm-field"><label><input type="checkbox" name="tgs_anm_dsgvo" required> Ich stimme der <a href="<?php echo esc_url( home_url( '/datenschutz' ) ); ?>" target="_blank" rel="noopener">Datenschutzerklärung</a> zu. *</label></div>
            <?php if ( $fragen_an ) : ?>
            <div class="tgs-anm-frage-hint">💬 <strong>Nur eine Frage?</strong> Schreib sie oben ins Feld „Nachricht / Frage" und klick „Frage stellen" — anmelden musst du dich dafür nicht. Deine Frage geht direkt an die Kursleitung.</div>
            <?php endif; ?>
            <div class="tgs-anm-btns">
                <button type="submit" name="tgs_anm_submit" class="tgs-anm-submit"><?php echo $is_full ? 'Auf Warteliste setzen' : 'Anmeldung absenden'; ?></button>
                <?php if ( $fragen_an ) : ?><button type="submit" name="tgs_frage_submit" formnovalidate class="tgs-anm-frage-btn">Frage stellen</button><?php endif; ?>
            </div>
        </form>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}
add_shortcode( 'tgs_anmeldung', 'tgs_anmeldung_shortcode' );

/**
 * „Einfach vorbeikommen"-Karte für offene Kurse.
 *
 * Statt eines winzigen Hinweises (und viel leerer Fläche daneben) bündelt die
 * Karte das, was bei einem Drop-in-Kurs echten Nutzen hat: den wiederkehrenden
 * Termin in den eigenen Kalender holen (nutzt den bestehenden .ics-Feed) und
 * die Anfahrt zur Sportstätte. Optional eine Rückfrage an die Kursleitung.
 *
 * @param int  $kurs_id
 * @param bool $fragen_an  Rückfrage-Funktion anzeigen?
 */
function tgs_offener_kurs_html( $kurs_id, $fragen_an = true ) {
    $termin = function_exists( 'tgs_kurs_termin' ) ? tgs_kurs_termin( $kurs_id ) : array();
    $tag    = isset( $termin['tag'] ) ? $termin['tag'] : '';
    $zeit   = isset( $termin['zeit'] ) ? tgs_zeit_display( $termin['zeit'], isset( $termin['ende'] ) ? $termin['ende'] : '' ) : '';
    $ort    = isset( $termin['ort'] ) ? $termin['ort'] : '';
    $ort_id = isset( $termin['ort_id'] ) ? (int) $termin['ort_id'] : 0;

    // Nachfrage verarbeiten (nur wenn erlaubt)
    $message = '';
    if ( $fragen_an && isset( $_POST['tgs_anm_nonce'] ) && wp_verify_nonce( $_POST['tgs_anm_nonce'], 'tgs_anmeldung' ) && isset( $_POST['tgs_frage_submit'] ) ) {
        $message = tgs_create_frage( $kurs_id );
    }

    // Kalender-Abo nur, wenn der Kurs einen regelmäßigen Termin hat.
    $hat_serie = function_exists( 'tgs_ics_kurs_serien' ) && tgs_ics_kurs_serien( $kurs_id );

    ob_start();
    ?>
    <div class="tgs-offen" id="tgs-anmeldung">
        <div class="tgs-offen-head">
            <span class="tgs-offen-badge">Offener Kurs</span>
            <h3 class="tgs-offen-title">Einfach vorbeikommen</h3>
            <p class="tgs-offen-lead">Keine Anmeldung nötig – die Teilnahme ist über deine TGS-Mitgliedschaft abgedeckt. Komm zur Trainingszeit vorbei und mach mit.</p>
        </div>

        <?php if ( $tag || $zeit || $ort ) : ?>
        <div class="tgs-offen-wann">
            <?php if ( $tag || $zeit ) : ?><div class="tgs-offen-wann-item"><span>Wann</span><strong><?php echo esc_html( trim( $tag . ' ' . $zeit ) ); ?></strong></div><?php endif; ?>
            <?php if ( $ort ) : ?><div class="tgs-offen-wann-item"><span>Wo</span><strong><?php echo tgs_ort_html( $ort, $ort_id ); ?></strong></div><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="tgs-offen-actions">
            <?php if ( $hat_serie && function_exists( 'tgs_kalender_abo_html' ) ) : ?>
                <?php echo tgs_kalender_abo_html( 'kurs-' . $kurs_id, 'Termin in meinen Kalender', 'So verpasst du keine Woche – der Termin landet automatisch in deinem Kalender, inklusive Ausfällen.' ); ?>
            <?php endif; ?>

            <?php if ( $ort_id && get_post_status( $ort_id ) === 'publish' ) :
                $maps = get_post_meta( $ort_id, '_tgs_maps_link', true ); ?>
                <div class="tgs-offen-anfahrt">
                    <span class="tgs-offen-anfahrt-l">Anfahrt</span>
                    <a href="<?php echo esc_url( get_permalink( $ort_id ) ); ?>"><?php echo esc_html( get_the_title( $ort_id ) ); ?></a>
                    <?php if ( $maps ) : ?> · <a href="<?php echo esc_url( $maps ); ?>" target="_blank" rel="noopener">Route ↗</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ( $fragen_an ) : ?>
            <?php if ( $message ) : ?>
                <div class="tgs-anm-message"><?php echo wp_kses_post( $message ); ?></div>
            <?php else : ?>
            <details class="tgs-offen-frage">
                <summary>Noch eine Frage? Kurz an die Kursleitung schreiben</summary>
                <form method="post" action="#tgs-anmeldung">
                    <?php wp_nonce_field( 'tgs_anmeldung', 'tgs_anm_nonce' ); ?>
                    <input type="hidden" name="tgs_anm_kurs_id" value="<?php echo esc_attr( $kurs_id ); ?>">
                    <div class="tgs-anm-field"><label for="tgs_of_name">Name *</label>
                        <input type="text" id="tgs_of_name" name="tgs_anm_name" required placeholder="Vor- und Nachname"></div>
                    <div class="tgs-anm-field"><label for="tgs_of_email">E-Mail *</label>
                        <input type="email" id="tgs_of_email" name="tgs_anm_email" required placeholder="deine@email.de"></div>
                    <div class="tgs-anm-field"><label for="tgs_of_text">Deine Frage *</label>
                        <textarea id="tgs_of_text" name="tgs_anm_nachricht" rows="3" required placeholder="z. B. „Sollte ich etwas mitbringen?“"></textarea></div>
                    <div class="tgs-anm-field"><label><input type="checkbox" name="tgs_anm_dsgvo" required> Ich stimme der <a href="<?php echo esc_url( home_url( '/datenschutz' ) ); ?>" target="_blank" rel="noopener">Datenschutzerklärung</a> zu. *</label></div>
                    <button type="submit" name="tgs_frage_submit" class="tgs-anm-frage-btn">Frage stellen</button>
                </form>
            </details>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

/**
 * Legt eine unbestätigte Anmeldung an und verschickt die Bestätigungs-Mail.
 * Gibt die HTML-Statusmeldung fürs Formular zurück.
 */
function tgs_create_anmeldung( $kurs_id ) {
    $kinder = tgs_kurs_ist_kinderkurs( $kurs_id );
    $msg    = sanitize_textarea_field( $_POST['tgs_anm_nachricht'] ?? '' );
    $k1_name = $k2_name = $k2_tel = $k2_email = '';

    if ( $kinder ) {
        $name     = sanitize_text_field( $_POST['tgs_anm_kind'] ?? '' );   // Teilnehmer = Kind
        $k1_name  = sanitize_text_field( $_POST['tgs_anm_k1_name'] ?? '' ); // Elternkontakt
        $email    = sanitize_email( $_POST['tgs_anm_k1_email'] ?? '' );
        $tel      = sanitize_text_field( $_POST['tgs_anm_k1_tel'] ?? '' );
        $k2_name  = sanitize_text_field( $_POST['tgs_anm_k2_name'] ?? '' );
        $k2_tel   = sanitize_text_field( $_POST['tgs_anm_k2_tel'] ?? '' );
        $k2_email = sanitize_email( $_POST['tgs_anm_k2_email'] ?? '' );
        if ( empty( $name ) || empty( $k1_name ) || empty( $email ) || ! is_email( $email ) ) {
            return '<p class="tgs-anm-error">Bitte gib den Namen des Kindes sowie Name und eine gültige E-Mail-Adresse eines Elternteils an.</p>';
        }
    } else {
        $name  = sanitize_text_field( $_POST['tgs_anm_name'] ?? '' );
        $email = sanitize_email( $_POST['tgs_anm_email'] ?? '' );
        $tel   = sanitize_text_field( $_POST['tgs_anm_telefon'] ?? '' );
        if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
            return '<p class="tgs-anm-error">Bitte gib deinen Namen und eine gültige E-Mail-Adresse an.</p>';
        }
    }
    if ( empty( $_POST['tgs_anm_dsgvo'] ) ) {
        return '<p class="tgs-anm-error">Bitte stimme der Datenschutzerklärung zu.</p>';
    }

    // Altersgrenzen prüfen (falls für den Kurs gesetzt)
    $alter        = tgs_kurs_altersgrenzen( $kurs_id );
    $geburtsdatum = '';
    if ( $alter['has'] ) {
        $geburtsdatum = sanitize_text_field( $_POST['tgs_anm_geburtsdatum'] ?? '' );
        $jahre = tgs_alter_aus_geburtsdatum( $geburtsdatum );
        if ( $jahre === false ) {
            return '<p class="tgs-anm-error">Bitte gib ein gültiges Geburtsdatum an.</p>';
        }
        if ( ( $alter['min'] > 0 && $jahre < $alter['min'] ) || ( $alter['max'] > 0 && $jahre > $alter['max'] ) ) {
            return '<p class="tgs-anm-error">Dieser Kurs ist für ein Alter <strong>' . esc_html( tgs_alter_hinweis( $alter['min'], $alter['max'] ) ) . '</strong> vorgesehen (angegeben: ' . intval( $jahre ) . ' Jahre). Bei Fragen melde dich gern bei uns.</p>';
        }
    }

    // Vorhandene Anmeldung dieser E-Mail für diesen Kurs?
    $existing = get_posts( array(
        'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => 1,
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_anm_email', 'value' => $email ),
            array( 'key' => '_tgs_anm_name', 'value' => $name ),
            array( 'key' => '_tgs_anm_status', 'value' => array( 'unbestaetigt', 'bestaetigt', 'warteliste' ), 'compare' => 'IN' ),
        ),
    ) );
    if ( ! empty( $existing ) ) {
        $st = get_post_meta( $existing[0]->ID, '_tgs_anm_status', true );
        if ( $st === 'unbestaetigt' ) {
            tgs_mail_optin( $existing[0]->ID ); // Bestätigungslink erneut senden
            return '<p class="tgs-anm-success">Wir haben dir den Bestätigungslink erneut geschickt. Bitte schau in dein E-Mail-Postfach.</p>';
        }
        return '<p class="tgs-anm-error">Für diese E-Mail liegt für diesen Kurs bereits eine Anmeldung vor.</p>';
    }

    $token = wp_generate_password( 32, false, false );
    $anm_id = wp_insert_post( array(
        'post_type'   => 'tgs_anmeldung',
        'post_status' => 'publish',
        'post_title'  => sprintf( '%s — %s', $name, get_the_title( $kurs_id ) ),
    ) );
    if ( is_wp_error( $anm_id ) ) {
        return '<p class="tgs-anm-error">Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.</p>';
    }
    update_post_meta( $anm_id, '_tgs_anm_kurs_id', $kurs_id );
    update_post_meta( $anm_id, '_tgs_anm_name', $name );
    update_post_meta( $anm_id, '_tgs_anm_email', $email );
    update_post_meta( $anm_id, '_tgs_anm_telefon', $tel );
    update_post_meta( $anm_id, '_tgs_anm_nachricht', $msg );
    update_post_meta( $anm_id, '_tgs_anm_status', 'unbestaetigt' );
    update_post_meta( $anm_id, '_tgs_anm_token', $token );
    update_post_meta( $anm_id, '_tgs_anm_datum', current_time( 'Y-m-d H:i:s' ) );
    if ( $geburtsdatum ) update_post_meta( $anm_id, '_tgs_anm_geburtsdatum', $geburtsdatum );
    if ( $kinder ) {
        update_post_meta( $anm_id, '_tgs_anm_kind', '1' );
        update_post_meta( $anm_id, '_tgs_anm_kontakt_name', $k1_name );
        if ( $k2_name )  update_post_meta( $anm_id, '_tgs_anm_kontakt2_name', $k2_name );
        if ( $k2_tel )   update_post_meta( $anm_id, '_tgs_anm_kontakt2_tel', $k2_tel );
        if ( $k2_email ) update_post_meta( $anm_id, '_tgs_anm_kontakt2_email', $k2_email );
    }

    tgs_mail_optin( $anm_id );

    return '<p class="tgs-anm-success"><strong>Fast geschafft!</strong> Wir haben dir eine E-Mail geschickt. Bitte klicke auf den Bestätigungslink darin — erst dann ist deine Anmeldung gültig.</p>';
}

/**
 * Speichert eine Frage zum Kurs und benachrichtigt die Kursleitung (Reply-To = Fragende).
 * Keine Anmeldung, kein Platz belegt. Gibt die HTML-Statusmeldung fürs Formular zurück.
 */
function tgs_create_frage( $kurs_id ) {
    // Name/E-Mail aus dem jeweils sichtbaren Feld (Kinderkurs nutzt k1_*)
    $name  = sanitize_text_field( $_POST['tgs_anm_name'] ?? ( $_POST['tgs_anm_k1_name'] ?? '' ) );
    $email = sanitize_email( $_POST['tgs_anm_email'] ?? ( $_POST['tgs_anm_k1_email'] ?? '' ) );
    $text  = sanitize_textarea_field( $_POST['tgs_anm_nachricht'] ?? '' );

    if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
        return '<p class="tgs-anm-error">Für deine Frage brauchen wir deinen Namen und eine gültige E-Mail-Adresse (für die Antwort).</p>';
    }
    if ( $text === '' ) {
        return '<p class="tgs-anm-error">Bitte schreib deine Frage ins Feld „Nachricht / Frage".</p>';
    }
    if ( empty( $_POST['tgs_anm_dsgvo'] ) ) {
        return '<p class="tgs-anm-error">Bitte stimme der Datenschutzerklärung zu.</p>';
    }

    $frage_id = wp_insert_post( array(
        'post_type'   => 'tgs_frage',
        'post_status' => 'publish',
        'post_title'  => sprintf( 'Frage: %s — %s', $name, get_the_title( $kurs_id ) ),
    ) );
    if ( is_wp_error( $frage_id ) ) {
        return '<p class="tgs-anm-error">Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.</p>';
    }
    update_post_meta( $frage_id, '_tgs_frage_kurs_id', $kurs_id );
    update_post_meta( $frage_id, '_tgs_frage_name', $name );
    update_post_meta( $frage_id, '_tgs_frage_email', $email );
    update_post_meta( $frage_id, '_tgs_frage_text', $text );
    update_post_meta( $frage_id, '_tgs_frage_datum', current_time( 'Y-m-d H:i:s' ) );
    update_post_meta( $frage_id, '_tgs_frage_status', 'neu' );

    tgs_mail_frage_an_leitung( $frage_id );

    return '<p class="tgs-anm-success"><strong>Danke für deine Frage!</strong> Sie ist bei der Kursleitung angekommen — die Antwort kommt per E-Mail an dich.</p>';
}

/** Benachrichtigt die Kursleitung über eine neue Frage; Reply-To = Fragende (direkte Antwort möglich). */
function tgs_mail_frage_an_leitung( $frage_id ) {
    $kurs_id = (int) get_post_meta( $frage_id, '_tgs_frage_kurs_id', true );
    $kl      = sanitize_email( get_post_meta( $kurs_id, '_tgs_ansprechpartner_email', true ) );
    if ( ! $kl || ! is_email( $kl ) ) $kl = get_option( 'admin_email' );

    $name  = get_post_meta( $frage_id, '_tgs_frage_name', true );
    $email = get_post_meta( $frage_id, '_tgs_frage_email', true );
    $text  = get_post_meta( $frage_id, '_tgs_frage_text', true );
    $kurs  = get_the_title( $kurs_id );

    $body = tgs_mail_wrap(
        '<p>Zum Kurs <strong>' . esc_html( $kurs ) . '</strong> ist eine Frage eingegangen:</p>'
        . '<blockquote style="margin:0 0 16px;padding:10px 14px;border-left:3px solid #3D5A40;background:#f4f6f1;">' . nl2br( esc_html( $text ) ) . '</blockquote>'
        . '<p><strong>Von:</strong> ' . esc_html( $name ) . ' (' . esc_html( $email ) . ')</p>'
        . '<p>Du kannst direkt auf diese E-Mail <em>antworten</em> — die Antwort geht dann an ' . esc_html( $name ) . '.</p>'
    );

    // header-sicherer Name (keine Zeilenumbrüche/Sonderzeichen)
    $hname     = trim( str_replace( array( "\r", "\n", ',', '<', '>', '"' ), '', (string) $name ) );
    $headers   = tgs_mail_headers();
    $headers[] = 'Reply-To: ' . ( $hname !== '' ? '"' . $hname . '" ' : '' ) . '<' . $email . '>';

    wp_mail( $kl, 'Frage zum Kurs: ' . $kurs, $body, $headers );
}

/* =========================================================================
 * Status-Seite  [tgs_kurs_status]  (Bestätigen / Ansehen / Abmelden)
 * ========================================================================= */
function tgs_kurs_status_shortcode() {
    $notice = '';

    // Bestätigen (Double-Opt-In)
    if ( isset( $_GET['aktion'], $_GET['token'] ) && sanitize_key( $_GET['aktion'] ) === 'confirm' ) {
        $r = tgs_confirm_anmeldung( sanitize_text_field( wp_unslash( $_GET['token'] ) ) );
        $notice = $r['msg'];
    }
    // Abmelden (POST mit Anmeldungs-Token + Nonce)
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['tgs_cancel_token'], $_POST['tgs_cancel_nonce'] )
         && wp_verify_nonce( $_POST['tgs_cancel_nonce'], 'tgs_cancel' ) ) {
        $r = tgs_cancel_anmeldung( sanitize_text_field( wp_unslash( $_POST['tgs_cancel_token'] ) ) );
        $notice = $r['msg'];
    }
    // Magic-Link anfordern (POST E-Mail)
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['tgs_meine_email'], $_POST['tgs_meine_nonce'] )
         && wp_verify_nonce( $_POST['tgs_meine_nonce'], 'tgs_meine' ) ) {
        tgs_send_access_link( sanitize_email( wp_unslash( $_POST['tgs_meine_email'] ) ) );
        $notice = '<span class="tgs-status-ok">Wenn zu dieser Adresse Anmeldungen bestehen, haben wir dir gerade einen Link geschickt. Bitte schau in dein E-Mail-Postfach.</span>';
    }
    // Bewertung absenden (POST)
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['tgs_bew_nonce'] ) && function_exists( 'tgs_process_bewertung' ) ) {
        $notice = tgs_process_bewertung();
    }

    // E-Mail der Person bestimmen (aus Anmeldungs-Token, Zugangs-Token oder Abmelde-POST)
    $email = '';
    if ( isset( $_GET['token'] ) ) {
        $a = tgs_get_anmeldung_by_token( sanitize_text_field( wp_unslash( $_GET['token'] ) ) );
        if ( $a ) $email = get_post_meta( $a->ID, '_tgs_anm_email', true );
    } elseif ( isset( $_GET['zugang'] ) ) {
        $email = tgs_resolve_access_token( sanitize_text_field( wp_unslash( $_GET['zugang'] ) ) );
    } elseif ( isset( $_GET['bewerten'] ) ) {
        $a = tgs_get_anmeldung_by_token( sanitize_text_field( wp_unslash( $_GET['bewerten'] ) ) );
        if ( $a ) $email = get_post_meta( $a->ID, '_tgs_anm_email', true );
    } elseif ( isset( $_POST['tgs_bew_token'] ) ) {
        $a = tgs_get_anmeldung_by_token( sanitize_text_field( wp_unslash( $_POST['tgs_bew_token'] ) ) );
        if ( $a ) $email = get_post_meta( $a->ID, '_tgs_anm_email', true );
    }

    // Bewertungs-Formular (verifiziert über Anmeldungs-Token)
    if ( isset( $_GET['bewerten'] ) && function_exists( 'tgs_render_bewertung_form' ) ) {
        $a = tgs_get_anmeldung_by_token( sanitize_text_field( wp_unslash( $_GET['bewerten'] ) ) );
        if ( $a && get_post_meta( $a->ID, '_tgs_anm_status', true ) === 'bestaetigt' ) {
            $pre = $notice ? '<div class="tgs-status-card" style="margin-bottom:1rem;"><div class="tgs-status-notice">' . wp_kses_post( $notice ) . '</div></div>' : '';
            return $pre . tgs_render_bewertung_form( $a );
        }
    }

    ob_start();
    if ( $notice ) echo '<div class="tgs-status-card" style="margin-bottom:1rem;"><div class="tgs-status-notice">' . wp_kses_post( $notice ) . '</div></div>';
    echo $email ? tgs_render_person_overview( $email ) : tgs_meine_kurse_form();
    return ob_get_clean();
}
add_shortcode( 'tgs_kurs_status', 'tgs_kurs_status_shortcode' );

/** Status-Badge (Frontend) für eine Anmeldung. */
function tgs_status_badge_html( $status, $anm_id ) {
    if ( $status === 'bestaetigt' )   return '<span class="tgs-status-badge tgs-status-badge--ok">✓ Angemeldet</span>';
    if ( $status === 'warteliste' )   { $p = tgs_waitlist_position( $anm_id ); return '<span class="tgs-status-badge tgs-status-badge--wait">⏳ Warteliste' . ( $p ? ' · Platz ' . intval( $p ) : '' ) . '</span>'; }
    if ( $status === 'unbestaetigt' ) return '<span class="tgs-status-badge tgs-status-badge--wait">✉ Nicht bestätigt</span>';
    if ( $status === 'storniert' )    return '<span class="tgs-status-badge tgs-status-badge--off">Abgemeldet</span>';
    return '';
}

/** Übersicht aller aktiven Anmeldungen einer E-Mail. */
function tgs_render_person_overview( $email ) {
    $anms = get_posts( array(
        'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => -1,
        'orderby' => 'date', 'order' => 'ASC',
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_anm_email', 'value' => $email ),
            array( 'key' => '_tgs_anm_status', 'value' => array( 'unbestaetigt', 'bestaetigt', 'warteliste' ), 'compare' => 'IN' ),
        ),
    ) );
    $page     = get_option( 'tgs_status_page_id' );
    $page_url = $page ? get_permalink( $page ) : home_url( '/' );
    ob_start();
    echo '<div class="tgs-status-card">';
    echo '<h2 class="tgs-status-h">Meine Kurse</h2>';
    echo '<p class="tgs-status-name">' . esc_html( $email ) . '</p>';
    if ( empty( $anms ) ) {
        echo '<p>Für diese Adresse sind aktuell keine aktiven Anmeldungen hinterlegt.</p>';
    } else {
        echo '<div class="tgs-mk-list">';
        foreach ( $anms as $a ) {
            $status  = get_post_meta( $a->ID, '_tgs_anm_status', true );
            $kurs_id = intval( get_post_meta( $a->ID, '_tgs_anm_kurs_id', true ) );
            $tok     = get_post_meta( $a->ID, '_tgs_anm_token', true );
            echo '<div class="tgs-mk-item">';
            echo '<div class="tgs-mk-head"><a class="tgs-mk-kurs" href="' . esc_url( get_permalink( $kurs_id ) ) . '">' . esc_html( get_the_title( $kurs_id ) ) . '</a> ' . tgs_status_badge_html( $status, $a->ID ) . '</div>';
            $info = tgs_kurs_infozeile( $kurs_id );
            if ( $info ) echo '<div class="tgs-mk-meta">' . $info . '</div>';
            echo '<form method="post" class="tgs-mk-cancel" onsubmit="return confirm(\'Von diesem Kurs abmelden?\');">';
            wp_nonce_field( 'tgs_cancel', 'tgs_cancel_nonce' );
            echo '<input type="hidden" name="tgs_cancel_token" value="' . esc_attr( $tok ) . '">';
            echo '<button type="submit" class="tgs-mk-cancel-btn">Abmelden</button></form>';
            if ( $status === 'bestaetigt' && function_exists( 'tgs_bewertung_aktiv' ) && tgs_bewertung_aktiv( $kurs_id ) ) {
                $rated = function_exists( 'tgs_user_bewertung' ) && tgs_user_bewertung( $a->ID );
                echo '<a class="tgs-mk-bew" href="' . esc_url( add_query_arg( 'bewerten', $tok, $page_url ) ) . '">★ ' . ( $rated ? 'Bewertung ansehen' : 'Kurs bewerten' ) . '</a>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
    return ob_get_clean();
}

/** Zugangs-Formular „Meine Kurse" (E-Mail → Magic-Link). */
function tgs_meine_kurse_form() {
    ob_start();
    echo '<div class="tgs-status-card">';
    echo '<h2 class="tgs-status-h">Meine Kurse</h2>';
    echo '<p>Gib deine E-Mail-Adresse ein — wir schicken dir einen Link zu deiner persönlichen Kursübersicht (Anmeldungen &amp; Warteliste). Ganz ohne Konto.</p>';
    echo '<form method="post" class="tgs-meine-form">';
    wp_nonce_field( 'tgs_meine', 'tgs_meine_nonce' );
    echo '<div class="tgs-anm-field"><label for="tgs_meine_email">E-Mail</label><input type="email" id="tgs_meine_email" name="tgs_meine_email" required placeholder="deine@email.de"></div>';
    echo '<button type="submit" class="tgs-anm-submit">Link anfordern</button>';
    echo '</form></div>';
    return ob_get_clean();
}

/** Verschickt den Zugangs-Link zur Kursübersicht (nur wenn Anmeldungen existieren). */
function tgs_send_access_link( $email ) {
    if ( ! is_email( $email ) ) return;
    $has = get_posts( array( 'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids',
        'meta_query' => array( array( 'key' => '_tgs_anm_email', 'value' => $email ) ) ) );
    if ( empty( $has ) ) return;

    $token = wp_generate_password( 40, false, false );
    set_transient( 'tgs_zugang_' . $token, $email, 7 * DAY_IN_SECONDS );
    $page = get_option( 'tgs_status_page_id' );
    $link = add_query_arg( 'zugang', $token, $page ? get_permalink( $page ) : home_url( '/' ) );

    $body = tgs_mail_wrap(
        '<h2>Deine Kursübersicht</h2>'
        . '<p>hier kommst du zu deinen Anmeldungen und Wartelisten bei der TGS Langenhain:</p>'
        . '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#3D5A40;color:#fff;font-weight:bold;text-decoration:none;padding:10px 20px;border-radius:8px;">Meine Kurse ansehen</a></p>'
        . '<p style="color:#8a8577;font-size:13px;">Der Link ist 7 Tage gültig.</p>'
    );
    wp_mail( $email, 'Deine Kursübersicht — TGS Langenhain', $body, tgs_mail_headers() );
}

function tgs_resolve_access_token( $token ) {
    if ( ! $token ) return '';
    $email = get_transient( 'tgs_zugang_' . $token );
    return $email ? $email : '';
}

/* =========================================================================
 * Aktionen: Bestätigen / Abmelden / Nachrücken
 * ========================================================================= */
function tgs_confirm_anmeldung( $token ) {
    $anm = tgs_get_anmeldung_by_token( $token );
    if ( ! $anm ) return array( 'ok' => false, 'msg' => '<span class="tgs-status-err">Bestätigungslink ungültig.</span>' );

    $status  = get_post_meta( $anm->ID, '_tgs_anm_status', true );
    $kurs_id = intval( get_post_meta( $anm->ID, '_tgs_anm_kurs_id', true ) );

    if ( in_array( $status, array( 'bestaetigt', 'warteliste' ), true ) ) {
        return array( 'ok' => true, 'msg' => '<span class="tgs-status-ok">Deine Anmeldung ist bereits bestätigt.</span>' );
    }
    if ( $status !== 'unbestaetigt' ) {
        return array( 'ok' => false, 'msg' => '<span class="tgs-status-err">Diese Anmeldung ist nicht mehr aktiv.</span>' );
    }

    $cap = tgs_kurs_capacity( $kurs_id );
    $new = $cap['is_full'] ? 'warteliste' : 'bestaetigt';
    update_post_meta( $anm->ID, '_tgs_anm_status', $new );
    update_post_meta( $anm->ID, '_tgs_anm_confirmed', current_time( 'Y-m-d H:i:s' ) );
    tgs_sync_kurs_status( $kurs_id );
    tgs_mail_confirmed( $anm->ID, $new );
    tgs_notify_leader( $anm->ID, $new );

    $txt = $new === 'bestaetigt'
        ? '<span class="tgs-status-ok">Anmeldung bestätigt — du hast einen Platz!</span>'
        : '<span class="tgs-status-ok">Anmeldung bestätigt — du bist auf der Warteliste.</span>';
    return array( 'ok' => true, 'msg' => $txt );
}

function tgs_cancel_anmeldung( $token ) {
    $anm = tgs_get_anmeldung_by_token( $token );
    if ( ! $anm ) return array( 'ok' => false, 'msg' => '<span class="tgs-status-err">Link ungültig.</span>' );

    $prev    = get_post_meta( $anm->ID, '_tgs_anm_status', true );
    $kurs_id = intval( get_post_meta( $anm->ID, '_tgs_anm_kurs_id', true ) );
    if ( $prev === 'storniert' ) return array( 'ok' => true, 'msg' => '' );

    update_post_meta( $anm->ID, '_tgs_anm_status', 'storniert' );
    tgs_sync_kurs_status( $kurs_id );
    if ( $prev === 'bestaetigt' ) tgs_promote_waitlist( $kurs_id );

    return array( 'ok' => true, 'msg' => '<span class="tgs-status-ok">Du bist abgemeldet.</span>' );
}

/** Rückt Wartelisten-Personen nach, solange Plätze frei sind. */
function tgs_promote_waitlist( $kurs_id ) {
    $guard = 0;
    while ( $guard++ < 100 ) {
        $cap = tgs_kurs_capacity( $kurs_id );
        if ( $cap['unlimited'] || $cap['is_full'] ) break;
        $next = get_posts( array(
            'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => 1,
            'orderby' => 'date', 'order' => 'ASC',
            'meta_query' => array( 'relation' => 'AND',
                array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
                array( 'key' => '_tgs_anm_status', 'value' => 'warteliste' ),
            ),
        ) );
        if ( empty( $next ) ) break;
        update_post_meta( $next[0]->ID, '_tgs_anm_status', 'bestaetigt' );
        tgs_mail_promoted( $next[0]->ID );
        tgs_notify_leader( $next[0]->ID, 'nachgerueckt' );
    }
    tgs_sync_kurs_status( $kurs_id );
}

/* =========================================================================
 * E-Mails
 * ========================================================================= */
function tgs_mail_headers() {
    return array( 'Content-Type: text/html; charset=UTF-8' );
}
function tgs_kurs_infozeile( $kurs_id ) {
    $tag  = get_post_meta( $kurs_id, '_tgs_wochentag', true );
    $zeit = get_post_meta( $kurs_id, '_tgs_uhrzeit', true );
    $ort  = get_post_meta( $kurs_id, '_tgs_ort', true );
    $parts = array_filter( array( $tag, $zeit ? $zeit . ' Uhr' : '', $ort ) );
    return esc_html( implode( ' · ', $parts ) );
}
function tgs_mail_wrap( $inner ) {
    return '<div style="font-family:Arial,sans-serif;font-size:15px;color:#1A2A1E;line-height:1.6;">' . $inner
        . '<p style="color:#8a8577;font-size:13px;margin-top:24px;">Sportliche Grüße<br>TGS 1886 Langenhain e.V.</p></div>';
}

function tgs_mail_optin( $anm_id ) {
    $email   = get_post_meta( $anm_id, '_tgs_anm_email', true );
    $name    = get_post_meta( $anm_id, '_tgs_anm_name', true );
    $token   = get_post_meta( $anm_id, '_tgs_anm_token', true );
    $kurs_id = intval( get_post_meta( $anm_id, '_tgs_anm_kurs_id', true ) );
    $kurs    = get_the_title( $kurs_id );
    $link    = tgs_status_url( $token, 'confirm' );

    $body = tgs_mail_wrap(
        '<h2>Hallo ' . esc_html( tgs_anm_greet( $anm_id ) ) . ',</h2>'
        . '<p>fast geschafft! Bitte bestätige ' . esc_html( tgs_anm_bezug( $anm_id ) ) . ' für <strong>' . esc_html( $kurs ) . '</strong> (' . tgs_kurs_infozeile( $kurs_id ) . ').</p>'
        . '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#3D5A40;color:#fff;font-weight:bold;text-decoration:none;padding:12px 24px;border-radius:8px;">Anmeldung bestätigen</a></p>'
        . '<p style="color:#8a8577;font-size:13px;">Falls du dich nicht angemeldet hast, ignoriere diese E-Mail einfach.</p>'
    );
    wp_mail( $email, 'Bitte bestätige deine Anmeldung: ' . $kurs, $body, tgs_mail_headers() );
}

function tgs_mail_confirmed( $anm_id, $status ) {
    $email   = get_post_meta( $anm_id, '_tgs_anm_email', true );
    $name    = get_post_meta( $anm_id, '_tgs_anm_name', true );
    $token   = get_post_meta( $anm_id, '_tgs_anm_token', true );
    $kurs_id = intval( get_post_meta( $anm_id, '_tgs_anm_kurs_id', true ) );
    $kurs    = get_the_title( $kurs_id );
    $link    = tgs_status_url( $token );

    $bezug = tgs_anm_bezug( $anm_id );
    if ( $status === 'bestaetigt' ) {
        $subject = 'Anmeldung bestätigt: ' . $kurs;
        $intro   = '<p>' . esc_html( ucfirst( $bezug ) ) . ' für <strong>' . esc_html( $kurs ) . '</strong> (' . tgs_kurs_infozeile( $kurs_id ) . ') ist bestätigt — der Platz ist reserviert!</p>';
    } else {
        $subject = 'Warteliste: ' . $kurs;
        $intro   = '<p>der Kurs <strong>' . esc_html( $kurs ) . '</strong> ist aktuell voll — ' . esc_html( $bezug ) . ' steht jetzt auf der <strong>Warteliste</strong>. Sobald ein Platz frei wird, rücken wir automatisch nach und melden uns.</p>';
    }
    $body = tgs_mail_wrap(
        '<h2>Hallo ' . esc_html( tgs_anm_greet( $anm_id ) ) . ',</h2>' . $intro
        . '<p>Status ansehen oder abmelden:</p>'
        . '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#3D5A40;color:#fff;font-weight:bold;text-decoration:none;padding:10px 20px;border-radius:8px;">Meine Anmeldung</a></p>'
    );
    wp_mail( $email, $subject, $body, tgs_mail_headers() );
}

function tgs_mail_promoted( $anm_id ) {
    if ( get_post_meta( $anm_id, '_tgs_anm_manuell', true ) ) return; // stille, importierte Teilnehmer
    $email   = get_post_meta( $anm_id, '_tgs_anm_email', true );
    $name    = get_post_meta( $anm_id, '_tgs_anm_name', true );
    $token   = get_post_meta( $anm_id, '_tgs_anm_token', true );
    $kurs_id = intval( get_post_meta( $anm_id, '_tgs_anm_kurs_id', true ) );
    $kurs    = get_the_title( $kurs_id );
    $link    = tgs_status_url( $token );

    $body = tgs_mail_wrap(
        '<h2>Gute Nachricht, ' . esc_html( tgs_anm_greet( $anm_id ) ) . '!</h2>'
        . '<p>Ein Platz in <strong>' . esc_html( $kurs ) . '</strong> (' . tgs_kurs_infozeile( $kurs_id ) . ') ist frei geworden — ' . esc_html( tgs_anm_bezug( $anm_id ) ) . ' ist jetzt <strong>angemeldet</strong>.</p>'
        . '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#3D5A40;color:#fff;font-weight:bold;text-decoration:none;padding:10px 20px;border-radius:8px;">Meine Anmeldung</a></p>'
    );
    wp_mail( $email, 'Ein Platz ist frei — du bist dabei: ' . $kurs, $body, tgs_mail_headers() );
}

/** Benachrichtigt den Kursleiter über eine neue/aktualisierte Anmeldung. */
function tgs_notify_leader( $anm_id, $art ) {
    $kurs_id  = intval( get_post_meta( $anm_id, '_tgs_anm_kurs_id', true ) );
    $kl_email = get_post_meta( $kurs_id, '_tgs_ansprechpartner_email', true );
    if ( ! $kl_email || ! is_email( $kl_email ) ) return;

    $name  = get_post_meta( $anm_id, '_tgs_anm_name', true );
    $email = get_post_meta( $anm_id, '_tgs_anm_email', true );
    $tel   = get_post_meta( $anm_id, '_tgs_anm_telefon', true );
    $kurs  = get_the_title( $kurs_id );
    $labels = array( 'bestaetigt' => 'Neue Anmeldung', 'warteliste' => 'Neu auf Warteliste', 'nachgerueckt' => 'Nachgerückt (jetzt angemeldet)' );
    $label  = $labels[ $art ] ?? 'Anmeldung';

    $body = tgs_mail_wrap(
        '<h2>' . esc_html( $label ) . ': ' . esc_html( $kurs ) . '</h2>'
        . '<table style="border-collapse:collapse;font-size:14px;">'
        . '<tr><td style="padding:3px 14px 3px 0;color:#8a8577;">Name</td><td>' . esc_html( $name ) . '</td></tr>'
        . '<tr><td style="padding:3px 14px 3px 0;color:#8a8577;">E-Mail</td><td>' . esc_html( $email ) . '</td></tr>'
        . ( $tel ? '<tr><td style="padding:3px 14px 3px 0;color:#8a8577;">Telefon</td><td>' . esc_html( $tel ) . '</td></tr>' : '' )
        . '</table>'
    );
    wp_mail( $kl_email, $label . ': ' . $name . ' — ' . $kurs, $body, tgs_mail_headers() );
}

/* =========================================================================
 * Backend: Anmeldungs-Übersicht direkt am Kurs
 * ========================================================================= */
function tgs_add_kurs_anmeldungen_metabox() {
    add_meta_box( 'tgs_kurs_anmeldungen', 'Anmeldungen zu diesem Kurs', 'tgs_kurs_anmeldungen_metabox_html', 'tgs_kurs', 'normal', 'default' );
    add_meta_box( 'tgs_kurs_fragen', 'Fragen zu diesem Kurs', 'tgs_kurs_fragen_metabox_html', 'tgs_kurs', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'tgs_add_kurs_anmeldungen_metabox' );

/** Backend-Box: Fragen zu diesem Kurs (MVP: anzeigen; Antwort/FAQ folgt später). */
function tgs_kurs_fragen_metabox_html( $post ) {
    $fragen = get_posts( array(
        'post_type'   => 'tgs_frage',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'meta_query'  => array( array( 'key' => '_tgs_frage_kurs_id', 'value' => $post->ID ) ),
    ) );

    if ( ! $fragen ) {
        echo '<p style="color:#666;">Noch keine Fragen zu diesem Kurs.</p>';
        return;
    }

    echo '<table class="widefat striped"><thead><tr><th>Datum</th><th>Von</th><th>Frage</th></tr></thead><tbody>';
    foreach ( $fragen as $f ) {
        $name  = get_post_meta( $f->ID, '_tgs_frage_name', true );
        $email = get_post_meta( $f->ID, '_tgs_frage_email', true );
        $text  = get_post_meta( $f->ID, '_tgs_frage_text', true );
        $datum = get_post_meta( $f->ID, '_tgs_frage_datum', true );
        printf(
            '<tr><td style="white-space:nowrap;">%s</td><td>%s<br><a href="mailto:%s">%s</a></td><td>%s</td></tr>',
            esc_html( mysql2date( 'd.m.Y H:i', $datum ) ),
            esc_html( $name ),
            esc_attr( $email ),
            esc_html( $email ),
            nl2br( esc_html( $text ) )
        );
    }
    echo '</tbody></table>';
    echo '<p class="description" style="margin-top:8px;">Die Kursleitung wurde per E-Mail benachrichtigt (mit Antwort-Adresse der fragenden Person). Antworten/FAQ-Freigabe folgt in einer späteren Ausbaustufe.</p>';
}

function tgs_kurs_anm_list( $kurs_id, $status ) {
    return get_posts( array(
        'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => -1,
        'orderby' => 'date', 'order' => 'ASC',
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_anm_status', 'value' => $status ),
        ),
    ) );
}

/** Aktions-Button (Backend) für eine Anmeldung. */
function tgs_anm_action_link( $anm_id, $op, $label, $confirm = '' ) {
    $url = wp_nonce_url( admin_url( 'admin-post.php?action=tgs_anm_manage&op=' . $op . '&anm=' . $anm_id ), 'tgs_anm_manage_' . $anm_id );
    $onclick = $confirm ? ' onclick="return confirm(\'' . esc_js( $confirm ) . '\');"' : '';
    return '<a href="' . esc_url( $url ) . '" class="button button-small"' . $onclick . '>' . esc_html( $label ) . '</a>';
}

function tgs_render_anm_table( $title, $list, $numbered, $ops = array() ) {
    if ( empty( $list ) ) return;
    echo '<h4 style="margin:1.2em 0 .3em;">' . esc_html( $title ) . ' (' . count( $list ) . ')</h4>';
    echo '<table class="widefat striped" style="margin-bottom:.5em;"><thead><tr>';
    if ( $numbered ) echo '<th style="width:34px;">#</th>';
    echo '<th>Name</th><th>E-Mail</th><th>Telefon</th><th>Datum</th>';
    if ( $ops ) echo '<th>Aktion</th>';
    echo '</tr></thead><tbody>';
    $i = 0;
    foreach ( $list as $a ) {
        $i++;
        $email = get_post_meta( $a->ID, '_tgs_anm_email', true );
        $name  = get_post_meta( $a->ID, '_tgs_anm_name', true );
        $namecell = '<strong>' . esc_html( $name ) . '</strong>';
        if ( get_post_meta( $a->ID, '_tgs_anm_kind', true ) ) {
            $sub = 'Kind · Kontakt: ' . esc_html( get_post_meta( $a->ID, '_tgs_anm_kontakt_name', true ) );
            $k2  = get_post_meta( $a->ID, '_tgs_anm_kontakt2_name', true );
            if ( $k2 ) {
                $k2t = get_post_meta( $a->ID, '_tgs_anm_kontakt2_tel', true );
                $sub .= ' · ' . esc_html( $k2 ) . ( $k2t ? ' (' . esc_html( $k2t ) . ')' : '' );
            }
            $namecell .= '<br><small style="color:#888;">' . $sub . '</small>';
        }
        $geb = get_post_meta( $a->ID, '_tgs_anm_geburtsdatum', true );
        if ( $geb ) {
            $j = tgs_alter_aus_geburtsdatum( $geb );
            $namecell .= '<br><small style="color:#888;">geb. ' . esc_html( date_i18n( 'd.m.Y', strtotime( $geb ) ) ) . ( $j !== false ? ' · ' . intval( $j ) . ' J.' : '' ) . '</small>';
        }
        echo '<tr>';
        if ( $numbered ) echo '<td>' . $i . '</td>';
        echo '<td>' . $namecell . '</td>';
        echo '<td><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></td>';
        echo '<td>' . esc_html( get_post_meta( $a->ID, '_tgs_anm_telefon', true ) ) . '</td>';
        echo '<td>' . esc_html( get_post_meta( $a->ID, '_tgs_anm_datum', true ) ) . '</td>';
        if ( $ops ) {
            echo '<td style="white-space:nowrap;">';
            if ( in_array( 'promote', $ops, true ) ) echo tgs_anm_action_link( $a->ID, 'promote', 'In Kurs aufnehmen' ) . ' ';
            if ( in_array( 'remove', $ops, true ) )  echo tgs_anm_action_link( $a->ID, 'remove', 'Entfernen', 'Diese Person wirklich entfernen? Bei einem belegten Platz rückt automatisch die Warteliste nach.' );
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/** Backend-Aktion: Teilnehmer entfernen (+ Nachrücken) oder von Warteliste aufnehmen. */
function tgs_handle_anm_manage() {
    $anm_id = isset( $_GET['anm'] ) ? intval( $_GET['anm'] ) : 0;
    $op     = isset( $_GET['op'] ) ? sanitize_key( $_GET['op'] ) : '';
    if ( ! $anm_id || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'tgs_anm_manage_' . $anm_id ) ) {
        wp_die( 'Ungültige oder abgelaufene Anfrage.' );
    }
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Keine Berechtigung.' );

    $kurs_id = intval( get_post_meta( $anm_id, '_tgs_anm_kurs_id', true ) );
    if ( $op === 'remove' ) {
        $prev = get_post_meta( $anm_id, '_tgs_anm_status', true );
        update_post_meta( $anm_id, '_tgs_anm_status', 'storniert' );
        tgs_sync_kurs_status( $kurs_id );
        if ( in_array( $prev, array( 'bestaetigt', 'warteliste' ), true ) ) tgs_mail_removed( $anm_id );
        if ( $prev === 'bestaetigt' ) tgs_promote_waitlist( $kurs_id );
    } elseif ( $op === 'promote' ) {
        update_post_meta( $anm_id, '_tgs_anm_status', 'bestaetigt' );
        tgs_sync_kurs_status( $kurs_id );
        tgs_mail_promoted( $anm_id );
    }
    wp_safe_redirect( admin_url( 'post.php?post=' . $kurs_id . '&action=edit' ) );
    exit;
}
add_action( 'admin_post_tgs_anm_manage', 'tgs_handle_anm_manage' );

/** Info-Mail an eine vom Kursleiter entfernte Person. */
function tgs_mail_removed( $anm_id ) {
    if ( get_post_meta( $anm_id, '_tgs_anm_manuell', true ) ) return; // stille, importierte Teilnehmer
    $email = get_post_meta( $anm_id, '_tgs_anm_email', true );
    if ( ! $email || ! is_email( $email ) ) return;
    $name    = get_post_meta( $anm_id, '_tgs_anm_name', true );
    $kurs_id = intval( get_post_meta( $anm_id, '_tgs_anm_kurs_id', true ) );
    $kurs    = get_the_title( $kurs_id );
    $body = tgs_mail_wrap(
        '<h2>Hallo ' . esc_html( $name ) . ',</h2>'
        . '<p>deine Anmeldung für <strong>' . esc_html( $kurs ) . '</strong> wurde storniert. Falls das ein Versehen war, wende dich bitte an den Kursleiter oder melde dich auf der Kursseite neu an.</p>'
    );
    wp_mail( $email, 'Abmeldung: ' . $kurs, $body, tgs_mail_headers() );
}

/* -------------------------------------------------------------------------
 * Stiller Import bestehender Teilnehmer (laufende Kurse) — OHNE E-Mails
 * ----------------------------------------------------------------------- */
function tgs_register_teilnehmer_add_page() {
    add_submenu_page( null, 'Teilnehmer hinzufügen', '', 'edit_posts', 'tgs-teilnehmer-add', 'tgs_teilnehmer_add_page' );
}
add_action( 'admin_menu', 'tgs_register_teilnehmer_add_page' );

function tgs_teilnehmer_add_page() {
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Keine Berechtigung.' );
    $kurs_id = isset( $_GET['kurs'] ) ? intval( $_GET['kurs'] ) : 0;
    $kurs    = $kurs_id ? get_post( $kurs_id ) : null;
    if ( ! $kurs || $kurs->post_type !== 'tgs_kurs' ) wp_die( 'Kurs nicht gefunden.' );
    ?>
    <div class="wrap">
        <h1>Teilnehmer hinzufügen — <?php echo esc_html( get_the_title( $kurs_id ) ); ?></h1>
        <p style="max-width:640px;">Für bereits laufende Kurse: trage hier bestehende Teilnehmer ein. <strong>Es werden keine E-Mails verschickt</strong> — niemand bekommt eine Bestätigungs- oder Benachrichtigungsmail. (Online-Neuanmeldungen über die Website laufen weiterhin mit Bestätigungslink.)</p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="tgs_anm_add">
            <input type="hidden" name="kurs_id" value="<?php echo esc_attr( $kurs_id ); ?>">
            <?php wp_nonce_field( 'tgs_anm_add_' . $kurs_id, 'tgs_add_nonce' ); ?>
            <table class="form-table"><tbody>
                <tr><th><label for="tgs_add_people">Teilnehmer</label></th>
                    <td><textarea id="tgs_add_people" name="tgs_add_people" rows="10" class="large-text" placeholder="Je Zeile eine Person:&#10;Petra Muster, petra@example.de&#10;Max Beispiel&#10;Anna Schmidt, anna@example.de"></textarea>
                    <p class="description">Eine Person pro Zeile: <code>Vor- und Nachname, e-mail@optional.de</code>. Die E-Mail ist optional — ohne E-Mail kann die Person ihre Kursübersicht später nicht selbst per „Meine Kurse" abrufen, du verwaltest sie dann im Backend.</p></td></tr>
                <tr><th>Status</th><td>
                    <label><input type="radio" name="tgs_add_status" value="bestaetigt" checked> Als angemeldet</label> &nbsp;&nbsp;
                    <label><input type="radio" name="tgs_add_status" value="warteliste"> Auf Warteliste</label></td></tr>
            </tbody></table>
            <p><button type="submit" class="button button-primary">Hinzufügen (ohne E-Mail)</button>
               <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $kurs_id . '&action=edit' ) ); ?>" class="button">Abbrechen</a></p>
        </form>
    </div>
    <?php
}

function tgs_handle_anm_add() {
    $kurs_id = isset( $_POST['kurs_id'] ) ? intval( $_POST['kurs_id'] ) : 0;
    if ( ! $kurs_id || empty( $_POST['tgs_add_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_add_nonce'], 'tgs_anm_add_' . $kurs_id ) ) {
        wp_die( 'Ungültige oder abgelaufene Anfrage.' );
    }
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Keine Berechtigung.' );

    $status = ( isset( $_POST['tgs_add_status'] ) && $_POST['tgs_add_status'] === 'warteliste' ) ? 'warteliste' : 'bestaetigt';
    $lines  = preg_split( '/\r\n|\r|\n/', isset( $_POST['tgs_add_people'] ) ? wp_unslash( $_POST['tgs_add_people'] ) : '' );
    $added  = 0;

    foreach ( (array) $lines as $line ) {
        $line = trim( $line );
        if ( $line === '' ) continue;
        $email = ''; $name = $line;
        if ( preg_match( '/^(.*?)[;,]\s*([^\s;,<>]+@[^\s;,<>]+)\s*$/u', $line, $m ) ) {
            $name  = trim( $m[1] );
            $email = sanitize_email( $m[2] );
        } elseif ( preg_match( '/([^\s;,<>]+@[^\s;,<>]+)/', $line, $m ) ) {
            $email = sanitize_email( $m[1] );
            $name  = trim( str_replace( array( $m[1], '<', '>' ), '', $line ), " ,;\t" );
        }
        $name = sanitize_text_field( $name );
        if ( $name === '' && $email === '' ) continue;

        if ( $email ) {
            $dup = get_posts( array( 'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids',
                'meta_query' => array( 'relation' => 'AND',
                    array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
                    array( 'key' => '_tgs_anm_email', 'value' => $email ),
                    array( 'key' => '_tgs_anm_status', 'value' => array( 'unbestaetigt', 'bestaetigt', 'warteliste' ), 'compare' => 'IN' ),
                ) ) );
            if ( $dup ) continue;
        }
        $id = wp_insert_post( array( 'post_type' => 'tgs_anmeldung', 'post_status' => 'publish',
            'post_title' => sprintf( '%s — %s', ( $name ? $name : $email ), get_the_title( $kurs_id ) ) ) );
        if ( is_wp_error( $id ) || ! $id ) continue;
        update_post_meta( $id, '_tgs_anm_kurs_id', $kurs_id );
        update_post_meta( $id, '_tgs_anm_name', $name );
        update_post_meta( $id, '_tgs_anm_email', $email );
        update_post_meta( $id, '_tgs_anm_status', $status );
        update_post_meta( $id, '_tgs_anm_token', wp_generate_password( 32, false, false ) );
        update_post_meta( $id, '_tgs_anm_datum', current_time( 'Y-m-d H:i:s' ) );
        update_post_meta( $id, '_tgs_anm_manuell', '1' ); // still importiert → keine Auto-Mails
        $added++;
    }
    tgs_sync_kurs_status( $kurs_id );
    wp_safe_redirect( add_query_arg( 'tgs_added', $added, admin_url( 'post.php?post=' . $kurs_id . '&action=edit' ) ) );
    exit;
}
add_action( 'admin_post_tgs_anm_add', 'tgs_handle_anm_add' );

function tgs_kurs_anmeldungen_metabox_html( $post ) {
    $kurs_id = $post->ID;

    if ( tgs_kurs_ist_offen( $kurs_id ) ) {
        echo '<p style="font-size:14px;"><strong>Offener Kurs</strong> — keine Anmeldung und keine Teilnehmerverwaltung nötig. Auf der Website erscheint nur ein „einfach vorbeikommen"-Hinweis. Umstellen kannst du das oben in „Kursdetails" → <em>Anmeldung</em>.</p>';
        return;
    }

    $cap  = tgs_kurs_capacity( $kurs_id );
    $conf = tgs_kurs_anm_list( $kurs_id, 'bestaetigt' );
    $wait = tgs_kurs_anm_list( $kurs_id, 'warteliste' );
    $pend = tgs_count_anmeldungen( $kurs_id, 'unbestaetigt' );

    $belegt = $cap['unlimited'] ? count( $conf ) . ' (unbegrenzt)' : count( $conf ) . ' / ' . $cap['max'];
    echo '<p style="font-size:14px;margin:.2em 0 .8em;"><strong>Angemeldet:</strong> ' . esc_html( $belegt )
        . ' &nbsp;·&nbsp; <strong>Warteliste:</strong> ' . count( $wait );
    if ( $pend ) echo ' &nbsp;·&nbsp; <span style="color:#999;">Unbestätigt: ' . intval( $pend ) . '</span>';
    echo '</p>';
    echo '<p style="color:#999;font-size:12px;margin:0 0 .5em;">Die max. Teilnehmerzahl stellst du oben in „Kursdetails" ein (leer = unbegrenzt). Status wird automatisch berechnet.</p>';

    if ( isset( $_GET['tgs_added'] ) ) {
        echo '<div class="notice notice-success inline" style="margin:.2em 0 1em;"><p>' . intval( $_GET['tgs_added'] ) . ' Teilnehmer hinzugefügt (ohne E-Mail).</p></div>';
    }
    $add_url = admin_url( 'admin.php?page=tgs-teilnehmer-add&kurs=' . $kurs_id );
    echo '<p style="margin:.2em 0 1.2em;"><a href="' . esc_url( $add_url ) . '" class="button">＋ Teilnehmer manuell hinzufügen (ohne E-Mail)</a> <span style="color:#999;font-size:12px;">— für bereits laufende Kurse</span></p>';

    if ( empty( $conf ) && empty( $wait ) ) {
        echo '<p style="color:#999;">Noch keine Anmeldungen.</p>';
        return;
    }
    tgs_render_anm_table( 'Angemeldet', $conf, false, array( 'remove' ) );
    tgs_render_anm_table( 'Warteliste', $wait, true, array( 'promote', 'remove' ) );
}

/* =========================================================================
 * Backend-Spalten
 * ========================================================================= */
function tgs_anmeldung_admin_columns( $columns ) {
    return array(
        'cb'             => $columns['cb'],
        'title'          => 'Anmeldung',
        'tgs_anm_kurs'   => 'Kurs',
        'tgs_anm_email'  => 'E-Mail',
        'tgs_anm_status' => 'Status',
        'tgs_anm_datum'  => 'Datum',
    );
}
add_filter( 'manage_tgs_anmeldung_posts_columns', 'tgs_anmeldung_admin_columns' );

function tgs_anmeldung_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'tgs_anm_kurs':
            $kurs_id = get_post_meta( $post_id, '_tgs_anm_kurs_id', true );
            echo $kurs_id ? '<a href="' . esc_url( get_edit_post_link( $kurs_id ) ) . '">' . esc_html( get_the_title( $kurs_id ) ) . '</a>' : '—';
            break;
        case 'tgs_anm_email':
            echo esc_html( get_post_meta( $post_id, '_tgs_anm_email', true ) );
            break;
        case 'tgs_anm_status':
            $s = get_post_meta( $post_id, '_tgs_anm_status', true );
            $labels = array( 'unbestaetigt' => '✉ Unbestätigt', 'bestaetigt' => '✓ Angemeldet', 'warteliste' => '⏳ Warteliste', 'storniert' => '✗ Abgemeldet' );
            $colors = array( 'unbestaetigt' => '#999', 'bestaetigt' => '#3D5A40', 'warteliste' => '#C07020', 'storniert' => '#CC3333' );
            printf( '<span style="color:%s;font-weight:600;">%s</span>', $colors[ $s ] ?? '#999', $labels[ $s ] ?? esc_html( $s ) );
            break;
        case 'tgs_anm_datum':
            echo esc_html( get_post_meta( $post_id, '_tgs_anm_datum', true ) );
            break;
    }
}
add_action( 'manage_tgs_anmeldung_posts_custom_column', 'tgs_anmeldung_admin_column_content', 10, 2 );
