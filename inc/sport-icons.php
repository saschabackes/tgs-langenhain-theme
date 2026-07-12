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
    // Format: array( viewBox-Größe, 'line'|'fill', SVG-Innenteil )
    $icons = array(
        // Hantel (Linie)
        'fitness'     => array( 24, 'line', '<path d="M4 9v6"/><path d="M7 7.5v9"/><path d="M7 12h10"/><path d="M17 7.5v9"/><path d="M20 9v6"/>' ),
        // Tischtennisschläger + Ball (Linie)
        'tischtennis' => array( 24, 'line', '<circle cx="9.7" cy="9.7" r="5.3"/><path d="M13.6 13.6 17.4 17.4"/><path d="M15.4 18.6 18.6 15.4"/><circle cx="17.9" cy="6.1" r="1.3" fill="currentColor" stroke="none"/>' ),
        // Fahrrad (Linie)
        'radsport'    => array( 24, 'line', '<circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>' ),
        // Handball: Wurf-Silhouette nach Standard-Piktogramm (gefüllt)
        'handball'    => array( 300, 'fill', '<circle cx="150" cy="26.572" r="15"/><circle cx="195.893" cy="68.48" r="21.5"/><path d="M64.768,284.922c16.877-32.665,35.639-64.323,58.218-93.454a198.612,198.612,0,0,1,25.665-27.128,12.8,12.8,0,0,1,18.041,1.4c4.49,5.175,3.7,12.31-.282,17.294a152.976,152.976,0,0,1-10.579,14.07c-10.774,13.108-24.378,26.361-36.183,38.706C108.01,247.888,93.541,262.986,81.6,274.656c-4.253,4.183-8.942,8.7-13.4,12.67A2.125,2.125,0,0,1,64.768,284.922Z"/><path d="M85.112,190.274c7.306-15.072,16.9-29.735,29.823-40.557,3.009-2.518,7.587-4.641,11.16-6.366,13.644-6.374,25.145,11.43,13.95,21.264-13.756,11.663-25.477,25.225-36.4,39.531C93.769,215.622,77.412,203.226,85.112,190.274Z"/><path d="M140.6,90.587c-13.167-7.357-31.105-20.88-32.267-37.262-.456-13.618,11.614-22.993,21.268-30.436,3.043-2.249,6.108-4.411,9.253-6.468a1.483,1.483,0,0,1,2,2.11c-5.094,7.539-20.642,24.291-17.658,33.187,2.317,4.692,6.633,7.292,11.039,10.52,5.44,3.786,11.562,7.569,17.137,12.234C160.211,82.033,150.869,95.858,140.6,90.587Z"/><path d="M242.477,143.069c-.962-12.782-17.263-28.068-26.866-35.733-9.937-7.285-22.29,5.543-14.4,15.223,7.192,8.977,16.719,14.756,23.865,23.028-1.493,2.9-4.239,4.908-6.706,7.038-5.636,4.694-21.311,14.9-27.571,19.237a1.605,1.605,0,0,0,1.36,2.853c12.378-3.183,24.588-6.444,36.188-12.081C235.865,159.069,243.319,152.124,242.477,143.069Z"/>' ),
    );
    if ( empty( $icons[ $key ] ) ) return '';
    list( $vb, $mode, $inner ) = $icons[ $key ];
    $attrs = ( $mode === 'fill' )
        ? 'fill="currentColor" stroke="none"'
        : 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"';
    return '<svg class="tgs-sport-icon" viewBox="0 0 ' . $vb . ' ' . $vb . '" ' . $attrs . ' aria-hidden="true">' . $inner . '</svg>';
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
