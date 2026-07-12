<?php
/**
 * Meta Fields / Custom Fields for CPTs
 * Uses WordPress native meta boxes (no ACF dependency)
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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
        '_tgs_zielgruppe'      => 'string',
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

    // Sportstätte meta fields
    $ss_fields = array(
        '_tgs_adresse'      => 'string',
        '_tgs_plz_ort'      => 'string',
        '_tgs_maps_link'    => 'string',
        '_tgs_ausstattung'  => 'string',
        '_tgs_barrierefreiheit' => 'string',
        '_tgs_parkplaetze'  => 'string',
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
        '_tgs_zielgruppe'      => array( 'label' => 'Zielgruppe', 'type' => 'text', 'placeholder' => 'z.B. Erwachsene, Kinder 3-6 J.' ),
        '_tgs_ansprechpartner' => array( 'label' => 'Ansprechpartner (Name)', 'type' => 'text' ),
        '_tgs_ansprechpartner_email' => array( 'label' => 'E-Mail Ansprechpartner', 'type' => 'email' ),
        '_tgs_ansprechpartner_tel'   => array( 'label' => 'Telefon Ansprechpartner', 'type' => 'tel' ),
        '_tgs_mitbringen'      => array( 'label' => 'Mitbringen', 'type' => 'text', 'placeholder' => 'z.B. Yogamatte, Sportkleidung' ),
    );

    echo '<table class="form-table"><tbody>';
    foreach ( $fields as $key => $field ) {
        $value = get_post_meta( $post->ID, $key, true );
        echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';

        if ( $field['type'] === 'select' ) {
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
        '_tgs_status', '_tgs_max_teilnehmer', '_tgs_kurs_anmeldung', '_tgs_zielgruppe',
        '_tgs_ansprechpartner', '_tgs_ansprechpartner_email', '_tgs_ansprechpartner_tel',
        '_tgs_mitbringen',
    );

    foreach ( $fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
        }
    }
}
add_action( 'save_post_tgs_kurs', 'tgs_save_kurs_meta' );
