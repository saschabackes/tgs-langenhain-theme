<?php
/**
 * Touren — Bewertungen & Kommentare.
 *
 * Nutzt bewusst denselben CPT `tgs_bewertung` wie die Kurse (nur mit
 * `_tgs_bew_tour_id` statt `_tgs_bew_kurs_id`), damit es EIN Bewertungs- und
 * Moderationskonzept gibt und nicht zwei konkurrierende.
 *
 * Der Unterschied zu Kursen: Kursbewertungen sind durch den Anmelde-Token
 * abgesichert — nur bestätigte Teilnehmer können bewerten, deshalb gibt es
 * dort praktisch keinen Spam. Eine Tour kann jeder fahren, also gibt es hier
 * keinen solchen Anker. Statt eines Captchas (reCAPTCHA wäre Google und damit
 * genau das, was wir überall sonst vermeiden) greifen mehrere unauffällige
 * Hürden ineinander:
 *   1. Honeypot-Feld, das nur Bots ausfüllen
 *   2. Mindest-Ausfüllzeit (Formulare, die in <4 s zurückkommen, sind Bots)
 *   3. Rate-Limit pro Absender (gespeichert wird ein Hash, nie die IP)
 *   4. Nonce
 *   5. Moderation: nichts erscheint ohne Freigabe
 *
 * Ehrlich: Punkt 5 bedeutet Arbeit für euch. Ohne ihn geht es aber nicht.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tgs_register_tour_bew_meta() {
    register_post_meta( 'tgs_bewertung', '_tgs_bew_tour_id', array(
        'show_in_rest' => false, 'single' => true, 'type' => 'integer',
    ) );
}
add_action( 'init', 'tgs_register_tour_bew_meta' );

/** Sind Bewertungen für diese Tour erlaubt? (Standard: ja) */
function tgs_tour_bewertung_aktiv( $tour_id ) {
    return get_post_meta( $tour_id, '_tgs_tour_bewertung', true ) !== '0';
}

/** Durchschnitt + Anzahl der freigegebenen Bewertungen. */
function tgs_tour_rating_summary( $tour_id ) {
    $ids = get_posts( array( 'post_type' => 'tgs_bewertung', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids',
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_bew_tour_id', 'value' => $tour_id ),
            array( 'key' => '_tgs_bew_public', 'value' => '1' ),
        ) ) );
    $sum = 0;
    foreach ( $ids as $id ) $sum += intval( get_post_meta( $id, '_tgs_bew_stars', true ) );
    $n = count( $ids );
    return array( 'count' => $n, 'avg' => $n ? round( $sum / $n, 1 ) : 0 );
}

/** Absender-Kennung — gehasht, damit keine IP gespeichert wird (DSGVO). */
function tgs_absender_hash() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
    return substr( md5( $ip . wp_salt( 'nonce' ) ), 0, 20 );
}

/**
 * Einsendung verarbeiten.
 * @return array( ok => bool, msg => string )
 */
function tgs_tour_bewertung_verarbeiten( $tour_id ) {
    if ( empty( $_POST['tgs_tbew_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_tbew_nonce'], 'tgs_tour_bew_' . $tour_id ) ) {
        return array( 'ok' => false, 'msg' => 'Die Sitzung ist abgelaufen. Bitte die Seite neu laden und noch einmal versuchen.' );
    }

    // (1) Honeypot: für Menschen unsichtbar. Bots füllen ihn aus.
    if ( ! empty( $_POST['tgs_hp_website'] ) ) {
        return array( 'ok' => true, 'msg' => 'Danke! Dein Beitrag wird geprüft und dann freigeschaltet.' );
    }

    // (2) Zu schnell = Bot.
    $t = isset( $_POST['tgs_tbew_t'] ) ? intval( $_POST['tgs_tbew_t'] ) : 0;
    if ( $t && ( time() - $t ) < 4 ) {
        return array( 'ok' => true, 'msg' => 'Danke! Dein Beitrag wird geprüft und dann freigeschaltet.' );
    }

    // (3) Rate-Limit
    $key = 'tgs_tbew_' . tgs_absender_hash();
    $n   = (int) get_transient( $key );
    if ( $n >= 3 ) {
        return array( 'ok' => false, 'msg' => 'Du hast gerade schon mehrere Beiträge gesendet. Bitte versuche es später noch einmal.' );
    }

    $stars = isset( $_POST['tgs_tbew_stars'] ) ? intval( $_POST['tgs_tbew_stars'] ) : 0;
    $stars = max( 1, min( 5, $stars ) );
    $komm  = sanitize_textarea_field( wp_unslash( $_POST['tgs_tbew_kommentar'] ?? '' ) );
    $name  = sanitize_text_field( wp_unslash( $_POST['tgs_tbew_name'] ?? '' ) );

    if ( mb_strlen( $komm ) > 1500 ) $komm = mb_substr( $komm, 0, 1500 );
    if ( ! $stars && $komm === '' ) {
        return array( 'ok' => false, 'msg' => 'Bitte vergib Sterne oder schreib einen kurzen Kommentar.' );
    }

    $id = wp_insert_post( array(
        'post_type'   => 'tgs_bewertung',
        'post_status' => 'publish',
        'post_title'  => 'Tour-Bewertung — ' . get_the_title( $tour_id ),
    ) );
    if ( is_wp_error( $id ) || ! $id ) {
        return array( 'ok' => false, 'msg' => 'Da ist etwas schiefgegangen. Bitte später noch einmal versuchen.' );
    }

    update_post_meta( $id, '_tgs_bew_tour_id', $tour_id );
    update_post_meta( $id, '_tgs_bew_stars', $stars );
    update_post_meta( $id, '_tgs_bew_kommentar', $komm );
    update_post_meta( $id, '_tgs_bew_name', $name );
    update_post_meta( $id, '_tgs_bew_public', '0' ); // (5) Moderation
    update_post_meta( $id, '_tgs_bew_datum', current_time( 'Y-m-d H:i:s' ) );

    set_transient( $key, $n + 1, HOUR_IN_SECONDS );

    return array( 'ok' => true, 'msg' => 'Danke! Dein Beitrag wird geprüft und dann freigeschaltet.' );
}

/**
 * Bewertungsblock (Liste + Formular) für die Tourseite.
 */
function tgs_render_tour_bewertungen( $tour_id ) {
    if ( ! tgs_tour_bewertung_aktiv( $tour_id ) ) return '';

    $msg = null;
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['tgs_tbew_submit'] ) ) {
        $msg = tgs_tour_bewertung_verarbeiten( $tour_id );
    }

    $sum  = tgs_tour_rating_summary( $tour_id );
    $revs = get_posts( array( 'post_type' => 'tgs_bewertung', 'post_status' => 'publish', 'numberposts' => 30,
        'orderby' => 'date', 'order' => 'DESC',
        'meta_query' => array( 'relation' => 'AND',
            array( 'key' => '_tgs_bew_tour_id', 'value' => $tour_id ),
            array( 'key' => '_tgs_bew_public', 'value' => '1' ),
        ) ) );

    ob_start();
    ?>
    <div class="tgs-bew-block tgs-tbew">
        <h2 class="tgs-bew-h">Wie war die Runde?</h2>

        <?php if ( $sum['count'] > 0 ) : ?>
            <div class="tgs-bew-summary"><?php echo tgs_stars_display( $sum['avg'] ); ?> <strong><?php echo esc_html( number_format_i18n( $sum['avg'], 1 ) ); ?></strong> <span>· <?php echo intval( $sum['count'] ); ?> <?php echo $sum['count'] === 1 ? 'Bewertung' : 'Bewertungen'; ?></span></div>
        <?php endif; ?>

        <?php foreach ( $revs as $r ) :
            $st   = intval( get_post_meta( $r->ID, '_tgs_bew_stars', true ) );
            $name = get_post_meta( $r->ID, '_tgs_bew_name', true );
            $komm = get_post_meta( $r->ID, '_tgs_bew_kommentar', true );
        ?>
            <div class="tgs-bew-item"><div class="tgs-bew-item-top"><?php echo tgs_stars_display( $st ); ?> <span class="tgs-bew-name"><?php echo $name !== '' ? esc_html( $name ) : 'Anonym'; ?></span></div><?php if ( $komm ) : ?><p class="tgs-bew-komm"><?php echo esc_html( $komm ); ?></p><?php endif; ?></div>
        <?php endforeach; ?>

        <?php if ( $msg ) : ?>
            <div class="tgs-meld <?php echo $msg['ok'] ? 'tgs-meld--info' : 'tgs-meld--warn'; ?>"><span class="tgs-meld-icon"><?php echo $msg['ok'] ? 'ℹ' : '⚠'; ?></span><div class="tgs-meld-body"><?php echo esc_html( $msg['msg'] ); ?></div></div>
        <?php endif; ?>

        <?php if ( ! $msg || ! $msg['ok'] ) : ?>
        <form method="post" class="tgs-tbew-form">
            <?php wp_nonce_field( 'tgs_tour_bew_' . $tour_id, 'tgs_tbew_nonce' ); ?>
            <input type="hidden" name="tgs_tbew_t" value="<?php echo esc_attr( time() ); ?>">
            <p class="tgs-tbew-hp"><label>Website<input type="text" name="tgs_hp_website" tabindex="-1" autocomplete="off"></label></p>

            <fieldset class="tgs-tbew-stars">
                <legend>Deine Bewertung</legend>
                <?php for ( $i = 5; $i >= 1; $i-- ) : ?>
                    <input type="radio" id="tgs-tbew-s<?php echo $i; ?>" name="tgs_tbew_stars" value="<?php echo $i; ?>"><label for="tgs-tbew-s<?php echo $i; ?>" title="<?php echo $i; ?> von 5">★</label>
                <?php endfor; ?>
            </fieldset>

            <p class="tgs-tbew-row"><label for="tgs-tbew-name">Name <span>(optional)</span></label><input type="text" id="tgs-tbew-name" name="tgs_tbew_name" maxlength="60"></p>
            <p class="tgs-tbew-row"><label for="tgs-tbew-k">Dein Kommentar</label><textarea id="tgs-tbew-k" name="tgs_tbew_kommentar" rows="4" maxlength="1500" placeholder="Wie war der Weg? Etwas gesperrt? Lohnender Abstecher?"></textarea></p>
            <p class="tgs-tbew-hint">Wir prüfen jeden Beitrag vor der Veröffentlichung. Es werden keine Cookies gesetzt und keine Daten an Dritte übertragen.</p>
            <button type="submit" name="tgs_tbew_submit" class="tgs-anm-submit">Beitrag senden</button>
        </form>
        <?php endif; ?>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

/* =========================================================================
 * Backend: Moderation direkt an der Tour
 * ========================================================================= */
function tgs_add_tour_bew_metabox() {
    add_meta_box( 'tgs_tour_bewertungen', 'Bewertungen zu dieser Tour', 'tgs_tour_bew_metabox_html', 'tgs_tour', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'tgs_add_tour_bew_metabox' );

function tgs_tour_bew_metabox_html( $post ) {
    $revs = get_posts( array( 'post_type' => 'tgs_bewertung', 'post_status' => 'publish', 'numberposts' => -1,
        'orderby' => 'date', 'order' => 'DESC',
        'meta_query' => array( array( 'key' => '_tgs_bew_tour_id', 'value' => $post->ID ) ) ) );
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
        echo ' ' . tgs_bew_action_link( $r->ID, 'delete', 'Löschen', 'Diesen Beitrag endgültig löschen?' );
        echo '</td></tr>';
    }
    echo '</tbody></table>';
}
