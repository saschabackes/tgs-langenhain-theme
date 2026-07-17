<?php
/**
 * Touren — Backend: Metabox, GPX-Upload, Speichern.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * GPX-Dateien erlauben.
 * WordPress lässt .gpx standardmäßig NICHT zu — ohne das hier scheitert schon
 * der Upload in die Mediathek.
 */
function tgs_allow_gpx_upload( $mimes ) {
    $mimes['gpx'] = 'application/gpx+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'tgs_allow_gpx_upload' );

/**
 * .gpx ist XML — WordPress' Dateityp-Prüfung erkennt es sonst als text/xml
 * und lehnt den Upload trotz erlaubtem MIME-Type ab.
 */
function tgs_fix_gpx_filetype( $data, $file, $filename, $mimes ) {
    if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) return $data;
    if ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) === 'gpx' ) {
        $data['ext']  = 'gpx';
        $data['type'] = 'application/gpx+xml';
    }
    return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'tgs_fix_gpx_filetype', 10, 4 );

/* =========================================================================
 * Meta registrieren
 * ========================================================================= */
function tgs_register_tour_meta() {
    $felder = array(
        '_tgs_tour_gpx' => 'integer', '_tgs_tour_art' => 'string', '_tgs_tour_level' => 'string',
        '_tgs_tour_dauer' => 'string', '_tgs_tour_trim' => 'integer', '_tgs_tour_komoot' => 'string',
        '_tgs_tour_start_id' => 'integer', '_tgs_tour_kurs_id' => 'integer', '_tgs_tour_einkehr' => 'string',
        '_tgs_tour_km' => 'number', '_tgs_tour_hm_auf' => 'integer', '_tgs_tour_hm_ab' => 'integer',
        '_tgs_tour_ele_min' => 'integer', '_tgs_tour_ele_max' => 'integer', '_tgs_tour_rundkurs' => 'string',
        '_tgs_tour_punkte' => 'integer', '_tgs_tour_track' => 'string', '_tgs_tour_profil' => 'string',
        '_tgs_tour_bounds' => 'string', '_tgs_tour_start' => 'string', '_tgs_tour_ende' => 'string',
        '_tgs_tour_fehler' => 'string', '_tgs_tour_bewertung' => 'string',
    );
    foreach ( $felder as $k => $t ) {
        register_post_meta( 'tgs_tour', $k, array(
            'show_in_rest' => false, 'single' => true, 'type' => $t,
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ) );
    }
}
add_action( 'init', 'tgs_register_tour_meta' );

/* =========================================================================
 * Metabox
 * ========================================================================= */
function tgs_add_tour_metabox() {
    add_meta_box( 'tgs_tour_details', 'Tour-Details', 'tgs_tour_metabox_html', 'tgs_tour', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'tgs_add_tour_metabox' );

function tgs_tour_admin_assets( $hook ) {
    global $post;
    if ( $post && $post->post_type === 'tgs_tour' && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        wp_enqueue_media();
    }
}
add_action( 'admin_enqueue_scripts', 'tgs_tour_admin_assets' );

function tgs_tour_metabox_html( $post ) {
    wp_nonce_field( 'tgs_tour_save_' . $post->ID, 'tgs_tour_nonce' );
    $d       = tgs_tour_daten( $post->ID );
    $fehler  = get_post_meta( $post->ID, '_tgs_tour_fehler', true );
    $gpx_id  = $d['gpx'];
    $gpx_nam = $gpx_id ? basename( get_attached_file( $gpx_id ) ) : '';
    $trim    = (int) get_post_meta( $post->ID, '_tgs_tour_trim', true );
    $bew     = get_post_meta( $post->ID, '_tgs_tour_bewertung', true ) !== '0';

    $staetten = get_posts( array( 'post_type' => 'tgs_sportstaette', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $kurse    = get_posts( array( 'post_type' => 'tgs_kurs', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    ?>
    <?php if ( $fehler ) : ?>
        <div class="notice notice-error inline" style="margin:.2em 0 1em;"><p><?php echo esc_html( $fehler ); ?></p></div>
    <?php endif; ?>

    <table class="form-table"><tbody>

        <tr>
            <th><label>GPX-Datei</label></th>
            <td>
                <input type="hidden" id="tgs_tour_gpx" name="_tgs_tour_gpx" value="<?php echo esc_attr( $gpx_id ); ?>">
                <button type="button" class="button" id="tgs_tour_gpx_pick">GPX auswählen / hochladen</button>
                <button type="button" class="button" id="tgs_tour_gpx_clear" <?php disabled( ! $gpx_id ); ?>>Entfernen</button>
                <span id="tgs_tour_gpx_name" style="margin-left:.6em;font-weight:600;"><?php echo esc_html( $gpx_nam ); ?></span>
                <p class="description">Die GPX gehört dem Verein und bleibt hier. Distanz, Höhenmeter und Streckenverlauf werden beim Speichern automatisch ausgelesen — nichts von Hand eintragen.</p>
            </td>
        </tr>

        <?php if ( tgs_tour_hat_track( $post->ID ) ) : ?>
        <tr>
            <th>Ausgewertet</th>
            <td>
                <p style="margin:.2em 0;">
                    <strong><?php echo esc_html( number_format_i18n( $d['km'], 1 ) ); ?> km</strong> ·
                    ▲ <strong><?php echo esc_html( number_format_i18n( $d['hm_auf'] ) ); ?> m</strong> ·
                    ▼ <?php echo esc_html( number_format_i18n( $d['hm_ab'] ) ); ?> m ·
                    <?php echo esc_html( $d['ele_min'] ); ?>–<?php echo esc_html( $d['ele_max'] ); ?> m ü. NN ·
                    <?php echo $d['rund'] ? 'Rundkurs' : 'Punkt-zu-Punkt'; ?>
                </p>
                <div style="max-width:420px;border:1px solid #dcdcde;border-radius:6px;padding:.4em;background:#fff;">
                    <?php echo tgs_tour_profil_svg( $post->ID ); ?>
                </div>
            </td>
        </tr>
        <?php endif; ?>

        <tr>
            <th><label for="tgs_tour_art">Art</label></th>
            <td><select id="tgs_tour_art" name="_tgs_tour_art">
                <option value="">— bitte wählen —</option>
                <?php foreach ( tgs_tour_arten() as $slug => $lbl ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $d['art'], $slug ); ?>><?php echo esc_html( $lbl ); ?></option>
                <?php endforeach; ?>
            </select></td>
        </tr>

        <tr>
            <th><label for="tgs_tour_level">Level</label></th>
            <td><select id="tgs_tour_level" name="_tgs_tour_level">
                <option value="">— bitte wählen —</option>
                <?php foreach ( tgs_tour_level() as $slug => $lbl ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $d['level'], $slug ); ?>><?php echo esc_html( $lbl ); ?></option>
                <?php endforeach; ?>
            </select></td>
        </tr>

        <tr>
            <th><label for="tgs_tour_dauer">Dauer</label></th>
            <td><input type="text" id="tgs_tour_dauer" name="_tgs_tour_dauer" value="<?php echo esc_attr( $d['dauer'] ); ?>" class="regular-text" placeholder="z. B. ca. 2 Std.">
                <p class="description">Freitext — die Zeit hängt zu sehr von der Gruppe ab, um sie zu rechnen.</p></td>
        </tr>

        <tr>
            <th><label for="tgs_tour_start_id">Startpunkt</label></th>
            <td><select id="tgs_tour_start_id" name="_tgs_tour_start_id">
                <option value="0">— keine Sportstätte —</option>
                <?php foreach ( $staetten as $s ) : ?>
                    <option value="<?php echo esc_attr( $s->ID ); ?>" <?php selected( $d['start_id'], $s->ID ); ?>><?php echo esc_html( $s->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description">Treffpunkt mit Parkplätzen — und der Grund, warum die Tour nicht an jemandes Haustür startet.</p></td>
        </tr>

        <tr>
            <th><label for="tgs_tour_kurs_id">Gehört zu Kurs / Gruppe</label></th>
            <td><select id="tgs_tour_kurs_id" name="_tgs_tour_kurs_id">
                <option value="0">— keine Verknüpfung —</option>
                <?php foreach ( $kurse as $k ) : ?>
                    <option value="<?php echo esc_attr( $k->ID ); ?>" <?php selected( $d['kurs_id'], $k->ID ); ?>><?php echo esc_html( $k->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description">Macht aus einer Datei eine Einladung: „Diese Runde fahren wir dienstags — hier anmelden."</p></td>
        </tr>

        <tr>
            <th><label for="tgs_tour_einkehr">Einkehr</label></th>
            <td><input type="text" id="tgs_tour_einkehr" name="_tgs_tour_einkehr" value="<?php echo esc_attr( $d['einkehr'] ); ?>" class="regular-text" placeholder="z. B. Da Luca am Sportplatz"></td>
        </tr>

        <tr>
            <th><label for="tgs_tour_komoot">Komoot-Link</label></th>
            <td><input type="url" id="tgs_tour_komoot" name="_tgs_tour_komoot" value="<?php echo esc_attr( $d['komoot'] ); ?>" class="regular-text" placeholder="https://www.komoot.de/tour/…">
                <p class="description">Optional, als zusätzlicher Kanal. Die Quelle bleibt die GPX oben.</p></td>
        </tr>

        <tr>
            <th><label for="tgs_tour_trim">Anfang/Ende kürzen</label></th>
            <td><input type="number" id="tgs_tour_trim" name="_tgs_tour_trim" value="<?php echo esc_attr( $trim ); ?>" min="0" max="5000" step="50" style="width:100px;"> Meter
                <p class="description"><strong>Privatsphäre:</strong> Aufgezeichnete Touren starten oft an der Haustür der aufzeichnenden Person — die Adresse steht dann maschinenlesbar in der GPX. Hier die ersten und letzten Meter abschneiden. Wirkt beim nächsten Speichern.</p></td>
        </tr>

        <tr>
            <th>Bewertungen</th>
            <td><label><input type="checkbox" name="_tgs_tour_bewertung" value="1" <?php checked( $bew ); ?>> Bewertungen &amp; Kommentare zulassen</label>
                <p class="description">Jede Einsendung muss unten freigegeben werden, bevor sie öffentlich erscheint.</p></td>
        </tr>

    </tbody></table>

    <script>
    (function () {
        var frame;
        var hid   = document.getElementById('tgs_tour_gpx');
        var name  = document.getElementById('tgs_tour_gpx_name');
        var clear = document.getElementById('tgs_tour_gpx_clear');

        document.getElementById('tgs_tour_gpx_pick').addEventListener('click', function (e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: 'GPX-Datei wählen',
                button: { text: 'Übernehmen' },
                library: { type: ['application/gpx+xml', 'text/xml'] },
                multiple: false
            });
            frame.on('select', function () {
                var a = frame.state().get('selection').first().toJSON();
                hid.value = a.id;
                name.textContent = a.filename || a.title;
                clear.disabled = false;
            });
            frame.open();
        });

        clear.addEventListener('click', function (e) {
            e.preventDefault();
            hid.value = ''; name.textContent = ''; clear.disabled = true;
        });
    })();
    </script>
    <?php
}

/* =========================================================================
 * Speichern
 * ========================================================================= */
function tgs_save_tour_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( empty( $_POST['tgs_tour_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_tour_nonce'], 'tgs_tour_save_' . $post_id ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $gpx_alt = (int) get_post_meta( $post_id, '_tgs_tour_gpx', true );
    $trim_alt = (int) get_post_meta( $post_id, '_tgs_tour_trim', true );

    $gpx_neu  = isset( $_POST['_tgs_tour_gpx'] ) ? intval( $_POST['_tgs_tour_gpx'] ) : 0;
    $trim_neu = isset( $_POST['_tgs_tour_trim'] ) ? max( 0, min( 5000, intval( $_POST['_tgs_tour_trim'] ) ) ) : 0;

    update_post_meta( $post_id, '_tgs_tour_gpx', $gpx_neu );
    update_post_meta( $post_id, '_tgs_tour_trim', $trim_neu );

    $arten = tgs_tour_arten(); $level = tgs_tour_level();
    $art = isset( $_POST['_tgs_tour_art'] ) ? sanitize_key( $_POST['_tgs_tour_art'] ) : '';
    $lvl = isset( $_POST['_tgs_tour_level'] ) ? sanitize_key( $_POST['_tgs_tour_level'] ) : '';
    update_post_meta( $post_id, '_tgs_tour_art', isset( $arten[ $art ] ) ? $art : '' );
    update_post_meta( $post_id, '_tgs_tour_level', isset( $level[ $lvl ] ) ? $lvl : '' );

    update_post_meta( $post_id, '_tgs_tour_dauer', sanitize_text_field( $_POST['_tgs_tour_dauer'] ?? '' ) );
    update_post_meta( $post_id, '_tgs_tour_einkehr', sanitize_text_field( $_POST['_tgs_tour_einkehr'] ?? '' ) );
    update_post_meta( $post_id, '_tgs_tour_komoot', esc_url_raw( $_POST['_tgs_tour_komoot'] ?? '' ) );
    update_post_meta( $post_id, '_tgs_tour_bewertung', empty( $_POST['_tgs_tour_bewertung'] ) ? '0' : '1' );

    $sid = isset( $_POST['_tgs_tour_start_id'] ) ? intval( $_POST['_tgs_tour_start_id'] ) : 0;
    update_post_meta( $post_id, '_tgs_tour_start_id', ( $sid > 0 && get_post_type( $sid ) === 'tgs_sportstaette' ) ? $sid : 0 );

    $kid = isset( $_POST['_tgs_tour_kurs_id'] ) ? intval( $_POST['_tgs_tour_kurs_id'] ) : 0;
    update_post_meta( $post_id, '_tgs_tour_kurs_id', ( $kid > 0 && get_post_type( $kid ) === 'tgs_kurs' ) ? $kid : 0 );

    // Nur neu auswerten, wenn sich die Datei oder der Zuschnitt geändert hat —
    // das Parsen einer großen GPX soll nicht bei jedem Speichern laufen.
    if ( ! $gpx_neu ) {
        foreach ( array( '_tgs_tour_track', '_tgs_tour_profil', '_tgs_tour_bounds', '_tgs_tour_start',
                         '_tgs_tour_ende', '_tgs_tour_km', '_tgs_tour_hm_auf', '_tgs_tour_hm_ab',
                         '_tgs_tour_ele_min', '_tgs_tour_ele_max', '_tgs_tour_rundkurs', '_tgs_tour_punkte',
                         '_tgs_tour_fehler' ) as $k ) {
            delete_post_meta( $post_id, $k );
        }
        return;
    }
    if ( $gpx_neu !== $gpx_alt || $trim_neu !== $trim_alt || ! tgs_tour_hat_track( $post_id ) ) {
        tgs_tour_gpx_auswerten( $post_id );
    }
}
add_action( 'save_post_tgs_tour', 'tgs_save_tour_meta' );

/* =========================================================================
 * Übersichtsspalten
 * ========================================================================= */
function tgs_tour_admin_columns( $cols ) {
    return array(
        'cb'        => $cols['cb'],
        'title'     => 'Tour',
        'tgs_art'   => 'Art',
        'tgs_km'    => 'Distanz',
        'tgs_hm'    => 'Höhenmeter',
        'tgs_level' => 'Level',
        'date'      => $cols['date'],
    );
}
add_filter( 'manage_tgs_tour_posts_columns', 'tgs_tour_admin_columns' );

function tgs_tour_admin_column_content( $col, $post_id ) {
    $d = tgs_tour_daten( $post_id );
    switch ( $col ) {
        case 'tgs_art':
            $a = tgs_tour_arten();
            echo isset( $a[ $d['art'] ] ) ? esc_html( $a[ $d['art'] ] ) : '—';
            break;
        case 'tgs_km':
            echo $d['km'] > 0 ? esc_html( number_format_i18n( $d['km'], 1 ) . ' km' ) : '—';
            break;
        case 'tgs_hm':
            echo $d['hm_auf'] > 0 ? esc_html( '▲ ' . number_format_i18n( $d['hm_auf'] ) . ' m' ) : '—';
            break;
        case 'tgs_level':
            $l = tgs_tour_level();
            echo isset( $l[ $d['level'] ] ) ? esc_html( $l[ $d['level'] ] ) : '—';
            break;
    }
}
add_action( 'manage_tgs_tour_posts_custom_column', 'tgs_tour_admin_column_content', 10, 2 );
