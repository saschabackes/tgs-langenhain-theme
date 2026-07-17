<?php
/**
 * Kurs-Bewertungen — optional pro Kurs, nur für verifizierte (bestätigte) Teilnehmer,
 * anonym möglich, Freigabe (Moderation) durch den Kursleiter.
 *
 * Bewerten läuft über „Meine Kurse" / den persönlichen Status-Link (Anmeldungs-Token = Nachweis).
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * CPT + Helfer
 * ========================================================================= */
function tgs_register_cpt_bewertung() {
    register_post_type( 'tgs_bewertung', array(
        'labels'       => array( 'name' => 'Bewertungen', 'singular_name' => 'Bewertung' ),
        'public'       => false,
        'show_ui'      => false,
        'supports'     => array( 'title' ),
        'capabilities' => array( 'create_posts' => 'do_not_allow' ),
        'map_meta_cap' => true,
    ) );
    foreach ( array(
        '_tgs_bew_kurs_id' => 'integer', '_tgs_bew_anm_id' => 'integer', '_tgs_bew_stars' => 'integer',
        '_tgs_bew_kommentar' => 'string', '_tgs_bew_name' => 'string', '_tgs_bew_public' => 'string', '_tgs_bew_datum' => 'string',
    ) as $k => $t ) {
        register_post_meta( 'tgs_bewertung', $k, array( 'show_in_rest' => false, 'single' => true, 'type' => $t ) );
    }
}
add_action( 'init', 'tgs_register_cpt_bewertung' );

function tgs_bewertung_aktiv( $kurs_id )    { return get_post_meta( $kurs_id, '_tgs_bewertung_aktiv', true ) === '1'; }
function tgs_bewertung_anzeigen( $kurs_id ) { return get_post_meta( $kurs_id, '_tgs_bewertung_anzeigen', true ) === '1'; }

/** Bestehende Bewertung zu einer Anmeldung (verhindert Doppelbewertung). */
function tgs_user_bewertung( $anm_id ) {
    $q = get_posts( array( 'post_type' => 'tgs_bewertung', 'post_status' => 'publish', 'numberposts' => 1,
        'meta_query' => array( array( 'key' => '_tgs_bew_anm_id', 'value' => $anm_id ) ) ) );
    return $q ? $q[0] : null;
}

/** Zusammenfassung (Ø + Anzahl) über freigegebene Bewertungen. */
function tgs_kurs_rating_summary( $kurs_id ) {
    $ids = get_posts( array( 'post_type' => 'tgs_bewertung', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids',
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_bew_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_bew_public', 'value' => '1' ),
        ) ) );
    $n = count( $ids ); $sum = 0;
    foreach ( $ids as $id ) $sum += intval( get_post_meta( $id, '_tgs_bew_stars', true ) );
    return array( 'count' => $n, 'avg' => $n ? $sum / $n : 0 );
}

/** Sterne-Anzeige (gefüllt bis $value gerundet). */
function tgs_stars_display( $value ) {
    $full = (int) round( $value );
    $out  = '<span class="tgs-stars" aria-hidden="true">';
    for ( $i = 1; $i <= 5; $i++ ) $out .= '<span class="' . ( $i <= $full ? 'on' : '' ) . '">★</span>';
    return $out . '</span>';
}

/* =========================================================================
 * Frontend: Bewertungsformular (verifiziert über Anmeldungs-Token)
 * ========================================================================= */
function tgs_render_bewertung_form( $anm ) {
    $kurs_id  = intval( get_post_meta( $anm->ID, '_tgs_anm_kurs_id', true ) );
    $existing = tgs_user_bewertung( $anm->ID );
    $token    = get_post_meta( $anm->ID, '_tgs_anm_token', true );

    ob_start();
    echo '<div class="tgs-status-card">';
    echo '<h2 class="tgs-status-h">' . esc_html( get_the_title( $kurs_id ) ) . ' bewerten</h2>';

    if ( $existing ) {
        $st  = intval( get_post_meta( $existing->ID, '_tgs_bew_stars', true ) );
        $pub = get_post_meta( $existing->ID, '_tgs_bew_public', true ) === '1';
        echo '<p>Du hast diesen Kurs bereits bewertet: ' . tgs_stars_display( $st ) . '</p>';
        echo '<p class="tgs-status-name">' . ( $pub ? 'Deine Bewertung ist freigegeben und sichtbar.' : 'Deine Bewertung wird nach Freigabe durch den Kursleiter sichtbar.' ) . '</p>';
    } else {
        echo '<p>Wie war der Kurs? Deine Bewertung hilft anderen bei der Auswahl. Du kannst anonym bleiben.</p>';
        echo '<form method="post" class="tgs-bew-form">';
        wp_nonce_field( 'tgs_bew', 'tgs_bew_nonce' );
        echo '<input type="hidden" name="tgs_bew_token" value="' . esc_attr( $token ) . '">';
        echo '<div class="tgs-anm-field"><label>Deine Bewertung *</label><div class="tgs-stars-input">';
        for ( $i = 5; $i >= 1; $i-- ) {
            echo '<input type="radio" id="tgsbew' . $i . '" name="tgs_bew_stars" value="' . $i . '" required><label for="tgsbew' . $i . '" title="' . $i . ' Sterne">★</label>';
        }
        echo '</div></div>';
        echo '<div class="tgs-anm-field"><label for="tgs_bew_komm">Kommentar (optional)</label><textarea id="tgs_bew_komm" name="tgs_bew_komm" rows="3" placeholder="Was hat dir gefallen?"></textarea></div>';
        echo '<div class="tgs-anm-field"><label for="tgs_bew_name">Name (optional)</label><input type="text" id="tgs_bew_name" name="tgs_bew_name" placeholder="Leer lassen = anonym"></div>';
        echo '<button type="submit" class="tgs-anm-submit">Bewertung absenden</button>';
        echo '</form>';
    }
    echo '<p class="tgs-status-back"><a href="' . esc_url( tgs_status_url( $token ) ) . '">← Zu meinen Kursen</a></p>';
    echo '</div>';
    return ob_get_clean();
}

/** Verarbeitet eine abgesendete Bewertung. Gibt eine Statusmeldung zurück. */
function tgs_process_bewertung() {
    if ( ! isset( $_POST['tgs_bew_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_bew_nonce'], 'tgs_bew' ) ) return '';
    $anm = tgs_get_anmeldung_by_token( sanitize_text_field( wp_unslash( $_POST['tgs_bew_token'] ?? '' ) ) );
    if ( ! $anm ) return '<span class="tgs-status-err">Link ungültig.</span>';

    $kurs_id = intval( get_post_meta( $anm->ID, '_tgs_anm_kurs_id', true ) );
    if ( get_post_meta( $anm->ID, '_tgs_anm_status', true ) !== 'bestaetigt' ) {
        return '<span class="tgs-status-err">Nur bestätigte Teilnehmer können bewerten.</span>';
    }
    if ( ! tgs_bewertung_aktiv( $kurs_id ) ) return '<span class="tgs-status-err">Für diesen Kurs sind Bewertungen nicht aktiviert.</span>';
    if ( tgs_user_bewertung( $anm->ID ) ) return '<span class="tgs-status-err">Du hast diesen Kurs bereits bewertet.</span>';

    $stars = intval( $_POST['tgs_bew_stars'] ?? 0 );
    if ( $stars < 1 || $stars > 5 ) return '<span class="tgs-status-err">Bitte wähle eine Sterne-Bewertung.</span>';
    $komm = sanitize_textarea_field( $_POST['tgs_bew_komm'] ?? '' );
    $name = sanitize_text_field( $_POST['tgs_bew_name'] ?? '' );

    $id = wp_insert_post( array( 'post_type' => 'tgs_bewertung', 'post_status' => 'publish',
        'post_title' => 'Bewertung — ' . get_the_title( $kurs_id ) ) );
    if ( is_wp_error( $id ) || ! $id ) return '<span class="tgs-status-err">Fehler beim Speichern.</span>';
    update_post_meta( $id, '_tgs_bew_kurs_id', $kurs_id );
    update_post_meta( $id, '_tgs_bew_anm_id', $anm->ID );
    update_post_meta( $id, '_tgs_bew_stars', $stars );
    update_post_meta( $id, '_tgs_bew_kommentar', $komm );
    update_post_meta( $id, '_tgs_bew_name', $name );
    update_post_meta( $id, '_tgs_bew_public', '0' ); // Moderation
    update_post_meta( $id, '_tgs_bew_datum', current_time( 'Y-m-d H:i:s' ) );

    return '<span class="tgs-status-ok">Danke für deine Bewertung! Sie wird nach Freigabe durch den Kursleiter sichtbar.</span>';
}

/* =========================================================================
 * Öffentliche Anzeige auf der Kursseite
 * ========================================================================= */
function tgs_render_kurs_bewertungen( $kurs_id ) {
    if ( ! tgs_bewertung_aktiv( $kurs_id ) || ! tgs_bewertung_anzeigen( $kurs_id ) ) return '';
    $sum = tgs_kurs_rating_summary( $kurs_id );
    if ( $sum['count'] === 0 ) return '';

    $revs = get_posts( array( 'post_type' => 'tgs_bewertung', 'post_status' => 'publish', 'numberposts' => 30,
        'orderby' => 'date', 'order' => 'DESC',
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_bew_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_bew_public', 'value' => '1' ),
        ) ) );

    ob_start();
    echo '<div class="tgs-bew-block"><h2 class="tgs-bew-h">Bewertungen</h2>';
    echo '<div class="tgs-bew-summary">' . tgs_stars_display( $sum['avg'] )
        . ' <strong>' . number_format_i18n( $sum['avg'], 1 ) . '</strong> <span>· '
        . intval( $sum['count'] ) . ' ' . ( $sum['count'] === 1 ? 'Bewertung' : 'Bewertungen' ) . '</span></div>';
    foreach ( $revs as $r ) {
        $st   = intval( get_post_meta( $r->ID, '_tgs_bew_stars', true ) );
        $name = get_post_meta( $r->ID, '_tgs_bew_name', true );
        $name = $name !== '' ? esc_html( $name ) : 'Anonym';
        $komm = get_post_meta( $r->ID, '_tgs_bew_kommentar', true );
        echo '<div class="tgs-bew-item"><div class="tgs-bew-item-top">' . tgs_stars_display( $st ) . ' <span class="tgs-bew-name">' . $name . '</span></div>';
        if ( $komm ) echo '<p class="tgs-bew-komm">' . esc_html( $komm ) . '</p>';
        echo '</div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode( 'tgs_kurs_bewertungen', function () { return tgs_render_kurs_bewertungen( get_the_ID() ); } );

/* =========================================================================
 * Backend: Moderation direkt am Kurs
 * ========================================================================= */
function tgs_add_bewertung_metabox() {
    add_meta_box( 'tgs_kurs_bewertungen', 'Bewertungen zu diesem Kurs', 'tgs_bewertung_metabox_html', 'tgs_kurs', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'tgs_add_bewertung_metabox' );

function tgs_bew_action_link( $bew_id, $op, $label, $confirm = '' ) {
    $url = wp_nonce_url( admin_url( 'admin-post.php?action=tgs_bew_manage&op=' . $op . '&bew=' . $bew_id ), 'tgs_bew_manage_' . $bew_id );
    $oc  = $confirm ? ' onclick="return confirm(\'' . esc_js( $confirm ) . '\');"' : '';
    return '<a href="' . esc_url( $url ) . '" class="button button-small"' . $oc . '>' . esc_html( $label ) . '</a>';
}

function tgs_bewertung_metabox_html( $post ) {
    $kurs_id = $post->ID;
    if ( ! tgs_bewertung_aktiv( $kurs_id ) ) {
        echo '<p style="color:#999;">Bewertungen sind für diesen Kurs deaktiviert (siehe „Kursdetails" → Bewertungen).</p>';
        return;
    }
    $revs = get_posts( array( 'post_type' => 'tgs_bewertung', 'post_status' => 'publish', 'numberposts' => -1,
        'orderby' => 'date', 'order' => 'DESC',
        'meta_query' => array( array( 'key' => '_tgs_bew_kurs_id', 'value' => $kurs_id ) ) ) );
    if ( empty( $revs ) ) { echo '<p style="color:#999;">Noch keine Bewertungen.</p>'; return; }

    echo '<table class="widefat striped"><thead><tr><th>Sterne</th><th>Name</th><th>Kommentar</th><th>Status</th><th>Aktion</th></tr></thead><tbody>';
    foreach ( $revs as $r ) {
        $st   = intval( get_post_meta( $r->ID, '_tgs_bew_stars', true ) );
        $name = get_post_meta( $r->ID, '_tgs_bew_name', true );
        $name = $name !== '' ? esc_html( $name ) : '<em>Anonym</em>';
        $komm = esc_html( get_post_meta( $r->ID, '_tgs_bew_kommentar', true ) );
        $pub  = get_post_meta( $r->ID, '_tgs_bew_public', true ) === '1';
        echo '<tr><td style="white-space:nowrap;">' . str_repeat( '★', $st ) . str_repeat( '☆', 5 - $st ) . '</td>';
        echo '<td>' . $name . '</td><td>' . $komm . '</td>';
        echo '<td>' . ( $pub ? '<span style="color:#3D5A40;font-weight:600;">Freigegeben</span>' : '<span style="color:#C07020;font-weight:600;">Ausstehend</span>' ) . '</td>';
        echo '<td style="white-space:nowrap;">';
        echo $pub ? tgs_bew_action_link( $r->ID, 'hide', 'Verbergen' ) : tgs_bew_action_link( $r->ID, 'publish', 'Freigeben' );
        echo ' ' . tgs_bew_action_link( $r->ID, 'delete', 'Löschen', 'Diese Bewertung endgültig löschen?' );
        echo '</td></tr>';
    }
    echo '</tbody></table>';
}

function tgs_handle_bew_manage() {
    $bew = isset( $_GET['bew'] ) ? intval( $_GET['bew'] ) : 0;
    $op  = isset( $_GET['op'] ) ? sanitize_key( $_GET['op'] ) : '';
    if ( ! $bew || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'tgs_bew_manage_' . $bew ) ) wp_die( 'Ungültige Anfrage.' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Keine Berechtigung.' );
    // Bewertungen hängen entweder an einem Kurs oder an einer Tour —
    // zurück geht es zu dem Beitrag, von dem aus moderiert wurde.
    $ziel = intval( get_post_meta( $bew, '_tgs_bew_kurs_id', true ) );
    if ( ! $ziel ) $ziel = intval( get_post_meta( $bew, '_tgs_bew_tour_id', true ) );
    if ( $op === 'publish' )     update_post_meta( $bew, '_tgs_bew_public', '1' );
    elseif ( $op === 'hide' )    update_post_meta( $bew, '_tgs_bew_public', '0' );
    elseif ( $op === 'delete' )  wp_delete_post( $bew, true );
    wp_safe_redirect( admin_url( 'post.php?post=' . $ziel . '&action=edit' ) );
    exit;
}
add_action( 'admin_post_tgs_bew_manage', 'tgs_handle_bew_manage' );
