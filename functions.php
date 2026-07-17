<?php
/**
 * TGS Langenhain Theme Functions
 *
 * @package TGS_Langenhain
 * @version 0.19.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TGS_VERSION', '0.27.3' );
define( 'TGS_DIR', get_template_directory() );
define( 'TGS_URI', get_template_directory_uri() );

/**
 * Theme Setup
 */
function tgs_setup() {
    // Schriften: selbstgehostet (DSGVO – kein Google-CDN)
    add_editor_style( 'assets/css/fonts.css' );

    // Theme supports
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'post-thumbnails' ); // aktiviert das „Beitragsbild" (Hero für Sportstätten & News)
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
    // Schriften: selbstgehostet (DSGVO – kein Google-CDN)
    wp_enqueue_style(
        'tgs-fonts',
        TGS_URI . '/assets/css/fonts.css',
        array(),
        TGS_VERSION
    );

    // Theme styles
    wp_enqueue_style(
        'tgs-theme',
        TGS_URI . '/assets/css/theme.css',
        array( 'tgs-fonts' ),
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
require_once TGS_DIR . '/inc/kurs-bewertung.php';
require_once TGS_DIR . '/inc/kurs-meldungen.php';
require_once TGS_DIR . '/inc/kalender.php';
require_once TGS_DIR . '/inc/touren.php';
require_once TGS_DIR . '/inc/touren-admin.php';
require_once TGS_DIR . '/inc/touren-bewertung.php';
require_once TGS_DIR . '/inc/touren-render.php';
require_once TGS_DIR . '/inc/touren-guides.php';
require_once TGS_DIR . '/inc/teilen.php';
require_once TGS_DIR . '/inc/sport-icons.php';
require_once TGS_DIR . '/inc/mitglied.php';
require_once TGS_DIR . '/inc/gaststaette.php';
require_once TGS_DIR . '/inc/speisekarte.php';
require_once TGS_DIR . '/inc/kontakt.php';
require_once TGS_DIR . '/inc/rechtstexte.php';
require_once TGS_DIR . '/inc/news.php';
require_once TGS_DIR . '/inc/shortcodes.php';
require_once TGS_DIR . '/inc/content-blocks.php';

/**
 * Auto-Setup: Logo, Seitentitel und Untertitel bei Theme-Aktivierung
 */
function tgs_auto_setup() {
    // Seitentitel
    update_option( 'blogname', 'TGS Langenhain' );
    update_option( 'blogdescription', 'Sportverein im Taunus seit 1886' );

    // Logo in Mediathek hochladen (falls noch nicht vorhanden)
    $logo_black = TGS_DIR . '/assets/images/tgs-logo-black.png';
    $logo_white = TGS_DIR . '/assets/images/tgs-logo-white.png';

    // Schwarzes Logo als Site Logo (für helle Hintergründe / Nav)
    $existing = get_posts( array(
        'post_type'  => 'attachment',
        'title'      => 'tgs-logo-black',
        'numberposts' => 1,
    ) );

    if ( empty( $existing ) && file_exists( $logo_black ) ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_upload_bits( 'tgs-logo-black.png', null, file_get_contents( $logo_black ) );
        if ( ! $upload['error'] ) {
            $attach_id = wp_insert_attachment( array(
                'post_title'     => 'tgs-logo-black',
                'post_mime_type' => 'image/png',
                'post_status'    => 'inherit',
            ), $upload['file'] );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );
            set_theme_mod( 'custom_logo', $attach_id );
        }
    } elseif ( ! empty( $existing ) ) {
        set_theme_mod( 'custom_logo', $existing[0]->ID );
    }

    // Weißes Logo ebenfalls hochladen (für dunkle Hintergründe)
    $existing_w = get_posts( array(
        'post_type'  => 'attachment',
        'title'      => 'tgs-logo-white',
        'numberposts' => 1,
    ) );

    if ( empty( $existing_w ) && file_exists( $logo_white ) ) {
        $upload_w = wp_upload_bits( 'tgs-logo-white.png', null, file_get_contents( $logo_white ) );
        if ( ! $upload_w['error'] ) {
            $attach_id_w = wp_insert_attachment( array(
                'post_title'     => 'tgs-logo-white',
                'post_mime_type' => 'image/png',
                'post_status'    => 'inherit',
            ), $upload_w['file'] );
            wp_generate_attachment_metadata( $attach_id_w, $upload_w['file'] );
            // Store white logo ID for use in templates
            update_option( 'tgs_logo_white_id', $attach_id_w );
        }
    }
}
add_action( 'after_switch_theme', 'tgs_auto_setup' );

/**
 * Helper: White logo URL (für Hero/Footer)
 */
function tgs_get_white_logo_url() {
    $id = get_option( 'tgs_logo_white_id' );
    if ( $id ) {
        $url = wp_get_attachment_url( $id );
        if ( $url ) return $url;
    }
    // Fallback: direkt aus Theme-Ordner
    return TGS_URI . '/assets/images/tgs-logo-white.png';
}

/**
 * Shortcode [tgs_logo] — rendert das Logo
 * Attribute: color="white|black" height="130"
 */
function tgs_shortcode_logo( $atts ) {
    $atts = shortcode_atts( array(
        'color'  => 'black',
        'height' => '44',
        'class'  => '',
    ), $atts );

    if ( $atts['color'] === 'white' ) {
        $url = tgs_get_white_logo_url();
    } else {
        $logo_id = get_theme_mod( 'custom_logo' );
        $url = $logo_id ? wp_get_attachment_url( $logo_id ) : TGS_URI . '/assets/images/tgs-logo-black.png';
    }

    return sprintf(
        '<img src="%s" alt="TGS 1886 Langenhain e.V." height="%s" style="height:%spx;width:auto;display:block;" class="%s">',
        esc_url( $url ),
        esc_attr( $atts['height'] ),
        esc_attr( $atts['height'] ),
        esc_attr( $atts['class'] )
    );
}
add_shortcode( 'tgs_logo', 'tgs_shortcode_logo' );

/**
 * Entfernt Zwischenraum zwischen HTML-Tags in Shortcode-Ausgaben.
 *
 * Zentrale Lösung gegen das wpautop-Problem: Zeilenumbrüche/Einrückungen
 * zwischen Inline-/Block-Tags würden sonst zu ungewollten <br>/<p> führen
 * (Label rutscht unter Input, Karten „verrutschen"). Nur Whitespace, der
 * direkt zwischen `>` und `<` steht, wird kollabiert – Text bleibt unberührt.
 *
 * Verwendung: `return tgs_strip_ws( ob_get_clean() );`
 *
 * @param string $html
 * @return string
 */
function tgs_strip_ws( $html ) {
    return trim( preg_replace( '/>\s+</', '><', (string) $html ) );
}

/**
 * Mailto-Link mit Spam-Schutz gegen Crawler.
 *
 * Nutzt WordPress' antispambot(): die Adresse wird in zufällige HTML-Entities
 * verschleiert (schlägt die üblichen Harvester ab) – ganz ohne JavaScript,
 * Copy-&-Paste und Screenreader funktionieren normal.
 *
 * @param string $email  E-Mail-Adresse.
 * @param string $label  Optionaler sichtbarer Text; leer = (verschleierte) Adresse.
 * @param string $attr   Optionale zusätzliche Attribute für das <a> (z. B. 'class="x"').
 * @return string        Fertiges <a>…</a> oder '' bei ungültiger Adresse.
 */
function tgs_mail_link( $email, $label = '', $attr = '' ) {
    $email = trim( (string) $email );
    if ( $email === '' || ! is_email( $email ) ) return '';
    $href = antispambot( 'mailto:' . $email );
    $text = ( $label !== '' ) ? esc_html( $label ) : antispambot( $email );
    return '<a href="' . $href . '"' . ( $attr ? ' ' . $attr : '' ) . '>' . $text . '</a>';
}

/**
 * Trainer-/Kursleiter-Name crawler-geschützt ausgeben.
 *
 * Der Klartext-Name steht NICHT im HTML: Er wird base64-kodiert in ein
 * data-Attribut gelegt und per JavaScript (theme.js) eingesetzt. Ohne
 * JavaScript erscheint der Fallback. So lässt sich der Name nicht einfach
 * mit dem (neutral betitelten) Foto zu einem Profil verknüpfen.
 *
 * @param string $name
 * @param string $fallback  Sichtbarer Text ohne JS.
 * @return string
 */
function tgs_trainer_name_html( $name, $fallback = 'Kursleitung' ) {
    $name = trim( (string) $name );
    if ( $name === '' ) return '';
    return '<span class="tgs-guard-name" data-n="' . esc_attr( base64_encode( $name ) ) . '">' . esc_html( $fallback ) . '</span>';
}

/**
 * Prevent wpautop from breaking shortcode output
 */
function tgs_fix_shortcode_wpautop( $content ) {
    $shortcodes = array(
        'tgs_kurstabelle', 'tgs_abteilungen', 'tgs_ansprechpartner', 
        'tgs_sponsoren', 'tgs_kurs_detail', 'tgs_kurse_in_ort',
        'tgs_navigation', 'tgs_logo', 'tgs_anmeldung', 'tgs_kurs_status',
        'tgs_sportstaette_detail', 'tgs_sportstaetten_liste',
        'tgs_abteilung_detail', 'tgs_abteilungen_detail_liste',
        'tgs_chips', 'tgs_infobox', 'tgs_gruppen', 'tgs_cta_box', 'tgs_whatsapp', 'tgs_hsg', 'tgs_kurs_bewertungen', 'tgs_kurs_meldungen', 'tgs_breadcrumb', 'tgs_beitraege', 'tgs_mitglied_werden', 'tgs_gaststaette', 'tgs_speisekarte', 'tgs_kontakt', 'tgs_impressum', 'tgs_datenschutz', 'tgs_news_detail', 'tgs_news_liste', 'tgs_news_teaser', 'tgs_kalender_abo', 'tgs_tour_detail', 'tgs_touren', 'tgs_tour_tipp', 'tgs_guide_detail', 'tgs_teilen',
    );
    
    foreach ( $shortcodes as $sc ) {
        $content = str_replace( 
            array( '<p>[' . $sc, $sc . ']</p>' ),
            array( '[' . $sc, $sc . ']' ),
            $content
        );
    }
    
    return $content;
}
add_filter( 'the_content', 'tgs_fix_shortcode_wpautop', 9 );
add_filter( 'widget_text_content', 'tgs_fix_shortcode_wpautop', 9 );
