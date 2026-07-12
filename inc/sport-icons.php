<?php
/**
 * Vereinseigenes Sport-Icon-Set (Inline-SVG, einheitlicher Linienstil, erbt Farbe via currentColor).
 * Ersetzt die generischen Emojis in den Abteilungs-Badges.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SVG-Markup für einen bekannten Sport-Key. Leerer String, wenn unbekannt.
 */
function tgs_sport_icon_svg( $key ) {
    $paths = array(
        // Hantel
        'fitness'     => '<path d="M4 9v6"/><path d="M7 7.5v9"/><path d="M7 12h10"/><path d="M17 7.5v9"/><path d="M20 9v6"/>',
        // Hand + Ball
        'handball'    => '<circle cx="14.5" cy="7" r="3.6"/><path d="M4 20v-3.5a2 2 0 0 1 2-2h1"/><path d="M7 17.5v-4a1.3 1.3 0 0 1 2.6 0v2.5"/><path d="M9.6 15v-1.2a1.3 1.3 0 0 1 2.6 0V16"/><path d="M12.2 15.3a1.3 1.3 0 0 1 2.6 0V17c0 2-1.6 3.5-3.6 3.5H8a3 3 0 0 1-3-3"/>',
        // Tischtennisschläger + Ball
        'tischtennis' => '<circle cx="9.7" cy="9.7" r="5.3"/><path d="M13.6 13.6 17.4 17.4"/><path d="M15.4 18.6 18.6 15.4"/><circle cx="17.9" cy="6.1" r="1.3" fill="currentColor" stroke="none"/>',
        // Fahrrad
        'radsport'    => '<circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>',
    );
    if ( empty( $paths[ $key ] ) ) return '';
    return '<svg class="tgs-sport-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[ $key ] . '</svg>';
}

/**
 * Ermittelt den Sport-Key einer Abteilung aus Slug/Titel.
 */
function tgs_abteilung_icon_key( $post ) {
    if ( ! $post ) return '';
    $hay = strtolower( $post->post_name . ' ' . $post->post_title );
    if ( strpos( $hay, 'rad' ) !== false )                                   return 'radsport';
    if ( strpos( $hay, 'hand' ) !== false )                                  return 'handball';
    if ( strpos( $hay, 'tisch' ) !== false )                                 return 'tischtennis';
    if ( strpos( $hay, 'fit' ) !== false || strpos( $hay, 'turn' ) !== false || strpos( $hay, 'gym' ) !== false ) return 'fitness';
    return '';
}

/**
 * Icon-Markup für eine Abteilung: eigenes SVG, sonst Emoji-Fallback aus Meta.
 */
function tgs_abteilung_icon_html( $post_id ) {
    $post = get_post( $post_id );
    $key  = tgs_abteilung_icon_key( $post );
    $svg  = $key ? tgs_sport_icon_svg( $key ) : '';
    if ( $svg ) return $svg;
    $emoji = get_post_meta( $post_id, '_tgs_abt_icon', true );
    return $emoji ? '<span class="tgs-sport-emoji">' . esc_html( $emoji ) . '</span>' : '';
}
