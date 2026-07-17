<?php
/**
 * Kurs-Meldungen — Ausfälle (einzelner Termin), Pausen (Zeitraum) und Freitext-Mitteilungen.
 * Optional Benachrichtigung aller bestätigten Teilnehmer per E-Mail.
 * Anzeige in Kursübersicht (Badge) und auf der Kursseite (Banner).
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * CPT + Helfer
 * ========================================================================= */
function tgs_register_cpt_meldung() {
    register_post_type( 'tgs_meldung', array(
        'labels'       => array( 'name' => 'Kurs-Meldungen', 'singular_name' => 'Meldung' ),
        'public'       => false,
        'show_ui'      => false,
        'supports'     => array( 'title' ),
        'capabilities' => array( 'create_posts' => 'do_not_allow' ),
        'map_meta_cap' => true,
    ) );
    foreach ( array(
        '_tgs_meld_kurs_id' => 'integer', '_tgs_meld_typ' => 'string',
        '_tgs_meld_datum' => 'string', '_tgs_meld_von' => 'string', '_tgs_meld_bis' => 'string',
        '_tgs_meld_text' => 'string', '_tgs_meld_sichtbar_bis' => 'string',
        '_tgs_meld_email' => 'string', '_tgs_meld_erstellt' => 'string',
    ) as $k => $t ) {
        register_post_meta( 'tgs_meldung', $k, array( 'show_in_rest' => false, 'single' => true, 'type' => $t ) );
    }
}
add_action( 'init', 'tgs_register_cpt_meldung' );

function tgs_fmt_datum( $ymd ) {
    if ( ! $ymd ) return '';
    $t = strtotime( $ymd );
    return $t ? date_i18n( 'd.m.Y', $t ) : $ymd;
}

/** Aktuell relevante Meldungen eines Kurses (vergangene Ausfälle/Pausen ausgeblendet). */
function tgs_kurs_aktive_meldungen( $kurs_id ) {
    $all = get_posts( array( 'post_type' => 'tgs_meldung', 'post_status' => 'publish', 'numberposts' => -1,
        'orderby' => 'date', 'order' => 'ASC',
        'meta_query' => array( array( 'key' => '_tgs_meld_kurs_id', 'value' => $kurs_id ) ) ) );
    $today = current_time( 'Y-m-d' );
    $out = array();
    foreach ( $all as $m ) {
        $typ = get_post_meta( $m->ID, '_tgs_meld_typ', true );
        if ( $typ === 'ausfall' ) {
            if ( get_post_meta( $m->ID, '_tgs_meld_datum', true ) >= $today ) $out[] = $m;
        } elseif ( $typ === 'pause' ) {
            if ( get_post_meta( $m->ID, '_tgs_meld_bis', true ) >= $today ) $out[] = $m;
        } else { // info
            $sb = get_post_meta( $m->ID, '_tgs_meld_sichtbar_bis', true );
            if ( ! $sb || $sb >= $today ) $out[] = $m;
        }
    }
    return $out;
}

/** Überschrift einer Meldung. */
function tgs_meldung_headline( $m ) {
    $typ = get_post_meta( $m->ID, '_tgs_meld_typ', true );
    if ( $typ === 'ausfall' ) {
        return 'Kurs fällt aus am ' . tgs_fmt_datum( get_post_meta( $m->ID, '_tgs_meld_datum', true ) );
    }
    if ( $typ === 'pause' ) {
        $v = tgs_fmt_datum( get_post_meta( $m->ID, '_tgs_meld_von', true ) );
        $b = tgs_fmt_datum( get_post_meta( $m->ID, '_tgs_meld_bis', true ) );
        return 'Kurs pausiert ' . ( $v ? 'vom ' . $v . ' ' : '' ) . 'bis ' . $b;
    }
    return ''; // Info: nur Freitext
}

/* =========================================================================
 * Frontend: Banner auf der Kursseite + Badge in der Kurstabelle
 * ========================================================================= */
function tgs_render_kurs_meldungen( $kurs_id ) {
    $ms = tgs_kurs_aktive_meldungen( $kurs_id );
    if ( empty( $ms ) ) return '';
    ob_start();
    echo '<div class="tgs-meld-block">';
    foreach ( $ms as $m ) {
        $typ  = get_post_meta( $m->ID, '_tgs_meld_typ', true );
        $text = get_post_meta( $m->ID, '_tgs_meld_text', true );
        $head = tgs_meldung_headline( $m );
        $warn = ( $typ !== 'info' );
        echo '<div class="tgs-meld ' . ( $warn ? 'tgs-meld--warn' : 'tgs-meld--info' ) . '">';
        echo '<span class="tgs-meld-icon">' . ( $warn ? '⚠' : 'ℹ' ) . '</span><div class="tgs-meld-body">';
        if ( $head ) echo '<strong>' . esc_html( $head ) . '</strong>';
        if ( $text ) echo '<div class="tgs-meld-text">' . nl2br( esc_html( $text ) ) . '</div>';
        echo '</div></div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode( 'tgs_kurs_meldungen', function () { return tgs_render_kurs_meldungen( get_the_ID() ); } );

/** Kleines Badge für die Kurstabelle (nur Ausfall/Pause). */
function tgs_kurs_meldung_badge( $kurs_id ) {
    foreach ( tgs_kurs_aktive_meldungen( $kurs_id ) as $m ) {
        $typ = get_post_meta( $m->ID, '_tgs_meld_typ', true );
        if ( $typ === 'ausfall' ) return '<span class="tgs-kurs-badge tgs-kurs-badge--warn">Fällt aus</span>';
        if ( $typ === 'pause' )   return '<span class="tgs-kurs-badge tgs-kurs-badge--warn">Pause</span>';
    }
    return '';
}

/* =========================================================================
 * E-Mail-Benachrichtigung an bestätigte Teilnehmer
 * ========================================================================= */
function tgs_notify_kurs_meldung( $kurs_id, $meldung_id ) {
    $anms = get_posts( array( 'post_type' => 'tgs_anmeldung', 'post_status' => 'publish', 'numberposts' => -1,
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_anm_kurs_id', 'value' => $kurs_id ),
            array( 'key' => '_tgs_anm_status', 'value' => 'bestaetigt' ),
        ) ) );
    if ( empty( $anms ) ) return 0;

    $kurs = get_the_title( $kurs_id );
    $typ  = get_post_meta( $meldung_id, '_tgs_meld_typ', true );
    $text = get_post_meta( $meldung_id, '_tgs_meld_text', true );
    $head = tgs_meldung_headline( get_post( $meldung_id ) );

    if ( $typ === 'ausfall' )     $subject = 'Kurs fällt aus: ' . $kurs;
    elseif ( $typ === 'pause' )   $subject = 'Kurs-Pause: ' . $kurs;
    else                          $subject = 'Info zu deinem Kurs: ' . $kurs;

    $inner = '<h2>' . esc_html( $kurs ) . '</h2>';
    if ( $head ) $inner .= '<p><strong>' . esc_html( $head ) . '</strong></p>';
    if ( $text ) $inner .= '<p>' . nl2br( esc_html( $text ) ) . '</p>';
    $body = function_exists( 'tgs_mail_wrap' ) ? tgs_mail_wrap( $inner ) : $inner;
    $headers = function_exists( 'tgs_mail_headers' ) ? tgs_mail_headers() : array( 'Content-Type: text/html; charset=UTF-8' );

    $sent = 0;
    foreach ( $anms as $a ) {
        $email = get_post_meta( $a->ID, '_tgs_anm_email', true );
        if ( $email && is_email( $email ) ) { wp_mail( $email, $subject, $body, $headers ); $sent++; }
    }
    return $sent;
}

/* =========================================================================
 * Backend: Metabox am Kurs + Hinzufügen-Seite + Aktionen
 * ========================================================================= */
function tgs_add_meldung_metabox() {
    add_meta_box( 'tgs_kurs_meldungen', 'Ausfälle & Mitteilungen', 'tgs_meldung_metabox_html', 'tgs_kurs', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'tgs_add_meldung_metabox' );

function tgs_meldung_metabox_html( $post ) {
    $kurs_id = $post->ID;
    $add_url = admin_url( 'admin.php?page=tgs-meldung-add&kurs=' . $kurs_id );
    if ( isset( $_GET['tgs_meld_done'] ) ) {
        $s = intval( $_GET['tgs_meld_done'] );
        echo '<div class="notice notice-success inline" style="margin:.2em 0 1em;"><p>Meldung gespeichert' . ( $s > 0 ? ' und an ' . $s . ' Teilnehmer per E-Mail gesendet' : '' ) . '.</p></div>';
    }
    echo '<p style="margin:.2em 0 1em;"><a href="' . esc_url( $add_url ) . '" class="button button-primary">＋ Ausfall / Pause / Mitteilung hinzufügen</a></p>';

    $ms = get_posts( array( 'post_type' => 'tgs_meldung', 'post_status' => 'publish', 'numberposts' => -1,
        'orderby' => 'date', 'order' => 'DESC',
        'meta_query' => array( array( 'key' => '_tgs_meld_kurs_id', 'value' => $kurs_id ) ) ) );
    if ( empty( $ms ) ) { echo '<p style="color:#999;">Keine Meldungen.</p>'; return; }

    $today = current_time( 'Y-m-d' );
    echo '<table class="widefat striped"><thead><tr><th>Typ</th><th>Zeitraum</th><th>Text</th><th>E-Mail</th><th>Aktion</th></tr></thead><tbody>';
    foreach ( $ms as $m ) {
        $typ = get_post_meta( $m->ID, '_tgs_meld_typ', true );
        $mail = get_post_meta( $m->ID, '_tgs_meld_email', true );
        if ( $typ === 'ausfall' )   { $lbl = 'Ausfall'; $zeit = tgs_fmt_datum( get_post_meta( $m->ID, '_tgs_meld_datum', true ) ); }
        elseif ( $typ === 'pause' ) { $lbl = 'Pause'; $zeit = tgs_fmt_datum( get_post_meta( $m->ID, '_tgs_meld_von', true ) ) . ' – ' . tgs_fmt_datum( get_post_meta( $m->ID, '_tgs_meld_bis', true ) ); }
        else                        { $lbl = 'Mitteilung'; $sb = get_post_meta( $m->ID, '_tgs_meld_sichtbar_bis', true ); $zeit = $sb ? 'bis ' . tgs_fmt_datum( $sb ) : 'dauerhaft'; }
        echo '<tr><td><strong>' . esc_html( $lbl ) . '</strong></td><td style="white-space:nowrap;">' . esc_html( $zeit ) . '</td>';
        echo '<td>' . esc_html( wp_trim_words( get_post_meta( $m->ID, '_tgs_meld_text', true ), 16 ) ) . '</td>';
        echo '<td>' . ( $mail ? '✓' : '—' ) . '</td><td>';
        $del = wp_nonce_url( admin_url( 'admin-post.php?action=tgs_meldung_manage&op=delete&meld=' . $m->ID ), 'tgs_meldung_manage_' . $m->ID );
        echo '<a href="' . esc_url( $del ) . '" class="button button-small" onclick="return confirm(\'Meldung löschen?\');">Löschen</a></td></tr>';
    }
    echo '</tbody></table>';
}

/** Versteckte Seite zum Anlegen einer Meldung. */
function tgs_register_meldung_add_page() {
    add_submenu_page( null, 'Meldung hinzufügen', '', 'edit_posts', 'tgs-meldung-add', 'tgs_meldung_add_page' );
}
add_action( 'admin_menu', 'tgs_register_meldung_add_page' );

function tgs_meldung_add_page() {
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Keine Berechtigung.' );
    $kurs_id = isset( $_GET['kurs'] ) ? intval( $_GET['kurs'] ) : 0;
    $kurs    = $kurs_id ? get_post( $kurs_id ) : null;
    if ( ! $kurs || $kurs->post_type !== 'tgs_kurs' ) wp_die( 'Kurs nicht gefunden.' );
    ?>
    <div class="wrap">
        <h1>Ausfall / Pause / Mitteilung — <?php echo esc_html( get_the_title( $kurs_id ) ); ?></h1>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="tgs_meldung_add">
            <input type="hidden" name="kurs_id" value="<?php echo esc_attr( $kurs_id ); ?>">
            <?php wp_nonce_field( 'tgs_meldung_add_' . $kurs_id, 'tgs_meld_nonce' ); ?>
            <table class="form-table"><tbody>
                <tr><th>Art</th><td>
                    <label><input type="radio" name="tgs_meld_typ" value="ausfall" checked class="tgs-meld-typ"> Einzelner Ausfall</label><br>
                    <label><input type="radio" name="tgs_meld_typ" value="pause" class="tgs-meld-typ"> Pause (Zeitraum)</label><br>
                    <label><input type="radio" name="tgs_meld_typ" value="info" class="tgs-meld-typ"> Mitteilung (Freitext)</label>
                </td></tr>
                <tr class="tgs-meld-row tgs-row-ausfall"><th><label for="tgs_meld_datum">Datum des Ausfalls</label></th><td><input type="date" id="tgs_meld_datum" name="tgs_meld_datum"></td></tr>
                <tr class="tgs-meld-row tgs-row-pause" style="display:none;"><th>Zeitraum</th><td>von <input type="date" name="tgs_meld_von"> bis <input type="date" name="tgs_meld_bis"></td></tr>
                <tr class="tgs-meld-row tgs-row-info" style="display:none;"><th><label for="tgs_meld_sb">Sichtbar bis (optional)</label></th><td><input type="date" id="tgs_meld_sb" name="tgs_meld_sichtbar_bis"> <span class="description">leer = bis zum manuellen Löschen</span></td></tr>
                <tr><th><label for="tgs_meld_text">Text / Grund</label></th><td><textarea id="tgs_meld_text" name="tgs_meld_text" rows="4" class="large-text" placeholder="z.B. Grund für den Ausfall, oder: Bitte nächstes Mal XY mitbringen."></textarea></td></tr>
                <tr><th>Benachrichtigung</th><td><label><input type="checkbox" name="tgs_meld_email" value="1" checked> Alle bestätigten Teilnehmer per E-Mail informieren</label></td></tr>
            </tbody></table>
            <p><button type="submit" class="button button-primary">Speichern</button>
               <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $kurs_id . '&action=edit' ) ); ?>" class="button">Abbrechen</a></p>
        </form>
    </div>
    <script>
    (function(){
        function upd(){
            var t=document.querySelector('.tgs-meld-typ:checked'); t=t?t.value:'ausfall';
            document.querySelectorAll('.tgs-meld-row').forEach(function(r){ r.style.display='none'; });
            var sel=document.querySelector('.tgs-row-'+t); if(sel) sel.style.display='';
        }
        document.querySelectorAll('.tgs-meld-typ').forEach(function(el){ el.addEventListener('change',upd); });
        upd();
    })();
    </script>
    <?php
}

function tgs_handle_meldung_add() {
    $kurs_id = isset( $_POST['kurs_id'] ) ? intval( $_POST['kurs_id'] ) : 0;
    if ( ! $kurs_id || empty( $_POST['tgs_meld_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_meld_nonce'], 'tgs_meldung_add_' . $kurs_id ) ) wp_die( 'Ungültige Anfrage.' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Keine Berechtigung.' );

    $typ = in_array( $_POST['tgs_meld_typ'] ?? '', array( 'ausfall', 'pause', 'info' ), true ) ? $_POST['tgs_meld_typ'] : 'ausfall';
    $id  = wp_insert_post( array( 'post_type' => 'tgs_meldung', 'post_status' => 'publish',
        'post_title' => 'Meldung — ' . get_the_title( $kurs_id ) ) );
    if ( is_wp_error( $id ) || ! $id ) wp_die( 'Fehler beim Speichern.' );

    update_post_meta( $id, '_tgs_meld_kurs_id', $kurs_id );
    update_post_meta( $id, '_tgs_meld_typ', $typ );
    update_post_meta( $id, '_tgs_meld_text', sanitize_textarea_field( $_POST['tgs_meld_text'] ?? '' ) );
    update_post_meta( $id, '_tgs_meld_erstellt', current_time( 'Y-m-d H:i:s' ) );
    if ( $typ === 'ausfall' ) {
        update_post_meta( $id, '_tgs_meld_datum', sanitize_text_field( $_POST['tgs_meld_datum'] ?? '' ) );
    } elseif ( $typ === 'pause' ) {
        update_post_meta( $id, '_tgs_meld_von', sanitize_text_field( $_POST['tgs_meld_von'] ?? '' ) );
        update_post_meta( $id, '_tgs_meld_bis', sanitize_text_field( $_POST['tgs_meld_bis'] ?? '' ) );
    } else {
        update_post_meta( $id, '_tgs_meld_sichtbar_bis', sanitize_text_field( $_POST['tgs_meld_sichtbar_bis'] ?? '' ) );
    }

    // Abonnierte Kalender über die Änderung informieren (SEQUENCE hochzählen).
    if ( function_exists( 'tgs_ics_bump_seq' ) ) tgs_ics_bump_seq( $kurs_id );

    $sent = 0;
    if ( ! empty( $_POST['tgs_meld_email'] ) ) {
        update_post_meta( $id, '_tgs_meld_email', '1' );
        $sent = tgs_notify_kurs_meldung( $kurs_id, $id );
    }
    wp_safe_redirect( add_query_arg( 'tgs_meld_done', $sent, admin_url( 'post.php?post=' . $kurs_id . '&action=edit' ) ) );
    exit;
}
add_action( 'admin_post_tgs_meldung_add', 'tgs_handle_meldung_add' );

function tgs_handle_meldung_manage() {
    $meld = isset( $_GET['meld'] ) ? intval( $_GET['meld'] ) : 0;
    $op   = isset( $_GET['op'] ) ? sanitize_key( $_GET['op'] ) : '';
    if ( ! $meld || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'tgs_meldung_manage_' . $meld ) ) wp_die( 'Ungültige Anfrage.' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Keine Berechtigung.' );
    $kurs_id = intval( get_post_meta( $meld, '_tgs_meld_kurs_id', true ) );
    if ( $op === 'delete' ) {
        wp_delete_post( $meld, true );
        if ( function_exists( 'tgs_ics_bump_seq' ) ) tgs_ics_bump_seq( $kurs_id );
    }
    wp_safe_redirect( admin_url( 'post.php?post=' . $kurs_id . '&action=edit' ) );
    exit;
}
add_action( 'admin_post_tgs_meldung_manage', 'tgs_handle_meldung_manage' );
