<?php
/**
 * Custom Post Type: Sportstätten
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tgs_register_cpt_sportstaette() {
    register_post_type( 'tgs_sportstaette', array(
        'labels' => array(
            'name'               => 'Sportstätten',
            'singular_name'      => 'Sportstätte',
            'menu_name'          => 'Sportstätten',
            'add_new'            => 'Neue Sportstätte',
            'add_new_item'       => 'Neue Sportstätte anlegen',
            'edit_item'          => 'Sportstätte bearbeiten',
            'all_items'          => 'Alle Sportstätten',
        ),
        'public'             => true,
        'show_in_rest'       => true,
        'rewrite'            => array( 'slug' => 'sportstaetten' ),
        'menu_position'      => 6,
        'menu_icon'          => 'dashicons-location',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
        'has_archive'        => true,
    ) );
}
add_action( 'init', 'tgs_register_cpt_sportstaette' );
