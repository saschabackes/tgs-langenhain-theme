<?php
/**
 * Meta Fields / Custom Fields for CPTs
 * Uses WordPress native meta boxes (no ACF dependency)
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Standardisierte Zielgruppen (Slug => Label).
 * Mehrfachauswahl je Kurs; wird auch für den Besucher-Filter genutzt.
 */
function tgs_zielgruppen() {
    return array(
        'kinder'      => 'Kinder',
        'jugendliche' => 'Jugendliche',
        'erwachsene'  => 'Erwachsene',
        'senioren'    => 'Senioren',
        'frauen'      => 'Frauen',
        'maenner'     => 'Männer',
    );
}

/**
 * Zielgruppen-Slugs eines Kurses als Array (robust gegenüber Altdaten-Freitext).
 */
function tgs_kurs_zielgruppen( $post_id ) {
    $val = get_post_meta( $post_id, '_tgs_zielgruppe', true );
    if ( is_array( $val ) ) {
        return array_values( array_intersect( array_keys( tgs_zielgruppen() ), $val ) );
    }
    return array();
}

/**
 * Zielgruppen eines Kurses als lesbare Labels.
 */
function tgs_kurs_zielgruppen_labels( $post_id ) {
    $map = tgs_zielgruppen();
    $out = array();
    foreach ( tgs_kurs_zielgruppen( $post_id ) as $slug ) {
        if ( isset( $map[ $slug ] ) ) $out[] = $map[ $slug ];
    }
    return $out;
}

/**
 * Register meta fields for REST API / Block Editor access
 */
function tgs_register_meta_fields() {
    // Kurs meta fields
    $kurs_fields = array(
        '_tgs_kurs_kurz'       => 'string',   // Kurzbeschreibung (Teaser)
        '_tgs_kurs_ueber'      => 'string',   // Über den Kurs (Fließtext)
        '_tgs_kurs_highlights' => 'string',   // Das erwartet dich (1 pro Zeile)
        '_tgs_wochentag'       => 'string',
        '_tgs_uhrzeit'         => 'string',
        '_tgs_uhrzeit_ende'    => 'string',
        '_tgs_ort'             => 'string',
        '_tgs_ort_id'          => 'integer',  // Link to tgs_sportstaette
        // Saisonabhängig (Sommer = Standardfelder oben, Winter = folgende Felder)
        '_tgs_saison'          => 'string',   // '1' = saisonabhängig (Winterfelder aktiv)
        '_tgs_winter_wochentag'    => 'string',
        '_tgs_winter_uhrzeit'      => 'string',
        '_tgs_winter_uhrzeit_ende' => 'string',
        '_tgs_winter_ort'          => 'string',
        '_tgs_winter_ort_id'       => 'integer',
        '_tgs_winter_pause'        => 'string', // '1' = im Winter kein Betrieb
        '_tgs_winter_von'          => 'integer', // Monat 1–12, Standard 10 (Okt)
        '_tgs_winter_bis'          => 'integer', // Monat 1–12, Standard 3 (März)
        '_tgs_status'          => 'string',   // 'frei' or 'warteliste'
        '_tgs_max_teilnehmer'  => 'integer',
        '_tgs_kurs_anmeldung'  => 'string',   // '' / 'pflicht' or 'offen'
        '_tgs_kurs_fragen'     => 'string',   // '' (=an) / 'aus' – Rückfrage-Funktion
        '_tgs_bewertung_aktiv'    => 'string', // '1' = an
        '_tgs_bewertung_anzeigen' => 'string', // '1' = öffentlich zeigen
        '_tgs_kurs_kinder'        => 'string', // '1' = Kinderkurs (Kind + Elternkontakt)
        '_tgs_kurs_alter_min'     => 'integer', // Mindestalter (Jahre), leer = keine Grenze
        '_tgs_kurs_alter_max'     => 'integer', // Höchstalter (Jahre), leer = keine Grenze
        '_tgs_ansprechpartner' => 'string',
        '_tgs_ansprechpartner_email' => 'string',
        '_tgs_ansprechpartner_tel'   => 'string',
        '_tgs_ansprechpartner_foto'  => 'integer', // Kursleitung-Foto (Attachment-ID), crawler-geschützt gerendert
        '_tgs_ansprechpartner_text'  => 'string',  // kurze Vorstellung (optional) – kein Steckbrief
        '_tgs_mitbringen'      => 'string',
    );

    foreach ( $kurs_fields as $key => $type ) {
        register_post_meta( 'tgs_kurs', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ) );
    }

    // Zielgruppe als Mehrfachauswahl (Array von Slugs)
    register_post_meta( 'tgs_kurs', '_tgs_zielgruppe', array(
        'show_in_rest'  => array(
            'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
        ),
        'single'        => true,
        'type'          => 'array',
        'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
    ) );

    // Sportstätte meta fields
    $ss_fields = array(
        '_tgs_ss_typ'       => 'string',   // z.B. Außenanlage / Fitnessplatz, Sporthalle
        '_tgs_adresse'      => 'string',
        '_tgs_plz_ort'      => 'string',
        '_tgs_maps_link'    => 'string',
        '_tgs_ss_zugang'    => 'string',   // z.B. 24/7 frei zugänglich
        '_tgs_ss_kosten'    => 'string',   // z.B. Kostenlos – keine Mitgliedschaft nötig
        '_tgs_ausstattung'  => 'string',
        '_tgs_barrierefreiheit' => 'string',
        '_tgs_parkplaetze'  => 'string',
        '_tgs_ss_app_name'    => 'string',
        '_tgs_ss_app_desc'    => 'string',
        '_tgs_ss_app_ios'     => 'string',
        '_tgs_ss_app_android' => 'string',
        '_tgs_ss_nahkauf'     => 'string',   // Entfernung/Hinweis zur Nahkauf Box; leer = ausblenden
        '_tgs_ss_nahkauf_link'=> 'string',   // optionaler Google-Maps-Link der Box
    );

    foreach ( $ss_fields as $key => $type ) {
        register_post_meta( 'tgs_sportstaette', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ) );
    }

    // Abteilung meta fields
    $abt_fields = array(
        '_tgs_abt_icon'     => 'string',
        '_tgs_abt_leitung'  => 'string',
        '_tgs_abt_email'    => 'string',
        '_tgs_abt_stv'      => 'string',
    );

    foreach ( $abt_fields as $key => $type ) {
        register_post_meta( 'tgs_abteilung', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ) );
    }
}
add_action( 'init', 'tgs_register_meta_fields' );

/**
 * Add meta boxes for Kurs editing (classic fallback)
 */
function tgs_add_kurs_meta_boxes() {
    add_meta_box(
        'tgs_kurs_text',
        'Kursbeschreibung',
        'tgs_kurs_text_meta_box_html',
        'tgs_kurs',
        'normal',
        'high'
    );
    add_meta_box(
        'tgs_kurs_details',
        'Kursdetails',
        'tgs_kurs_meta_box_html',
        'tgs_kurs',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'tgs_add_kurs_meta_boxes' );

/** WordPress-Mediathek auf der Kurs-Bearbeitungsseite laden (für den Foto-Picker). */
function tgs_kurs_admin_media( $hook ) {
    if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        $screen = get_current_screen();
        if ( $screen && $screen->post_type === 'tgs_kurs' ) {
            wp_enqueue_media();
        }
    }
}
add_action( 'admin_enqueue_scripts', 'tgs_kurs_admin_media' );

/**
 * Meta box "Kursbeschreibung" — die strukturierten Textfelder (festes Template).
 */
function tgs_kurs_text_meta_box_html( $post ) {
    wp_nonce_field( 'tgs_kurs_text', 'tgs_kurs_text_nonce' );
    $kurz  = get_post_meta( $post->ID, '_tgs_kurs_kurz', true );
    $ueber = get_post_meta( $post->ID, '_tgs_kurs_ueber', true );
    $high  = get_post_meta( $post->ID, '_tgs_kurs_highlights', true );
    ?>
    <p style="margin-top:0;color:#666;">Diese Felder ergeben den einheitlichen Aufbau jeder Kursseite. Ein Beitragsbild (rechts) zeigt zusätzlich ein Titelbild.</p>
    <table class="form-table"><tbody>
        <tr><th><label for="_tgs_kurs_kurz">Kurzbeschreibung</label></th>
            <td><textarea id="_tgs_kurs_kurz" name="_tgs_kurs_kurz" rows="2" class="large-text" placeholder="1–2 Sätze, erscheinen als Einleitung unter dem Titel."><?php echo esc_textarea( $kurz ); ?></textarea></td></tr>
        <tr><th><label for="_tgs_kurs_ueber">Über den Kurs</label></th>
            <td><textarea id="_tgs_kurs_ueber" name="_tgs_kurs_ueber" rows="6" class="large-text" placeholder="Ausführliche Beschreibung. Leerzeile = neuer Absatz."><?php echo esc_textarea( $ueber ); ?></textarea>
            <p class="description">Leer lassen, um den klassischen Inhaltsbereich (oben) zu verwenden.</p></td></tr>
        <tr><th><label for="_tgs_kurs_highlights">Das erwartet dich</label></th>
            <td><textarea id="_tgs_kurs_highlights" name="_tgs_kurs_highlights" rows="4" class="large-text" placeholder="Ein Stichpunkt pro Zeile, z. B.:&#10;Sanfter Einstieg&#10;Kleine Gruppe&#10;Persönliche Betreuung"><?php echo esc_textarea( $high ); ?></textarea>
            <p class="description">Eine Zeile = ein Häkchen-Punkt.</p></td></tr>
    </tbody></table>
    <?php
}

function tgs_save_kurs_text_meta( $post_id ) {
    if ( ! isset( $_POST['tgs_kurs_text_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_kurs_text_nonce'], 'tgs_kurs_text' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    foreach ( array( '_tgs_kurs_kurz', '_tgs_kurs_ueber', '_tgs_kurs_highlights' ) as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_textarea_field( $_POST[ $key ] ) );
        }
    }
}
add_action( 'save_post_tgs_kurs', 'tgs_save_kurs_text_meta' );

function tgs_kurs_meta_box_html( $post ) {
    wp_nonce_field( 'tgs_kurs_meta', 'tgs_kurs_meta_nonce' );

    $fields = array(
        '_tgs_wochentag'       => array( 'label' => 'Wochentag', 'type' => 'select', 'options' => array( 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So' ) ),
        '_tgs_uhrzeit'         => array( 'label' => 'Uhrzeit (Start)', 'type' => 'time' ),
        '_tgs_uhrzeit_ende'    => array( 'label' => 'Uhrzeit (Ende)', 'type' => 'time' ),
        '_tgs_ort'             => array( 'label' => 'Ort / Halle', 'type' => 'ortpicker', 'id_key' => '_tgs_ort_id' ),
        '_tgs_saison'          => array( 'label' => 'Saisonabhängig?', 'type' => 'select', 'options' => array( 'ja' => 'Ja – im Winter andere Zeit/Ort', 'nein' => 'Nein' ), 'note' => 'Wochentag/Uhrzeit/Ort oben gelten als <strong>Sommer</strong>. Bei „Ja" die Winter-Felder darunter ausfüllen.' ),
        '_tgs_winter_wochentag'    => array( 'label' => '❄ Winter: Wochentag', 'type' => 'select', 'options' => array( 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So' ), 'row' => 'saison', 'note' => 'leer = wie im Sommer' ),
        '_tgs_winter_uhrzeit'      => array( 'label' => '❄ Winter: Uhrzeit (Start)', 'type' => 'time', 'row' => 'saison' ),
        '_tgs_winter_uhrzeit_ende' => array( 'label' => '❄ Winter: Uhrzeit (Ende)', 'type' => 'time', 'row' => 'saison' ),
        '_tgs_winter_ort'          => array( 'label' => '❄ Winter: Ort / Halle', 'type' => 'ortpicker', 'id_key' => '_tgs_winter_ort_id', 'row' => 'saison' ),
        '_tgs_winter_pause'        => array( 'label' => '❄ Winter: Betrieb', 'type' => 'select', 'options' => array( 'nein' => 'findet statt (Winter-Zeit/Ort)', 'ja' => 'pausiert – kein Wintertraining' ), 'row' => 'saison' ),
        '_tgs_winter_von'          => array( 'label' => '❄ Winter von', 'type' => 'monthselect', 'default' => 10, 'row' => 'saison' ),
        '_tgs_winter_bis'          => array( 'label' => '❄ Winter bis', 'type' => 'monthselect', 'default' => 3, 'row' => 'saison', 'note' => 'Standard: Oktober bis März.' ),
        '_tgs_max_teilnehmer'  => array( 'label' => 'Max. Teilnehmer', 'type' => 'number', 'placeholder' => 'leer = unbegrenzt' ),
        '_tgs_kurs_anmeldung'  => array( 'label' => 'Anmeldung', 'type' => 'select', 'options' => array( 'pflicht' => 'Anmeldung erforderlich (mit Warteliste)', 'offen' => 'Offener Kurs – keine Anmeldung nötig' ) ),
        '_tgs_kurs_fragen'     => array( 'label' => 'Rückfragen', 'type' => 'select', 'options' => array( '' => 'Ja – Nachfragen an die Kursleitung erlauben', 'aus' => 'Nein – keine Frage-Funktion zeigen' ) ),
        '_tgs_kurs_kinder'     => array( 'label' => 'Kurs für Kinder', 'type' => 'select', 'options' => array( '1' => 'Ja – Anmeldung mit Kind + Elternkontakt', '0' => 'Nein' ) ),
        '_tgs_kurs_alter_min'  => array( 'label' => 'Mindestalter (Jahre)', 'type' => 'number', 'placeholder' => 'leer = keine Grenze' ),
        '_tgs_kurs_alter_max'  => array( 'label' => 'Höchstalter (Jahre)', 'type' => 'number', 'placeholder' => 'leer = keine Grenze' ),
        '_tgs_bewertung_aktiv'    => array( 'label' => 'Bewertungen', 'type' => 'select', 'options' => array( '1' => 'Aktiviert (Teilnehmer können bewerten)', '0' => 'Aus' ) ),
        '_tgs_bewertung_anzeigen' => array( 'label' => 'Bewertungen öffentlich zeigen', 'type' => 'select', 'options' => array( '1' => 'Ja – auf der Kursseite anzeigen', '0' => 'Nein – nur intern' ) ),
        '_tgs_zielgruppe'      => array( 'label' => 'Zielgruppe', 'type' => 'checkboxes', 'options' => tgs_zielgruppen() ),
        // Kursleitung: eigene Box (siehe inc/trainer.php) — hier bewusst raus.
        '_tgs_mitbringen'      => array( 'label' => 'Mitbringen', 'type' => 'text', 'placeholder' => 'z.B. Yogamatte, Sportkleidung' ),
    );

    echo '<table class="form-table"><tbody>';
    foreach ( $fields as $key => $field ) {
        $value = get_post_meta( $post->ID, $key, true );
        $row_attr = ! empty( $field['row'] ) ? ' data-tgs-row="' . esc_attr( $field['row'] ) . '"' : '';
        echo '<tr' . $row_attr . '><th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';

        if ( $field['type'] === 'checkboxes' ) {
            $selected = is_array( $value ) ? $value : array();
            echo '<fieldset>';
            foreach ( $field['options'] as $opt_val => $opt_label ) {
                printf(
                    '<label style="display:inline-block;margin:0 16px 6px 0;"><input type="checkbox" name="%s[]" value="%s"%s> %s</label>',
                    esc_attr( $key ),
                    esc_attr( $opt_val ),
                    checked( in_array( $opt_val, $selected, true ), true, false ),
                    esc_html( $opt_label )
                );
            }
            echo '</fieldset>';
            if ( is_string( $value ) && $value !== '' ) {
                echo '<p class="description">Bisheriger Freitext: <em>' . esc_html( $value ) . '</em> — bitte oben passend ankreuzen und speichern.</p>';
            } else {
                echo '<p class="description">Mehrfachauswahl möglich. Diese Auswahl können Besucher zum Filtern der Kursübersicht nutzen.</p>';
            }
        } elseif ( $field['type'] === 'select' ) {
            echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
            echo '<option value="">— auswählen —</option>';
            foreach ( $field['options'] as $opt_val => $opt_label ) {
                if ( is_int( $opt_val ) ) $opt_val = $opt_label;
                printf( '<option value="%s"%s>%s</option>', esc_attr( $opt_val ), selected( $value, $opt_val, false ), esc_html( $opt_label ) );
            }
            echo '</select>';
        } elseif ( $field['type'] === 'monthselect' ) {
            $eff = ( $value === '' && isset( $field['default'] ) ) ? (int) $field['default'] : (int) $value;
            $monate = array( 1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember' );
            echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
            foreach ( $monate as $mn => $ml ) {
                printf( '<option value="%d"%s>%s</option>', $mn, selected( $eff, $mn, false ), esc_html( $ml ) );
            }
            echo '</select>';
        } elseif ( $field['type'] === 'ortpicker' ) {
            $id_key   = $field['id_key'];
            $sel_id   = (int) get_post_meta( $post->ID, $id_key, true );
            $staetten = get_posts( array( 'post_type' => 'tgs_sportstaette', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) );
            echo '<select id="' . esc_attr( $id_key ) . '" name="' . esc_attr( $id_key ) . '" class="tgs-ortpicker" data-freitext="' . esc_attr( $key ) . '">';
            echo '<option value="">— Freitext (Feld darunter) —</option>';
            foreach ( $staetten as $st ) {
                printf( '<option value="%d"%s>%s</option>', (int) $st->ID, selected( $sel_id, $st->ID, false ), esc_html( $st->post_title ) );
            }
            echo '</select>';
            printf(
                '<input type="text" id="%s" name="%s" value="%s" class="regular-text" placeholder="…oder Ort frei eingeben" style="display:block;margin-top:6px;">',
                esc_attr( $key ), esc_attr( $key ), esc_attr( $value )
            );
            echo '<p class="description">Sportstätte auswählen <em>oder</em> unten frei eintippen. Bei Auswahl wird der Ort automatisch mit der Sportstätten-Seite verlinkt.</p>';
        } elseif ( $field['type'] === 'textarea' ) {
            printf(
                '<textarea id="%s" name="%s" rows="3" class="large-text" placeholder="%s">%s</textarea>',
                esc_attr( $key ), esc_attr( $key ), esc_attr( $field['placeholder'] ?? '' ), esc_textarea( $value )
            );
        } elseif ( $field['type'] === 'media' ) {
            $att_id = (int) get_post_meta( $post->ID, $key, true );
            echo '<div class="tgs-media-field">';
            echo '<input type="hidden" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $att_id ) . '">';
            echo '<div class="tgs-media-preview" style="margin-bottom:8px;">' . ( $att_id ? wp_get_attachment_image( $att_id, array( 90, 90 ) ) : '' ) . '</div>';
            echo '<button type="button" class="button tgs-media-choose">Foto wählen</button> ';
            echo '<button type="button" class="button tgs-media-remove"' . ( $att_id ? '' : ' style="display:none;"' ) . '>Entfernen</button>';
            echo '</div>';
        } else {
            printf(
                '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" placeholder="%s">',
                esc_attr( $field['type'] ),
                esc_attr( $key ),
                esc_attr( $key ),
                esc_attr( $value ),
                esc_attr( $field['placeholder'] ?? '' )
            );
        }

        if ( ! empty( $field['note'] ) ) {
            echo '<p class="description">' . wp_kses_post( $field['note'] ) . '</p>';
        }

        echo '</td></tr>';
    }
    echo '</tbody></table>';
    // Winter-Felder nur einblenden, wenn „Saisonabhängig" = Ja
    ?>
    <script>
    (function(){
        var sel = document.getElementById('_tgs_saison');
        if ( sel ) {
            var rows = document.querySelectorAll('tr[data-tgs-row="saison"]');
            var updS = function(){ var on = sel.value === 'ja'; rows.forEach(function(r){ r.style.display = on ? '' : 'none'; }); };
            sel.addEventListener('change', updS); updS();
        }
        document.querySelectorAll('select.tgs-ortpicker').forEach(function(pk){
            var txt = document.getElementById( pk.getAttribute('data-freitext') );
            if ( ! txt ) return;
            var updO = function(){ var chosen = pk.value !== ''; txt.disabled = chosen; txt.style.opacity = chosen ? '.5' : '1'; };
            pk.addEventListener('change', updO); updO();
        });
        // Foto-Picker (WordPress-Mediathek)
        document.querySelectorAll('.tgs-media-field').forEach(function(f){
            var input = f.querySelector('input[type=hidden]');
            var prev  = f.querySelector('.tgs-media-preview');
            var rem   = f.querySelector('.tgs-media-remove');
            var frame;
            f.querySelector('.tgs-media-choose').addEventListener('click', function(e){
                e.preventDefault();
                if ( frame ) { frame.open(); return; }
                if ( ! window.wp || ! wp.media ) return;
                frame = wp.media({ title: 'Foto wählen', button: { text: 'Übernehmen' }, multiple: false, library: { type: 'image' } });
                frame.on('select', function(){
                    var a = frame.state().get('selection').first().toJSON();
                    input.value = a.id;
                    var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
                    prev.innerHTML = '<img src="' + url + '" style="max-width:90px;height:auto;border-radius:6px;">';
                    rem.style.display = '';
                });
                frame.open();
            });
            rem.addEventListener('click', function(e){ e.preventDefault(); input.value = ''; prev.innerHTML = ''; rem.style.display = 'none'; });
        });
    })();
    </script>
    <?php
}

function tgs_save_kurs_meta( $post_id ) {
    if ( ! isset( $_POST['tgs_kurs_meta_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_kurs_meta_nonce'], 'tgs_kurs_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = array(
        '_tgs_wochentag', '_tgs_uhrzeit', '_tgs_uhrzeit_ende', '_tgs_ort',
        '_tgs_saison', '_tgs_winter_wochentag', '_tgs_winter_uhrzeit', '_tgs_winter_uhrzeit_ende',
        '_tgs_winter_ort', '_tgs_winter_pause', '_tgs_winter_von', '_tgs_winter_bis',
        '_tgs_status', '_tgs_max_teilnehmer', '_tgs_kurs_anmeldung', '_tgs_kurs_fragen',
        '_tgs_bewertung_aktiv', '_tgs_bewertung_anzeigen', '_tgs_kurs_kinder',
        '_tgs_kurs_alter_min', '_tgs_kurs_alter_max',
        '_tgs_ansprechpartner', '_tgs_ansprechpartner_email', '_tgs_ansprechpartner_tel', '_tgs_ansprechpartner_foto',
        '_tgs_mitbringen',
    );

    foreach ( $fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
        }
    }

    // Vorstellung: Textarea → Zeilenumbrüche erhalten.
    if ( isset( $_POST['_tgs_ansprechpartner_text'] ) ) {
        update_post_meta( $post_id, '_tgs_ansprechpartner_text', sanitize_textarea_field( $_POST['_tgs_ansprechpartner_text'] ) );
    }

    // Ort-Picker: gewählte Sportstätte → deren Titel als Ort + verknüpfte ID; sonst Freitext (steht schon), ID leeren
    foreach ( array( '_tgs_ort' => '_tgs_ort_id', '_tgs_winter_ort' => '_tgs_winter_ort_id' ) as $ort_key => $id_key ) {
        $sid = isset( $_POST[ $id_key ] ) ? intval( $_POST[ $id_key ] ) : 0;
        if ( $sid > 0 && get_post_type( $sid ) === 'tgs_sportstaette' ) {
            update_post_meta( $post_id, $ort_key, get_the_title( $sid ) );
            update_post_meta( $post_id, $id_key, $sid );
        } else {
            update_post_meta( $post_id, $id_key, 0 );
        }
    }

    // Zielgruppe (Mehrfach-Checkboxen) — nur gültige Slugs speichern
    $valid_zg = array_keys( tgs_zielgruppen() );
    $posted   = isset( $_POST['_tgs_zielgruppe'] ) && is_array( $_POST['_tgs_zielgruppe'] ) ? $_POST['_tgs_zielgruppe'] : array();
    $clean_zg = array_values( array_intersect( $valid_zg, array_map( 'sanitize_key', $posted ) ) );
    update_post_meta( $post_id, '_tgs_zielgruppe', $clean_zg );
}
add_action( 'save_post_tgs_kurs', 'tgs_save_kurs_meta' );

/**
 * Meta box for Sportstätte editing
 */
function tgs_add_sportstaette_meta_boxes() {
    add_meta_box(
        'tgs_ss_details',
        'Standort & Details',
        'tgs_sportstaette_meta_box_html',
        'tgs_sportstaette',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'tgs_add_sportstaette_meta_boxes' );

function tgs_sportstaette_meta_box_html( $post ) {
    wp_nonce_field( 'tgs_ss_meta', 'tgs_ss_meta_nonce' );

    $fields = array(
        '_tgs_ss_typ'           => array( 'label' => 'Art der Sportstätte', 'type' => 'text', 'placeholder' => 'z.B. Außenanlage / Fitnessplatz, Sporthalle' ),
        '_tgs_adresse'          => array( 'label' => 'Adresse (Straße)', 'type' => 'text', 'placeholder' => 'z.B. Sportplatzstraße 15' ),
        '_tgs_plz_ort'          => array( 'label' => 'PLZ & Ort', 'type' => 'text', 'placeholder' => 'z.B. 65719 Hofheim-Langenhain' ),
        '_tgs_maps_link'        => array( 'label' => 'Google-Maps-Link', 'type' => 'url', 'placeholder' => 'https://maps.app.goo.gl/…' ),
        '_tgs_ss_zugang'        => array( 'label' => 'Zugang / Öffnung', 'type' => 'text', 'placeholder' => 'z.B. 24/7 frei zugänglich' ),
        '_tgs_ss_kosten'        => array( 'label' => 'Kosten / Nutzung', 'type' => 'text', 'placeholder' => 'z.B. Kostenlos – keine Mitgliedschaft nötig' ),
        '_tgs_ausstattung'      => array( 'label' => 'Ausstattung', 'type' => 'textarea', 'placeholder' => "Ein Punkt pro Zeile, z.B.:\nReck & Barren\nKlimmzugstangen\nLeitern zum Hangeln\nFreifläche für Yoga & Gymnastik" ),
        '_tgs_parkplaetze'      => array( 'label' => 'Parkplätze', 'type' => 'text', 'placeholder' => 'z.B. Parkplätze am Sportplatz' ),
        '_tgs_barrierefreiheit' => array( 'label' => 'Barrierefreiheit', 'type' => 'text', 'placeholder' => 'z.B. Ebenerdig zugänglich' ),
        '_tgs_ss_app_name'      => array( 'label' => 'Trainings-App (Name, optional)', 'type' => 'text', 'placeholder' => 'z.B. KOMPAN Outdoor Fitness' ),
        '_tgs_ss_app_desc'      => array( 'label' => 'App-Beschreibung', 'type' => 'text', 'placeholder' => 'z.B. Kostenlose Übungsanleitungen & Workouts für die Geräte' ),
        '_tgs_ss_app_ios'       => array( 'label' => 'App-Store-Link (iOS)', 'type' => 'url', 'placeholder' => 'https://apps.apple.com/…' ),
        '_tgs_ss_app_android'   => array( 'label' => 'Google-Play-Link', 'type' => 'url', 'placeholder' => 'https://play.google.com/…' ),
        '_tgs_ss_nahkauf'       => array( 'label' => 'Nahkauf Box in der Nähe', 'type' => 'text', 'placeholder' => 'Entfernung/Hinweis, z.B. „direkt am Platz" — leer lassen = ausblenden' ),
        '_tgs_ss_nahkauf_link'  => array( 'label' => 'Nahkauf Box: Google-Maps-Link', 'type' => 'url', 'placeholder' => 'https://maps.app.goo.gl/… (Teilen-Link der Box, optional)' ),
    );

    echo '<table class="form-table"><tbody>';
    foreach ( $fields as $key => $field ) {
        $value = get_post_meta( $post->ID, $key, true );
        $row_attr = ! empty( $field['row'] ) ? ' data-tgs-row="' . esc_attr( $field['row'] ) . '"' : '';
        echo '<tr' . $row_attr . '><th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';

        if ( $field['type'] === 'textarea' ) {
            printf(
                '<textarea id="%s" name="%s" rows="5" class="large-text" placeholder="%s">%s</textarea>',
                esc_attr( $key ), esc_attr( $key ), esc_attr( $field['placeholder'] ), esc_textarea( $value )
            );
            echo '<p class="description">Eine Zeile = ein Listenpunkt auf der Seite.</p>';
        } else {
            printf(
                '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" placeholder="%s">',
                esc_attr( $field['type'] ), esc_attr( $key ), esc_attr( $key ),
                esc_attr( $value ), esc_attr( $field['placeholder'] )
            );
        }
        echo '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p class="description">Tipp: Ein <strong>Beitragsbild</strong> (rechts) wird als großes Titelbild oben genutzt. Weitere Fotos als <strong>Galerie</strong> direkt in den Textbereich einfügen.</p>';
}

function tgs_save_sportstaette_meta( $post_id ) {
    if ( ! isset( $_POST['tgs_ss_meta_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_ss_meta_nonce'], 'tgs_ss_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $text_fields = array( '_tgs_ss_typ', '_tgs_adresse', '_tgs_plz_ort', '_tgs_ss_zugang', '_tgs_ss_kosten', '_tgs_parkplaetze', '_tgs_barrierefreiheit', '_tgs_ss_app_name', '_tgs_ss_app_desc', '_tgs_ss_nahkauf' );
    foreach ( $text_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
        }
    }
    $url_fields = array( '_tgs_maps_link', '_tgs_ss_app_ios', '_tgs_ss_app_android', '_tgs_ss_nahkauf_link' );
    foreach ( $url_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, esc_url_raw( trim( $_POST[ $key ] ) ) );
        }
    }
    if ( isset( $_POST['_tgs_ausstattung'] ) ) {
        update_post_meta( $post_id, '_tgs_ausstattung', sanitize_textarea_field( $_POST['_tgs_ausstattung'] ) );
    }
}
add_action( 'save_post_tgs_sportstaette', 'tgs_save_sportstaette_meta' );
