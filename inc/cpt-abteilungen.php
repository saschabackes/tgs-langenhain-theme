<?php
/**
 * Custom Post Type: Abteilungen
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tgs_register_cpt_abteilung() {
    register_post_type( 'tgs_abteilung', array(
        'labels' => array(
            'name'               => 'Abteilungen',
            'singular_name'      => 'Abteilung',
            'menu_name'          => 'Abteilungen',
            'add_new'            => 'Neue Abteilung',
            'add_new_item'       => 'Neue Abteilung anlegen',
            'edit_item'          => 'Abteilung bearbeiten',
            'all_items'          => 'Alle Abteilungen',
        ),
        'public'             => true,
        'show_in_rest'       => true,
        'rewrite'            => array( 'slug' => 'abteilungen' ),
        'menu_position'      => 7,
        'menu_icon'          => 'dashicons-groups',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
        'has_archive'        => true,
    ) );
}
add_action( 'init', 'tgs_register_cpt_abteilung' );
