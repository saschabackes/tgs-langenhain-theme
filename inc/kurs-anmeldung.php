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

    $message = '';
    if ( isset( $_POST['tgs_anm_submit'] ) && isset( $_POST['tgs_anm_nonce'] )
         && wp_verify_nonce( $_POST['tgs_anm_nonce'], 'tgs_anmeldung' ) ) {
        $message = tgs_create_anmeldung( $kurs_id );
    }

    $cap     = tgs_kurs_capacity( $kurs_id );
    $is_full = $cap['is_full'];

    ob_start();
    ?>
    <div class="tgs-anmeldung-form" id="tgs-anmeldung">
        <?php if ( $message ) : ?><div class="tgs-anm-message"><?php echo wp_kses_post( $message ); ?></div><?php endif; ?>

        <h3 class="tgs-anm-title"><?php echo $is_full ? 'Auf die Warteliste' : 'Zum Kurs anmelden'; ?></h3>

        <?php if ( $is_full ) : ?>
            <p class="tgs-anm-info tgs-anm-info--wait">Dieser Kurs ist aktuell voll. Du kannst dich auf die Warteliste setzen — wir benachrichtigen dich automatisch, sobald ein Platz frei wird.</p>
        <?php else : ?>
            <p class="tgs-anm-info">
                <?php if ( ! $cap['unlimited'] ) : ?>Noch <?php echo esc_html( $cap['free'] ); ?> von <?php echo esc_html( $cap['max'] ); ?> Plätzen frei. <?php endif; ?>
                Die Teilnahme ist über deine TGS-Mitgliedschaft abgedeckt.
            </p>
        <?php endif; ?>

        <form method="post" action="#tgs-anmeldung">
            <?php wp_nonce_field( 'tgs_anmeldung', 'tgs_anm_nonce' ); ?>
            <input type="hidden" name="tgs_anm_kurs_id" value="<?php echo esc_attr( $kurs_id ); ?>">
            <div class="tgs-anm-field"><label for="tgs_anm_name">Name *</label>
                <input type="text" id="tgs_anm_name" name="tgs_anm_name" required placeholder="Vor- und Nachname"></div>
            <div class="tgs-anm-field"><label for="tgs_anm_email">E-Mail *</label>
                <input type="email" id="tgs_anm_email" name="tgs_anm_email" required placeholder="deine@email.de"></div>
            <div class="tgs-anm-field"><label for="tgs_anm_telefon">Telefon (optional)</label>
                <input type="tel" id="tgs_anm_telefon" name="tgs_anm_telefon" placeholder="0173 ..."></div>
            <div class="tgs-anm-field"><label for="tgs_anm_nachricht">Nachricht (optional)</label>
                <textarea id="tgs_anm_nachricht" name="tgs_anm_nachricht" rows="3" placeholder="Fragen, Anmerkungen..."></textarea></div>
            <div class="tgs-anm-field"><label>
                <input type="checkbox" name="tgs_anm_dsgvo" required>
                Ich stimme der <a href="/datenschutz" target="_blank" rel="noopener">Datenschutzerklärung</a> zu. *</label></div>
            <button type="submit" name="tgs_anm_submit" class="tgs-anm-submit"><?php echo $is_full ? 'Auf Warteliste setzen' : 'Anmeldung absenden'; ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_anmeldung', 'tgs_anmeldung_shortcode' );

/**
 * Legt eine unbestätigte Anmeldung an und verschickt die Bestätigungs-Mail.
 * Gibt die HTML-Statusmeldung fürs Formular zurück.
 */
function tgs_create_anmeldung( $kurs_id ) {
    $name  = sanitize_text_field( $_POST['tgs_anm_name'] ?? '' );
    $email = sanitize_email( $_POST['tgs_anm_email'] ?? '' );
    $tel   = sanitize_text_field( $_POST['tgs_anm_telefon'] ?? '' );
    $msg   = sanitize_textarea_field( $_POST['tgs_anm_nachricht'] ?? '' );

    if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
        return '<p class="tgs-anm-error">Bitte gib deinen Namen und eine gültige E-Mail-Adresse an.</p>';
    }
    if ( empty( $_POST['tgs_anm_dsgvo'] ) ) {
        return '<p class="tgs-anm-error">Bitte stimme der Datenschutzerklärung zu.</p>';
    }

    // Vorhandene Anmeldung dieser E-Mail für diesen Kurs?
    $existing = get_posts( array(
        'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => 1,
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_anm_email', 'value' => $email ),
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

    tgs_mail_optin( $anm_id );

    return '<p class="tgs-anm-success"><strong>Fast geschafft!</strong> Wir haben dir eine E-Mail geschickt. Bitte klicke auf den Bestätigungslink darin — erst dann ist deine Anmeldung gültig.</p>';
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

    // E-Mail der Person bestimmen (aus Anmeldungs-Token, Zugangs-Token oder Abmelde-POST)
    $email = '';
    if ( isset( $_GET['token'] ) ) {
        $a = tgs_get_anmeldung_by_token( sanitize_text_field( wp_unslash( $_GET['token'] ) ) );
        if ( $a ) $email = get_post_meta( $a->ID, '_tgs_anm_email', true );
    } elseif ( isset( $_GET['zugang'] ) ) {
        $email = tgs_resolve_access_token( sanitize_text_field( wp_unslash( $_GET['zugang'] ) ) );
    } elseif ( isset( $_POST['tgs_cancel_token'] ) ) {
        $a = tgs_get_anmeldung_by_token( sanitize_text_field( wp_unslash( $_POST['tgs_cancel_token'] ) ) );
        if ( $a ) $email = get_post_meta( $a->ID, '_tgs_anm_email', true );
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
        '<h2>Hallo ' . esc_html( $name ) . ',</h2>'
        . '<p>fast geschafft! Bitte bestätige deine Anmeldung für <strong>' . esc_html( $kurs ) . '</strong> (' . tgs_kurs_infozeile( $kurs_id ) . ').</p>'
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

    if ( $status === 'bestaetigt' ) {
        $subject = 'Anmeldung bestätigt: ' . $kurs;
        $intro   = '<p>deine Anmeldung für <strong>' . esc_html( $kurs ) . '</strong> (' . tgs_kurs_infozeile( $kurs_id ) . ') ist bestätigt. Du hast einen Platz!</p>';
    } else {
        $subject = 'Warteliste: ' . $kurs;
        $intro   = '<p>der Kurs <strong>' . esc_html( $kurs ) . '</strong> ist aktuell voll — du stehst jetzt auf der <strong>Warteliste</strong>. Sobald ein Platz frei wird, rücken wir dich automatisch nach und melden uns.</p>';
    }
    $body = tgs_mail_wrap(
        '<h2>Hallo ' . esc_html( $name ) . ',</h2>' . $intro
        . '<p>Status ansehen oder abmelden:</p>'
        . '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#3D5A40;color:#fff;font-weight:bold;text-decoration:none;padding:10px 20px;border-radius:8px;">Meine Anmeldung</a></p>'
    );
    wp_mail( $email, $subject, $body, tgs_mail_headers() );
}

function tgs_mail_promoted( $anm_id ) {
    $email   = get_post_meta( $anm_id, '_tgs_anm_email', true );
    $name    = get_post_meta( $anm_id, '_tgs_anm_name', true );
    $token   = get_post_meta( $anm_id, '_tgs_anm_token', true );
    $kurs_id = intval( get_post_meta( $anm_id, '_tgs_anm_kurs_id', true ) );
    $kurs    = get_the_title( $kurs_id );
    $link    = tgs_status_url( $token );

    $body = tgs_mail_wrap(
        '<h2>Gute Nachricht, ' . esc_html( $name ) . '!</h2>'
        . '<p>Ein Platz in <strong>' . esc_html( $kurs ) . '</strong> (' . tgs_kurs_infozeile( $kurs_id ) . ') ist frei geworden — du bist <strong>nachgerückt und jetzt angemeldet</strong>.</p>'
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
}
add_action( 'add_meta_boxes', 'tgs_add_kurs_anmeldungen_metabox' );

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
        echo '<tr>';
        if ( $numbered ) echo '<td>' . $i . '</td>';
        echo '<td><strong>' . esc_html( $name ) . '</strong></td>';
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

function tgs_kurs_anmeldungen_metabox_html( $post ) {
    $kurs_id = $post->ID;
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

    if ( empty( $conf ) && empty( $wait ) ) {
        echo '<p style="color:#999;">Noch keine bestätigten Anmeldungen.</p>';
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
