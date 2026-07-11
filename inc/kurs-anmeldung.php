<?php
/**
 * Kursanmeldung — Frontend-Formular, Warteliste, E-Mail-Benachrichtigungen
 *
 * Shortcode: [tgs_anmeldung kurs_id="123"]
 * Wird automatisch auf Kurs-Detailseiten eingebunden.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CPT for Anmeldungen (internal, not public)
 */
function tgs_register_cpt_anmeldung() {
    register_post_type( 'tgs_anmeldung', array(
        'labels' => array(
            'name'          => 'Kurs-Anmeldungen',
            'singular_name' => 'Anmeldung',
            'menu_name'     => 'Anmeldungen',
            'all_items'     => 'Alle Anmeldungen',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-clipboard',
        'menu_position' => 8,
        'supports'     => array( 'title', 'custom-fields' ),
        'capabilities' => array(
            'create_posts' => 'edit_posts', // Allow creation from frontend
        ),
    ) );

    // Meta fields for Anmeldung
    $anmeldung_fields = array(
        '_tgs_anm_kurs_id'   => 'integer',
        '_tgs_anm_name'      => 'string',
        '_tgs_anm_email'     => 'string',
        '_tgs_anm_telefon'   => 'string',
        '_tgs_anm_nachricht' => 'string',
        '_tgs_anm_status'    => 'string',  // 'bestaetigt', 'warteliste', 'abgelehnt', 'storniert'
        '_tgs_anm_datum'     => 'string',
    );

    foreach ( $anmeldung_fields as $key => $type ) {
        register_post_meta( 'tgs_anmeldung', $key, array(
            'show_in_rest' => false,
            'single'       => true,
            'type'         => $type,
        ) );
    }
}
add_action( 'init', 'tgs_register_cpt_anmeldung' );

/**
 * Shortcode: [tgs_anmeldung kurs_id="123"]
 */
function tgs_anmeldung_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'kurs_id' => get_the_ID(),
    ), $atts );

    $kurs_id = intval( $atts['kurs_id'] );
    $kurs    = get_post( $kurs_id );

    if ( ! $kurs || $kurs->post_type !== 'tgs_kurs' ) {
        return '<p>Kurs nicht gefunden.</p>';
    }

    // Check capacity
    $max_tn     = intval( get_post_meta( $kurs_id, '_tgs_max_teilnehmer', true ) );
    $current_tn = tgs_count_anmeldungen( $kurs_id, 'bestaetigt' );
    $is_full    = $max_tn > 0 && $current_tn >= $max_tn;

    // Process form submission
    $message = '';
    if ( isset( $_POST['tgs_anm_submit'] ) && wp_verify_nonce( $_POST['tgs_anm_nonce'], 'tgs_anmeldung' ) ) {
        $message = tgs_process_anmeldung( $kurs_id, $is_full );
        // Re-check capacity after submission
        $current_tn = tgs_count_anmeldungen( $kurs_id, 'bestaetigt' );
        $is_full    = $max_tn > 0 && $current_tn >= $max_tn;
    }

    // Build form HTML
    ob_start();
    ?>
    <div class="tgs-anmeldung-form" id="tgs-anmeldung">
        <?php if ( $message ) : ?>
            <div class="tgs-anm-message"><?php echo wp_kses_post( $message ); ?></div>
        <?php endif; ?>

        <h3 class="tgs-anm-title">
            <?php echo $is_full ? 'Auf die Warteliste setzen' : 'Jetzt anmelden'; ?>
        </h3>

        <?php if ( $is_full ) : ?>
            <p class="tgs-anm-info tgs-anm-info--wait">
                Dieser Kurs ist aktuell ausgebucht. Du kannst dich auf die Warteliste setzen —
                wir benachrichtigen dich per E-Mail, sobald ein Platz frei wird.
            </p>
        <?php else : ?>
            <p class="tgs-anm-info">
                <?php if ( $max_tn > 0 ) : ?>
                    Noch <?php echo esc_html( $max_tn - $current_tn ); ?> von <?php echo esc_html( $max_tn ); ?> Plätzen frei.
                <?php endif; ?>
                Voraussetzung: Mitgliedschaft in der TGS Langenhain.
            </p>
        <?php endif; ?>

        <form method="post" action="#tgs-anmeldung">
            <?php wp_nonce_field( 'tgs_anmeldung', 'tgs_anm_nonce' ); ?>
            <input type="hidden" name="tgs_anm_kurs_id" value="<?php echo esc_attr( $kurs_id ); ?>">

            <div class="tgs-anm-field">
                <label for="tgs_anm_name">Name *</label>
                <input type="text" id="tgs_anm_name" name="tgs_anm_name" required
                       placeholder="Vor- und Nachname">
            </div>

            <div class="tgs-anm-field">
                <label for="tgs_anm_email">E-Mail *</label>
                <input type="email" id="tgs_anm_email" name="tgs_anm_email" required
                       placeholder="deine@email.de">
            </div>

            <div class="tgs-anm-field">
                <label for="tgs_anm_telefon">Telefon (optional)</label>
                <input type="tel" id="tgs_anm_telefon" name="tgs_anm_telefon"
                       placeholder="0173 ...">
            </div>

            <div class="tgs-anm-field">
                <label for="tgs_anm_nachricht">Nachricht (optional)</label>
                <textarea id="tgs_anm_nachricht" name="tgs_anm_nachricht" rows="3"
                          placeholder="Fragen, Anmerkungen..."></textarea>
            </div>

            <div class="tgs-anm-field">
                <label>
                    <input type="checkbox" name="tgs_anm_dsgvo" required>
                    Ich stimme der <a href="/datenschutz" target="_blank">Datenschutzerklärung</a> zu. *
                </label>
            </div>

            <button type="submit" name="tgs_anm_submit" class="tgs-anm-submit">
                <?php echo $is_full ? '📋 Auf Warteliste setzen' : '✓ Verbindlich anmelden'; ?>
            </button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_anmeldung', 'tgs_anmeldung_shortcode' );

/**
 * Process the registration form
 */
function tgs_process_anmeldung( $kurs_id, $is_full ) {
    $name      = sanitize_text_field( $_POST['tgs_anm_name'] ?? '' );
    $email     = sanitize_email( $_POST['tgs_anm_email'] ?? '' );
    $telefon   = sanitize_text_field( $_POST['tgs_anm_telefon'] ?? '' );
    $nachricht = sanitize_textarea_field( $_POST['tgs_anm_nachricht'] ?? '' );

    if ( empty( $name ) || empty( $email ) ) {
        return '<p class="tgs-anm-error">Bitte Name und E-Mail ausfüllen.</p>';
    }

    // Check for duplicate registration
    $existing = get_posts( array(
        'post_type'  => 'tgs_anmeldung',
        'meta_query' => array(
            'relation' => 'AND',
            array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_anm_email', 'value' => $email ),
            array( 'key' => '_tgs_anm_status', 'value' => array( 'bestaetigt', 'warteliste' ), 'compare' => 'IN' ),
        ),
    ) );

    if ( ! empty( $existing ) ) {
        return '<p class="tgs-anm-error">Du bist für diesen Kurs bereits angemeldet.</p>';
    }

    $status = $is_full ? 'warteliste' : 'bestaetigt';
    $kurs_title = get_the_title( $kurs_id );

    // Create Anmeldung post
    $anm_id = wp_insert_post( array(
        'post_type'   => 'tgs_anmeldung',
        'post_title'  => sprintf( '%s — %s', $name, $kurs_title ),
        'post_status' => 'publish',
    ) );

    if ( is_wp_error( $anm_id ) ) {
        return '<p class="tgs-anm-error">Fehler bei der Anmeldung. Bitte versuche es erneut.</p>';
    }

    update_post_meta( $anm_id, '_tgs_anm_kurs_id', $kurs_id );
    update_post_meta( $anm_id, '_tgs_anm_name', $name );
    update_post_meta( $anm_id, '_tgs_anm_email', $email );
    update_post_meta( $anm_id, '_tgs_anm_telefon', $telefon );
    update_post_meta( $anm_id, '_tgs_anm_nachricht', $nachricht );
    update_post_meta( $anm_id, '_tgs_anm_status', $status );
    update_post_meta( $anm_id, '_tgs_anm_datum', current_time( 'Y-m-d H:i:s' ) );

    // Auto-update kurs status if full
    $max_tn = intval( get_post_meta( $kurs_id, '_tgs_max_teilnehmer', true ) );
    if ( $max_tn > 0 ) {
        $count = tgs_count_anmeldungen( $kurs_id, 'bestaetigt' );
        if ( $count >= $max_tn ) {
            update_post_meta( $kurs_id, '_tgs_status', 'warteliste' );
        }
    }

    // Send emails
    tgs_send_anmeldung_emails( $anm_id, $kurs_id, $status );

    if ( $status === 'warteliste' ) {
        return '<p class="tgs-anm-success tgs-anm-success--wait">✓ Du stehst jetzt auf der Warteliste für <strong>' . esc_html( $kurs_title ) . '</strong>. Wir melden uns per E-Mail, sobald ein Platz frei wird.</p>';
    }

    return '<p class="tgs-anm-success">✓ Deine Anmeldung für <strong>' . esc_html( $kurs_title ) . '</strong> war erfolgreich! Du erhältst eine Bestätigung per E-Mail.</p>';
}

/**
 * Count registrations for a course
 */
function tgs_count_anmeldungen( $kurs_id, $status = 'bestaetigt' ) {
    return count( get_posts( array(
        'post_type'   => 'tgs_anmeldung',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields'      => 'ids',
        'meta_query'  => array(
            array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_anm_status', 'value' => $status ),
        ),
    ) ) );
}

/**
 * Send confirmation and notification emails
 */
function tgs_send_anmeldung_emails( $anm_id, $kurs_id, $status ) {
    $name       = get_post_meta( $anm_id, '_tgs_anm_name', true );
    $email      = get_post_meta( $anm_id, '_tgs_anm_email', true );
    $kurs_title = get_the_title( $kurs_id );
    $wochentag  = get_post_meta( $kurs_id, '_tgs_wochentag', true );
    $uhrzeit    = get_post_meta( $kurs_id, '_tgs_uhrzeit', true );
    $ort        = get_post_meta( $kurs_id, '_tgs_ort', true );
    $kl_name    = get_post_meta( $kurs_id, '_tgs_ansprechpartner', true );
    $kl_email   = get_post_meta( $kurs_id, '_tgs_ansprechpartner_email', true );

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    // 1. Mail an Teilnehmer
    if ( $status === 'bestaetigt' ) {
        $subject_tn = sprintf( 'Anmeldung bestätigt: %s', $kurs_title );
        $body_tn = sprintf(
            '<h2>Hallo %s,</h2>
            <p>deine Anmeldung für <strong>%s</strong> war erfolgreich!</p>
            <table style="border-collapse:collapse;">
                <tr><td style="padding:4px 12px 4px 0;color:#999;">Kurs</td><td><strong>%s</strong></td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#999;">Wann</td><td>%s, %s Uhr</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#999;">Wo</td><td>%s</td></tr>
            </table>
            <p>Bei Fragen wende dich an %s (%s).</p>
            <p>Sportliche Grüße,<br>TGS 1886 Langenhain e.V.</p>',
            esc_html( $name ), esc_html( $kurs_title ),
            esc_html( $kurs_title ), esc_html( $wochentag ), esc_html( $uhrzeit ), esc_html( $ort ),
            esc_html( $kl_name ), esc_html( $kl_email )
        );
    } else {
        $subject_tn = sprintf( 'Warteliste: %s', $kurs_title );
        $body_tn = sprintf(
            '<h2>Hallo %s,</h2>
            <p>der Kurs <strong>%s</strong> ist aktuell ausgebucht. Du stehst jetzt auf der Warteliste.</p>
            <p>Wir benachrichtigen dich per E-Mail, sobald ein Platz frei wird.</p>
            <p>Sportliche Grüße,<br>TGS 1886 Langenhain e.V.</p>',
            esc_html( $name ), esc_html( $kurs_title )
        );
    }
    wp_mail( $email, $subject_tn, $body_tn, $headers );

    // 2. Mail an Kursleiter
    if ( $kl_email ) {
        $subject_kl = sprintf( 'Neue %s: %s für %s', $status === 'warteliste' ? 'Wartelisten-Anmeldung' : 'Kurs-Anmeldung', $name, $kurs_title );
        $body_kl = sprintf(
            '<p><strong>%s</strong> hat sich für <strong>%s</strong> %s.</p>
            <table style="border-collapse:collapse;">
                <tr><td style="padding:4px 12px 4px 0;color:#999;">Name</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#999;">E-Mail</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#999;">Status</td><td>%s</td></tr>
            </table>
            <p><a href="%s">Anmeldung im Backend ansehen</a></p>',
            esc_html( $name ), esc_html( $kurs_title ),
            $status === 'warteliste' ? 'auf die Warteliste gesetzt' : 'angemeldet',
            esc_html( $name ), esc_html( $email ),
            $status === 'warteliste' ? '⚠ Warteliste' : '✓ Bestätigt',
            admin_url( 'post.php?post=' . $anm_id . '&action=edit' )
        );
        wp_mail( $kl_email, $subject_kl, $body_kl, $headers );
    }
}

/**
 * Admin column for Anmeldungen
 */
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
            echo $kurs_id ? '<a href="' . get_edit_post_link( $kurs_id ) . '">' . esc_html( get_the_title( $kurs_id ) ) . '</a>' : '—';
            break;
        case 'tgs_anm_email':
            echo esc_html( get_post_meta( $post_id, '_tgs_anm_email', true ) );
            break;
        case 'tgs_anm_status':
            $status = get_post_meta( $post_id, '_tgs_anm_status', true );
            $labels = array( 'bestaetigt' => '✓ Bestätigt', 'warteliste' => '⚠ Warteliste', 'abgelehnt' => '✗ Abgelehnt', 'storniert' => '↩ Storniert' );
            $colors = array( 'bestaetigt' => '#3D5A40', 'warteliste' => '#C07020', 'abgelehnt' => '#CC3333', 'storniert' => '#999' );
            printf( '<span style="color:%s;font-weight:600;">%s</span>', $colors[ $status ] ?? '#999', $labels[ $status ] ?? $status );
            break;
        case 'tgs_anm_datum':
            echo esc_html( get_post_meta( $post_id, '_tgs_anm_datum', true ) );
            break;
    }
}
add_action( 'manage_tgs_anmeldung_posts_custom_column', 'tgs_anmeldung_admin_column_content', 10, 2 );
