<?php
/**
 * Wiederverwendbare Content-Bausteine (Shortcodes) für Abteilungs- und Inhaltsseiten.
 *
 * [tgs_chips]A | B | C[/tgs_chips]                         — Fakten-Chip-Reihe
 * [tgs_infobox][tgs_infospalte titel="" gross=""]…[/tgs_infospalte]…[/tgs_infobox]
 * [tgs_gruppen][tgs_gruppe name="" grad="" hinweis=""]…[/tgs_gruppe]…[/tgs_gruppen]
 * [tgs_cta_box titel="" text="" button="" url="" farbe="gruen|whatsapp"]
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Entfernt wpautop-Artefakte (<p>…</p>) aus Container-Shortcode-Inhalten.
 * <br>, <strong> etc. bleiben erhalten — Inhalte bitte je Zeile schreiben.
 */
function tgs_clean_shortcode_content( $content ) {
    $content = preg_replace( '/<\/?p[^>]*>/', '', (string) $content );
    return trim( $content );
}

/**
 * [tgs_chips] — Reihe aus Fakten-Chips, Einträge mit | getrennt.
 */
function tgs_shortcode_chips( $atts, $content = '' ) {
    $raw = tgs_clean_shortcode_content( wp_strip_all_tags( $content ) );
    if ( $raw === '' ) return '';
    $items = array_filter( array_map( 'trim', explode( '|', $raw ) ) );
    if ( empty( $items ) ) return '';
    $html = '<div class="tgs-chips">';
    foreach ( $items as $it ) {
        $html .= '<span class="tgs-chip-fact">' . esc_html( $it ) . '</span>';
    }
    return $html . '</div>';
}
add_shortcode( 'tgs_chips', 'tgs_shortcode_chips' );

/**
 * [tgs_infobox] — Container für 2 (oder mehr) Info-Spalten.
 */
function tgs_shortcode_infobox( $atts, $content = '' ) {
    $inner = do_shortcode( tgs_clean_shortcode_content( $content ) );
    if ( trim( $inner ) === '' ) return '';
    return '<div class="tgs-infobox">' . $inner . '</div>';
}
add_shortcode( 'tgs_infobox', 'tgs_shortcode_infobox' );

/**
 * [tgs_infospalte titel="" gross=""] — eine Spalte innerhalb von [tgs_infobox].
 */
function tgs_shortcode_infospalte( $atts, $content = '' ) {
    $atts = shortcode_atts( array( 'titel' => '', 'gross' => '' ), $atts );
    $body = do_shortcode( tgs_clean_shortcode_content( $content ) );
    $html  = '<div class="tgs-infobox-col">';
    if ( $atts['titel'] !== '' ) $html .= '<h4 class="tgs-infobox-h">' . esc_html( $atts['titel'] ) . '</h4>';
    if ( $atts['gross'] !== '' ) $html .= '<div class="tgs-infobox-big">' . esc_html( $atts['gross'] ) . '</div>';
    $html .= '<div class="tgs-infobox-body">' . wp_kses_post( $body ) . '</div>';
    return $html . '</div>';
}
add_shortcode( 'tgs_infospalte', 'tgs_shortcode_infospalte' );

/**
 * [tgs_gruppen] — Karten-Raster für Gruppen/Angebote.
 */
function tgs_shortcode_gruppen( $atts, $content = '' ) {
    $inner = do_shortcode( tgs_clean_shortcode_content( $content ) );
    if ( trim( $inner ) === '' ) return '';
    return '<div class="tgs-gruppen-grid">' . $inner . '</div>';
}
add_shortcode( 'tgs_gruppen', 'tgs_shortcode_gruppen' );

/**
 * [tgs_gruppe name="" grad="" hinweis=""] — eine Karte innerhalb von [tgs_gruppen].
 */
function tgs_shortcode_gruppe( $atts, $content = '' ) {
    $atts = shortcode_atts( array( 'name' => '', 'grad' => '', 'hinweis' => '' ), $atts );
    $desc = do_shortcode( tgs_clean_shortcode_content( $content ) );
    $html  = '<div class="tgs-gcard">';
    $html .= '<div class="tgs-gcard-top">';
    $html .= '<span class="tgs-gcard-nm">' . esc_html( $atts['name'] ) . '</span>';
    if ( $atts['grad'] !== '' ) $html .= '<span class="tgs-gcard-chip">' . esc_html( $atts['grad'] ) . '</span>';
    $html .= '</div>';
    if ( $desc !== '' ) $html .= '<p class="tgs-gcard-desc">' . wp_kses_post( $desc ) . '</p>';
    if ( $atts['hinweis'] !== '' ) $html .= '<div class="tgs-gcard-note">' . esc_html( $atts['hinweis'] ) . '</div>';
    return $html . '</div>';
}
add_shortcode( 'tgs_gruppe', 'tgs_shortcode_gruppe' );

/**
 * [tgs_cta_box titel="" text="" button="" url="" farbe="gruen|whatsapp"] — Hervorgehobene Aktions-Box.
 */
function tgs_shortcode_cta_box( $atts ) {
    $atts = shortcode_atts( array(
        'titel'  => '',
        'text'   => '',
        'button' => '',
        'url'    => '#',
        'farbe'  => 'gruen',
    ), $atts );
    $cls = 'tgs-cta-box' . ( $atts['farbe'] === 'whatsapp' ? ' tgs-cta-box--wa' : '' );
    $html  = '<div class="' . esc_attr( $cls ) . '">';
    if ( $atts['titel'] !== '' )  $html .= '<div class="tgs-cta-box-h">' . esc_html( $atts['titel'] ) . '</div>';
    if ( $atts['text'] !== '' )   $html .= '<p class="tgs-cta-box-t">' . esc_html( $atts['text'] ) . '</p>';
    if ( $atts['button'] !== '' ) $html .= '<a class="tgs-cta-box-btn" href="' . esc_url( $atts['url'] ) . '"' . ( strpos( $atts['url'], 'http' ) === 0 ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $atts['button'] ) . ' →</a>';
    return $html . '</div>';
}
add_shortcode( 'tgs_cta_box', 'tgs_shortcode_cta_box' );
