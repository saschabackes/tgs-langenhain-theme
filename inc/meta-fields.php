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
        '_tgs_wochentag'       => 'string',
        '_tgs_uhrzeit'         => 'string',
        '_tgs_uhrzeit_ende'    => 'string',
        '_tgs_ort'             => 'string',
        '_tgs_ort_id'          => 'integer',  // Link to tgs_sportstaette
        '_tgs_status'          => 'string',   // 'frei' or 'warteliste'
        '_tgs_max_teilnehmer'  => 'integer',
        '_tgs_kurs_anmeldung'  => 'string',   // '' / 'pflicht' or 'offen'
        '_tgs_bewertung_aktiv'    => 'string', // '1' = an
        '_tgs_bewertung_anzeigen' => 'string', // '1' = öffentlich zeigen
        '_tgs_kurs_kinder'        => 'string', // '1' = Kinderkurs (Kind + Elternkontakt)
        '_tgs_ansprechpartner' => 'string',
        '_tgs_ansprechpartner_email' => 'string',
        '_tgs_ansprechpartner_tel'   => 'string',
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
        'tgs_kurs_details',
        'Kursdetails',
        'tgs_kurs_meta_box_html',
        'tgs_kurs',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'tgs_add_kurs_meta_boxes' );

function tgs_kurs_meta_box_html( $post ) {
    wp_nonce_field( 'tgs_kurs_meta', 'tgs_kurs_meta_nonce' );

    $fields = array(
        '_tgs_wochentag'       => array( 'label' => 'Wochentag', 'type' => 'select', 'options' => array( 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So' ) ),
        '_tgs_uhrzeit'         => array( 'label' => 'Uhrzeit (Start)', 'type' => 'time' ),
        '_tgs_uhrzeit_ende'    => array( 'label' => 'Uhrzeit (Ende)', 'type' => 'time' ),
        '_tgs_ort'             => array( 'label' => 'Ort / Halle', 'type' => 'text', 'placeholder' => 'z.B. Wilhelm-Busch-Halle' ),
        '_tgs_max_teilnehmer'  => array( 'label' => 'Max. Teilnehmer', 'type' => 'number', 'placeholder' => 'leer = unbegrenzt' ),
        '_tgs_kurs_anmeldung'  => array( 'label' => 'Anmeldung', 'type' => 'select', 'options' => array( 'pflicht' => 'Anmeldung erforderlich (mit Warteliste)', 'offen' => 'Offener Kurs – keine Anmeldung nötig' ) ),
        '_tgs_kurs_kinder'     => array( 'label' => 'Kurs für Kinder', 'type' => 'select', 'options' => array( '1' => 'Ja – Anmeldung mit Kind + Elternkontakt', '0' => 'Nein' ) ),
        '_tgs_bewertung_aktiv'    => array( 'label' => 'Bewertungen', 'type' => 'select', 'options' => array( '1' => 'Aktiviert (Teilnehmer können bewerten)', '0' => 'Aus' ) ),
        '_tgs_bewertung_anzeigen' => array( 'label' => 'Bewertungen öffentlich zeigen', 'type' => 'select', 'options' => array( '1' => 'Ja – auf der Kursseite anzeigen', '0' => 'Nein – nur intern' ) ),
        '_tgs_zielgruppe'      => array( 'label' => 'Zielgruppe', 'type' => 'checkboxes', 'options' => tgs_zielgruppen() ),
        '_tgs_ansprechpartner' => array( 'label' => 'Ansprechpartner (Name)', 'type' => 'text' ),
        '_tgs_ansprechpartner_email' => array( 'label' => 'E-Mail Ansprechpartner', 'type' => 'email' ),
        '_tgs_ansprechpartner_tel'   => array( 'label' => 'Telefon Ansprechpartner', 'type' => 'tel' ),
        '_tgs_mitbringen'      => array( 'label' => 'Mitbringen', 'type' => 'text', 'placeholder' => 'z.B. Yogamatte, Sportkleidung' ),
    );

    echo '<table class="form-table"><tbody>';
    foreach ( $fields as $key => $field ) {
        $value = get_post_meta( $post->ID, $key, true );
        echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';

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

        echo '</td></tr>';
    }
    echo '</tbody></table>';
}

function tgs_save_kurs_meta( $post_id ) {
    if ( ! isset( $_POST['tgs_kurs_meta_nonce'] ) || ! wp_verify_nonce( $_POST['tgs_kurs_meta_nonce'], 'tgs_kurs_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = array(
        '_tgs_wochentag', '_tgs_uhrzeit', '_tgs_uhrzeit_ende', '_tgs_ort',
        '_tgs_status', '_tgs_max_teilnehmer', '_tgs_kurs_anmeldung',
        '_tgs_bewertung_aktiv', '_tgs_bewertung_anzeigen', '_tgs_kurs_kinder',
        '_tgs_ansprechpartner', '_tgs_ansprechpartner_email', '_tgs_ansprechpartner_tel',
        '_tgs_mitbringen',
    );

    foreach ( $fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
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
    );

    echo '<table class="form-table"><tbody>';
    foreach ( $fields as $key => $field ) {
        $value = get_post_meta( $post->ID, $key, true );
        echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';

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

    $text_fields = array( '_tgs_ss_typ', '_tgs_adresse', '_tgs_plz_ort', '_tgs_ss_zugang', '_tgs_ss_kosten', '_tgs_parkplaetze', '_tgs_barrierefreiheit', '_tgs_ss_app_name', '_tgs_ss_app_desc' );
    foreach ( $text_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
        }
    }
    $url_fields = array( '_tgs_maps_link', '_tgs_ss_app_ios', '_tgs_ss_app_android' );
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
