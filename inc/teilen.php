<?php
/**
 * Teilen — Open-Graph-Vorschau + datenschutzfreundlicher Teilen-Button.
 *
 * Zwei Teile:
 *  1. Open-Graph-/Twitter-Meta im <head>, damit ein geteilter Link sich zu
 *     einer Karte mit Foto + Titel + Text „entfaltet" (WhatsApp, Facebook,
 *     iMessage …) statt als nackte URL zu erscheinen.
 *  2. Ein Teilen-Button ohne Fremd-Widgets: die native Web-Share-API (ein Tipp
 *     → echter Teilen-Dialog des Geräts), am Desktop ein kleines Menü mit
 *     Link-kopieren, WhatsApp-Link und E-Mail. Kein Facebook-/Twitter-Skript,
 *     kein Tracking, keine Datenübertragung beim Laden der Seite.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * Open Graph / Twitter Cards
 * ========================================================================= */

/** Fallback-Bild (Vereinslogo), wenn ein Beitrag kein eigenes Bild hat. */
function tgs_og_fallback_bild() {
    $logo_id = get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        $url = wp_get_attachment_image_url( $logo_id, 'full' );
        if ( $url ) return $url;
    }
    return TGS_URI . '/assets/images/tgs-logo-black.png';
}

/** Text auf OG-taugliche Länge kürzen (~200 Zeichen, an Wortgrenze). */
function tgs_og_kurz( $text, $max = 200 ) {
    $text = trim( wp_strip_all_tags( (string) $text ) );
    if ( mb_strlen( $text ) <= $max ) return $text;
    return rtrim( mb_substr( $text, 0, $max - 1 ), " \t\n\r\0\x0B.,;:" ) . '…';
}

/**
 * OG-Daten für den aktuellen Beitrag bestimmen.
 * @return array( title, desc, image, url, type )
 */
function tgs_og_daten( $id ) {
    $typ   = get_post_type( $id );
    $title = get_the_title( $id );
    $url   = get_permalink( $id );
    $desc  = '';
    $og_type = ( $typ === 'post' ) ? 'article' : 'website';

    // Beschreibung je Inhaltstyp
    if ( $typ === 'tgs_kurs' ) {
        $desc = get_post_meta( $id, '_tgs_kurs_kurz', true );
    } elseif ( $typ === 'tgs_tour' && function_exists( 'tgs_tour_daten' ) ) {
        // Eine Tour teilt sich mit ihren Eckdaten: „37,7 km · ▲ 711 m · MTB".
        $d = tgs_tour_daten( $id );
        $teile = array();
        if ( $d['km'] > 0 )     $teile[] = number_format_i18n( $d['km'], 1 ) . ' km';
        if ( $d['hm_auf'] > 0 ) $teile[] = '▲ ' . number_format_i18n( $d['hm_auf'] ) . ' m';
        $arten = function_exists( 'tgs_tour_arten' ) ? tgs_tour_arten() : array();
        if ( isset( $arten[ $d['art'] ] ) ) $teile[] = $arten[ $d['art'] ];
        $desc = implode( ' · ', $teile );
        $exc = get_the_excerpt( $id );
        if ( $exc ) $desc = trim( $desc . ' — ' . $exc );
    }

    if ( $desc === '' ) {
        $desc = get_the_excerpt( $id );
    }
    if ( $desc === '' ) {
        $desc = get_post_field( 'post_content', $id );
    }
    $desc = tgs_og_kurz( do_shortcode( $desc ) ); // z. B. [tgs_kurs_anzahl] im Auszug auflösen

    // Bild: Beitragsbild, sonst Logo
    $image = get_the_post_thumbnail_url( $id, 'large' );
    if ( ! $image ) $image = tgs_og_fallback_bild();

    return array(
        'title' => $title,
        'desc'  => $desc,
        'image' => $image,
        'url'   => $url,
        'type'  => $og_type,
    );
}

/**
 * Open-Graph- und Twitter-Meta ausgeben.
 * Läuft für einzelne Beiträge/Seiten/CPTs und die Startseite.
 */
function tgs_open_graph() {
    // Kümmert sich später ein SEO-Plugin um OG, hier NICHT doppelt ausgeben.
    if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || class_exists( 'The_SEO_Framework\\Load' ) ) {
        return;
    }

    $site = get_bloginfo( 'name' );

    if ( is_singular() ) {
        $og = tgs_og_daten( get_the_ID() );
    } elseif ( is_front_page() || is_home() ) {
        $og = array(
            'title' => $site,
            'desc'  => tgs_og_kurz( get_bloginfo( 'description' ) ),
            'image' => tgs_og_fallback_bild(),
            'url'   => home_url( '/' ),
            'type'  => 'website',
        );
    } else {
        return; // Archive/Suche: keine spezifische Vorschau
    }

    $tags = array(
        'og:site_name'   => $site,
        'og:locale'      => 'de_DE',
        'og:type'        => $og['type'],
        'og:title'       => $og['title'],
        'og:description' => $og['desc'],
        'og:url'         => $og['url'],
        'og:image'       => $og['image'],
    );

    echo "\n<!-- TGS Open Graph -->\n";
    foreach ( $tags as $prop => $val ) {
        if ( $val === '' ) continue;
        printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $prop ), esc_attr( $val ) );
    }
    // Twitter: bei Bild große Karte, sonst kompakt.
    $card = $og['image'] ? 'summary_large_image' : 'summary';
    printf( '<meta name="twitter:card" content="%s">' . "\n", esc_attr( $card ) );
    printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $og['title'] ) );
    if ( $og['desc'] )  printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $og['desc'] ) );
    if ( $og['image'] ) printf( '<meta name="twitter:image" content="%s">' . "\n", esc_attr( $og['image'] ) );
    echo "<!-- /TGS Open Graph -->\n";
}
add_action( 'wp_head', 'tgs_open_graph', 5 );

/* =========================================================================
 * Teilen-Button
 * ========================================================================= */

/**
 * Teilen-Steuerung für einen Beitrag.
 *
 * Fortschrittlich aufgebaut: die WhatsApp- und E-Mail-Einträge sind echte
 * Links (funktionieren auch ohne JavaScript). Gibt es die native Web-Share-API
 * (Handy), öffnet ein Tipp direkt den System-Teilen-Dialog; sonst klappt das
 * Menü auf. Kein Fremd-Skript, kein Tracking.
 *
 * @param int    $post_id
 * @param string $label
 */
function tgs_teilen_html( $post_id = 0, $label = 'Teilen' ) {
    $post_id = $post_id ? $post_id : get_the_ID();
    if ( ! $post_id ) return '';
    $url   = get_permalink( $post_id );
    $title = get_the_title( $post_id );
    if ( ! $url ) return '';

    $teaser = get_post_type( $post_id ) === 'tgs_tour' ? $title : $title;
    $wa   = 'https://wa.me/?text=' . rawurlencode( $title . ' — ' . $url );
    $mail = 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $title . "\n\n" . $url );

    ob_start();
    ?>
    <div class="tgs-teilen" data-title="<?php echo esc_attr( $title ); ?>" data-text="<?php echo esc_attr( $teaser ); ?>" data-url="<?php echo esc_attr( $url ); ?>">
        <button type="button" class="tgs-teilen-toggle" aria-haspopup="true" aria-expanded="false">
            <span class="tgs-teilen-icon" aria-hidden="true">↗</span> <?php echo esc_html( $label ); ?>
        </button>
        <div class="tgs-teilen-menu" hidden>
            <a class="tgs-teilen-opt" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
            <a class="tgs-teilen-opt" href="<?php echo esc_url( $mail ); ?>">E-Mail</a>
            <button type="button" class="tgs-teilen-opt tgs-teilen-copy" data-url="<?php echo esc_attr( $url ); ?>">Link kopieren</button>
        </div>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

/** [tgs_teilen] – optional mit label="…". */
function tgs_teilen_shortcode( $atts ) {
    $a = shortcode_atts( array( 'label' => 'Teilen', 'id' => 0 ), $atts );
    return tgs_teilen_html( intval( $a['id'] ), $a['label'] );
}
add_shortcode( 'tgs_teilen', 'tgs_teilen_shortcode' );
