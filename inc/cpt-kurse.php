<?php
/**
 * Custom Post Type: Kurse
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tgs_register_cpt_kurs() {
    $labels = array(
        'name'               => 'Kurse',
        'singular_name'      => 'Kurs',
        'menu_name'          => 'Kurse',
        'add_new'            => 'Neuen Kurs anlegen',
        'add_new_item'       => 'Neuen Kurs anlegen',
        'edit_item'          => 'Kurs bearbeiten',
        'new_item'           => 'Neuer Kurs',
        'view_item'          => 'Kurs ansehen',
        'search_items'       => 'Kurse suchen',
        'not_found'          => 'Keine Kurse gefunden',
        'not_found_in_trash' => 'Keine Kurse im Papierkorb',
        'all_items'          => 'Alle Kurse',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true, // Block Editor support
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'kurse' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-calendar-alt',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'template'           => array(),
    );

    register_post_type( 'tgs_kurs', $args );

    // Taxonomy: Kurs-Kategorie
    register_taxonomy( 'tgs_kurs_kategorie', 'tgs_kurs', array(
        'labels'            => array(
            'name'          => 'Kurs-Kategorien',
            'singular_name' => 'Kurs-Kategorie',
            'add_new_item'  => 'Neue Kategorie hinzufügen',
        ),
        'public'            => true,
        'hierarchical'      => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'kurs-kategorie' ),
    ) );
}
add_action( 'init', 'tgs_register_cpt_kurs' );

/**
 * Pre-populate Kurs-Kategorien on theme activation
 */
function tgs_create_default_kurs_kategorien() {
    $kategorien = array(
        'fitness-kurse'    => 'Fitness-Kurse',
        'fitness-training' => 'Fitness-Trainings',
        'kinder-jugend'    => 'Kinder & Jugend',
        'senioren'         => 'Senioren',
        'radsport'         => 'Radsport',
    );

    foreach ( $kategorien as $slug => $name ) {
        if ( ! term_exists( $slug, 'tgs_kurs_kategorie' ) ) {
            wp_insert_term( $name, 'tgs_kurs_kategorie', array( 'slug' => $slug ) );
        }
    }
}
add_action( 'after_switch_theme', 'tgs_create_default_kurs_kategorien' );

/**
 * Customize admin columns for Kurse
 */
function tgs_kurs_admin_columns( $columns ) {
    $new_columns = array(
        'cb'            => $columns['cb'],
        'title'         => 'Kursname',
        'tgs_kategorie' => 'Kategorie',
        'tgs_wochentag' => 'Tag',
        'tgs_uhrzeit'   => 'Uhrzeit',
        'tgs_ort'       => 'Ort',
        'tgs_status'    => 'Status',
        'tgs_max_tn'    => 'Max. TN',
        'date'          => $columns['date'],
    );
    return $new_columns;
}
add_filter( 'manage_tgs_kurs_posts_columns', 'tgs_kurs_admin_columns' );

function tgs_kurs_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'tgs_kategorie':
            $terms = get_the_terms( $post_id, 'tgs_kurs_kategorie' );
            echo $terms ? esc_html( $terms[0]->name ) : '—';
            break;
        case 'tgs_wochentag':
            echo esc_html( get_post_meta( $post_id, '_tgs_wochentag', true ) ?: '—' );
            break;
        case 'tgs_uhrzeit':
            echo esc_html( get_post_meta( $post_id, '_tgs_uhrzeit', true ) ?: '—' );
            break;
        case 'tgs_ort':
            echo esc_html( get_post_meta( $post_id, '_tgs_ort', true ) ?: '—' );
            break;
        case 'tgs_status':
            $status = get_post_meta( $post_id, '_tgs_status', true );
            $label  = $status === 'warteliste' ? '⚠ Warteliste' : '✓ Freie Plätze';
            $color  = $status === 'warteliste' ? '#C07020' : '#3D5A40';
            printf( '<span style="color:%s;font-weight:600;">%s</span>', $color, $label );
            break;
        case 'tgs_max_tn':
            $max = get_post_meta( $post_id, '_tgs_max_teilnehmer', true );
            echo $max ? esc_html( $max ) : '—';
            break;
    }
}
add_action( 'manage_tgs_kurs_posts_custom_column', 'tgs_kurs_admin_column_content', 10, 2 );
