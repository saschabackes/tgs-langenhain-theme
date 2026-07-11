<?php
/**
 * TGS Langenhain Theme Functions
 *
 * @package TGS_Langenhain
 * @version 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TGS_VERSION', '0.1.0' );
define( 'TGS_DIR', get_template_directory() );
define( 'TGS_URI', get_template_directory_uri() );

/**
 * Theme Setup
 */
function tgs_setup() {
    // Google Fonts
    add_editor_style( 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Libre+Baskerville:ital,wght@0,700;1,400&display=swap' );

    // Theme supports
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 100,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Register navigation menus
    register_nav_menus( array(
        'primary'   => __( 'Hauptnavigation', 'tgs-langenhain' ),
        'footer'    => __( 'Footer-Navigation', 'tgs-langenhain' ),
        'topbar'    => __( 'Topbar-Links', 'tgs-langenhain' ),
    ) );
}
add_action( 'after_setup_theme', 'tgs_setup' );

/**
 * Enqueue Styles & Scripts
 */
function tgs_enqueue_assets() {
    // Google Fonts
    wp_enqueue_style(
        'tgs-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Libre+Baskerville:ital,wght@0,700;1,400&display=swap',
        array(),
        null
    );

    // Theme styles
    wp_enqueue_style(
        'tgs-theme',
        TGS_URI . '/assets/css/theme.css',
        array(),
        TGS_VERSION
    );

    // Theme scripts
    wp_enqueue_script(
        'tgs-theme',
        TGS_URI . '/assets/js/theme.js',
        array(),
        TGS_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'tgs_enqueue_assets' );

/**
 * Load Custom Post Types and includes
 */
require_once TGS_DIR . '/inc/cpt-kurse.php';
require_once TGS_DIR . '/inc/cpt-sportstaetten.php';
require_once TGS_DIR . '/inc/cpt-abteilungen.php';
require_once TGS_DIR . '/inc/meta-fields.php';
require_once TGS_DIR . '/inc/kurs-anmeldung.php';
