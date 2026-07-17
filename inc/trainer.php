<?php
/**
 * Kursleitungen — eigener Bereich (CPT), im Kurs nur noch verknüpft.
 *
 * Warum: Eine Kursleitung gibt oft mehrere Kurse. Statt die Daten pro Kurs zu
 * kopieren, wird sie EINMAL gepflegt und in den Kursen nur ausgewählt — wie die
 * Guides bei den Touren.
 *
 * Datenschutz: Der CPT ist NICHT öffentlich (keine eigene Profilseite). Sonst
 * wäre eine Seite mit Name + Foto + allen Kursen genau das leicht abgreifbare
 * Profil, das wir vermeiden wollen. Die Kursleitung erscheint nur eingebettet
 * auf den Kursseiten — Name crawler-geschützt, Foto mit neutralem „alt".
 *
 * Übergang: Kurse ohne verknüpfte Kursleitung fallen automatisch auf die alten
 * Inline-Felder (_tgs_ansprechpartner*) zurück — nichts geht verloren.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * CPT (nicht öffentlich)
 * ========================================================================= */
function tgs_register_cpt_trainer() {
    register_post_type( 'tgs_trainer', array(
        'labels' => array(
            'name'          => 'Kursleitungen',
            'singular_name' => 'Kursleitung',
            'menu_name'     => 'Kursleitungen',
            'add_new'       => 'Neue Kursleitung',
            'add_new_item'  => 'Neue Kursleitung anlegen',
            'edit_item'     => 'Kursleitung bearbeiten',
            'all_items'     => 'Alle Kursleitungen',
            'not_found'     => 'Noch keine Kursleitungen angelegt',
        ),
        'public'             => false,      // keine öffentliche Profilseite
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,       // komfortabler Editor
        'menu_position'      => 6,
        'menu_icon'          => 'dashicons-groups',
        'supports'           => array( 'title', 'thumbnail' ),
    ) );

    foreach ( array( '_tgs_trainer_bio' => 'string', '_tgs_trainer_email' => 'string', '_tgs_trainer_tel' => 'string' ) as $k => $t ) {
        register_post_meta( 'tgs_trainer', $k, array(
            'show_in_rest' => false, 'single' => true, 'type' => $t,
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ) );
    }
}
add_action( 'init', 'tgs_register_cpt_trainer' );

/* =========================================================================
 * Backend: Metabox an der Kursleitung (Bio, E-Mail, Telefon)
 * ========================================================================= */
function tgs_trainer_metabox() {
    add_meta_box( 'tgs_trainer_details', 'Kontakt & Vorstellung', 'tgs_trainer_metabox_html', 'tgs_trainer', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'tgs_trainer_metabox' );

function tgs_trainer_metabox_html( $post ) {
    wp_nonce_field( 'tgs_trainer_save_' . $post->ID, 'tgs_trainer_nonce' );
    $bio   = get_post_meta( $post->ID, '_tgs_trainer_bio', true );
    $email = get_post_meta( $post->ID, '_tgs_trainer_email', true );
    $tel   = get_post_meta( $post->ID, '_tgs_trainer_tel', true );
    ?>
    <table class="form-table"><tbody>
        <tr>
            <th><label for="tgs_trainer_bio">Kurze Vorstellung (optional)</label></th>
            <td><textarea id="tgs_trainer_bio" name="_tgs_trainer_bio" rows="3" class="large-text" placeholder="Ein, zwei Sätze in warmem Ton – kein Steckbrief."><?php echo esc_textarea( $bio ); ?></textarea>
                <p class="description">Nur, wenn die Kursleitung das möchte. Leer + kein Foto = auf der Kursseite erscheint keine Vorstellung, nur (falls gesetzt) der Kontakt.</p></td>
        </tr>
        <tr>
            <th><label for="tgs_trainer_email">E-Mail (optional)</label></th>
            <td><input type="email" id="tgs_trainer_email" name="_tgs_trainer_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" placeholder="name@example.de">
                <p class="description">Wird crawler-geschützt als „E-Mail schreiben"-Link angezeigt (Adresse nie im Klartext).</p></td>
        </tr>
        <tr>
            <th><label for="tgs_trainer_tel">Telefon (optional)</label></th>
            <td><input type="tel" id="tgs_trainer_tel" name="_tgs_trainer_tel" value="<?php echo esc_attr( $tel ); ?>" class="regular-text" placeholder="0173 …"></td>
        </tr>
        <tr>
            <th>Foto</th>
            <td><p class="description">Das <strong>Beitragsbild</strong> (rechts) ist das Foto. Tipp: mit neutralem Dateinamen hochladen (nicht „vorname-nachname.jpg"), Name wird crawler-geschützt eingesetzt.</p></td>
        </tr>
    </tbody></table>
    <?php
}

function tgs_trainer_save( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( empty( $_POST['tgs_trainer_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_trainer_nonce'], 'tgs_trainer_save_' . $post_id ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    update_post_meta( $post_id, '_tgs_trainer_bio', sanitize_textarea_field( $_POST['_tgs_trainer_bio'] ?? '' ) );
    update_post_meta( $post_id, '_tgs_trainer_email', sanitize_email( $_POST['_tgs_trainer_email'] ?? '' ) );
    update_post_meta( $post_id, '_tgs_trainer_tel', sanitize_text_field( $_POST['_tgs_trainer_tel'] ?? '' ) );
}
add_action( 'save_post_tgs_trainer', 'tgs_trainer_save' );

/* =========================================================================
 * Backend: Auswahl-Box am Kurs
 * ========================================================================= */
function tgs_kurs_trainer_metabox() {
    add_meta_box( 'tgs_kurs_trainer', 'Kursleitung', 'tgs_kurs_trainer_metabox_html', 'tgs_kurs', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'tgs_kurs_trainer_metabox' );

function tgs_kurs_trainer_metabox_html( $post ) {
    wp_nonce_field( 'tgs_kurs_trainer_save_' . $post->ID, 'tgs_kurs_trainer_nonce' );
    $selected = tgs_kurs_trainer_ids( $post->ID, false );
    $trainers = get_posts( array( 'post_type' => 'tgs_trainer', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    $add_url  = admin_url( 'post-new.php?post_type=tgs_trainer' );

    if ( empty( $trainers ) ) {
        echo '<p>Noch keine Kursleitungen angelegt.</p>';
        echo '<p><a href="' . esc_url( $add_url ) . '" class="button">＋ Kursleitung anlegen</a></p>';
    } else {
        echo '<p style="margin-top:0;">Wer leitet diesen Kurs? (Mehrfachauswahl möglich)</p>';
        echo '<ul style="margin:0 0 .6em; max-height:220px; overflow:auto;">';
        foreach ( $trainers as $t ) {
            printf(
                '<li><label><input type="checkbox" name="tgs_kurs_trainer[]" value="%d"%s> %s</label></li>',
                (int) $t->ID, in_array( $t->ID, $selected, true ) ? ' checked' : '', esc_html( $t->post_title )
            );
        }
        echo '</ul>';
        echo '<a href="' . esc_url( $add_url ) . '" class="button button-small">＋ Neue Kursleitung</a>';
    }

    // Hinweis auf Alt-Daten (Fallback), falls vorhanden und nichts verknüpft.
    if ( empty( $selected ) && get_post_meta( $post->ID, '_tgs_ansprechpartner', true ) ) {
        echo '<p class="description" style="margin-top:.8em;">Für diesen Kurs sind noch alte Kursleiter-Daten hinterlegt. Sie werden angezeigt, bis du oben eine Kursleitung verknüpfst.</p>';
    }
}

function tgs_kurs_trainer_save( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( empty( $_POST['tgs_kurs_trainer_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_kurs_trainer_nonce'], 'tgs_kurs_trainer_save_' . $post_id ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $ids = isset( $_POST['tgs_kurs_trainer'] ) && is_array( $_POST['tgs_kurs_trainer'] ) ? array_map( 'intval', $_POST['tgs_kurs_trainer'] ) : array();
    $ids = array_values( array_filter( $ids, function ( $id ) { return get_post_type( $id ) === 'tgs_trainer'; } ) );
    update_post_meta( $post_id, '_tgs_kurs_trainer', $ids );
}
add_action( 'save_post_tgs_kurs', 'tgs_kurs_trainer_save' );

/* =========================================================================
 * Daten & Frontend
 * ========================================================================= */

/** Verknüpfte Kursleitungs-IDs eines Kurses (nur veröffentlichte, wenn $published). */
function tgs_kurs_trainer_ids( $kurs_id, $published = true ) {
    $ids = get_post_meta( $kurs_id, '_tgs_kurs_trainer', true );
    if ( ! is_array( $ids ) ) return array();
    $ids = array_map( 'intval', $ids );
    if ( $published ) {
        $ids = array_values( array_filter( $ids, function ( $id ) {
            return get_post_type( $id ) === 'tgs_trainer' && get_post_status( $id ) === 'publish';
        } ) );
    }
    return $ids;
}

/** Eine einzelne Kursleitungs-Karte (aus CPT-Daten). */
function tgs_trainer_card( $trainer_id ) {
    $foto  = get_post_thumbnail_id( $trainer_id );
    $name  = get_the_title( $trainer_id );
    $bio   = trim( (string) get_post_meta( $trainer_id, '_tgs_trainer_bio', true ) );
    $email = get_post_meta( $trainer_id, '_tgs_trainer_email', true );
    $tel   = get_post_meta( $trainer_id, '_tgs_trainer_tel', true );
    return tgs_trainer_card_markup( $foto, $name, $bio, $email, $tel );
}

/**
 * Gemeinsames Karten-Markup — genutzt von CPT-Karten UND vom Alt-Daten-Fallback.
 * Passt sich an, was vorhanden ist; ohne Foto kein Platzhalter.
 */
function tgs_trainer_card_markup( $foto_id, $name, $bio, $email, $tel ) {
    $foto_id = (int) $foto_id;
    $name    = trim( (string) $name );
    $bio     = trim( (string) $bio );
    if ( ! $foto_id && $name === '' && $bio === '' && ! $email && ! $tel ) return '';

    ob_start();
    ?>
    <div class="tgs-kd-trainer<?php echo $foto_id ? '' : ' tgs-kd-trainer--nophoto'; ?>">
        <?php if ( $foto_id ) : ?>
        <div class="tgs-kd-trainer-foto"><?php echo wp_get_attachment_image( $foto_id, array( 240, 240 ), false, array( 'alt' => 'Kursleitung', 'loading' => 'lazy' ) ); ?></div>
        <?php endif; ?>
        <div class="tgs-kd-trainer-txt">
            <span class="tgs-kd-trainer-label">Deine Kursleitung</span>
            <?php if ( $name !== '' ) : ?><span class="tgs-kd-trainer-name"><?php echo tgs_trainer_name_html( $name ); ?></span><?php endif; ?>
            <?php if ( $bio !== '' ) : ?><p class="tgs-kd-trainer-bio"><?php echo nl2br( esc_html( $bio ) ); ?></p><?php endif; ?>
            <?php if ( $email || $tel ) : ?>
            <span class="tgs-kd-trainer-contact">
                <?php if ( $email ) echo tgs_mail_link( $email, 'E-Mail schreiben' ); ?>
                <?php if ( $tel ) : ?><?php echo $email ? ' · ' : ''; ?><?php echo esc_html( $tel ); ?><?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Kursleitung(en) eines Kurses fürs Frontend.
 * Bevorzugt verknüpfte Kursleitungen; sonst Fallback auf die alten Inline-Felder.
 */
function tgs_kurs_leitung_html( $kurs_id ) {
    $ids = tgs_kurs_trainer_ids( $kurs_id );
    $out = '';

    if ( ! empty( $ids ) ) {
        foreach ( $ids as $id ) $out .= tgs_trainer_card( $id );
    } else {
        // Fallback: alte Inline-Daten am Kurs.
        $out = tgs_trainer_card_markup(
            (int) get_post_meta( $kurs_id, '_tgs_ansprechpartner_foto', true ),
            get_post_meta( $kurs_id, '_tgs_ansprechpartner', true ),
            get_post_meta( $kurs_id, '_tgs_ansprechpartner_text', true ),
            get_post_meta( $kurs_id, '_tgs_ansprechpartner_email', true ),
            get_post_meta( $kurs_id, '_tgs_ansprechpartner_tel', true )
        );
    }

    if ( $out === '' ) return '';
    return '<div class="tgs-kd-leitung-wrap">' . tgs_strip_ws( $out ) . '</div>';
}
