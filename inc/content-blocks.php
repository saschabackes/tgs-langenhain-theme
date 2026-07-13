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

/**
 * [tgs_whatsapp] — WhatsApp-Community-Karte mit Ein-Klick-Beitritt (+ optional QR-Code).
 *
 * Attribute:
 *   titel      — Überschrift (Default: "WhatsApp-Community")
 *   text       — Kurzbeschreibung; {mitglieder} wird durch die Mitgliederzahl (fett) ersetzt
 *   mitglieder — z. B. "59 Aktive"
 *   tel        — Rufnummer international ohne + und Leerzeichen, z. B. 491738966223
 *   nachricht  — vorbelegter Chat-Text
 *   link       — alternativ: direkter Gruppenlink (chat.whatsapp.com/…), überschreibt tel
 *   qr         — Dateiname in assets/images/ ODER volle URL; zeigt QR-Code + breites Layout
 *   button     — Button-Text (Default: "Per WhatsApp beitreten")
 */
function tgs_shortcode_whatsapp( $atts ) {
    $atts = shortcode_atts( array(
        'titel'      => 'WhatsApp-Community',
        'text'       => '',
        'mitglieder' => '',
        'tel'        => '',
        'nachricht'  => '',
        'link'       => '',
        'qr'         => '',
        'button'     => 'Per WhatsApp beitreten',
    ), $atts );

    if ( $atts['link'] !== '' ) {
        $url = $atts['link'];
    } elseif ( $atts['tel'] !== '' ) {
        $url = 'https://wa.me/' . preg_replace( '/\D/', '', $atts['tel'] );
        if ( $atts['nachricht'] !== '' ) {
            $url .= '?text=' . rawurlencode( $atts['nachricht'] );
        }
    } else {
        return '';
    }

    $qr_url = '';
    if ( $atts['qr'] !== '' ) {
        $qr_url = ( strpos( $atts['qr'], 'http' ) === 0 || strpos( $atts['qr'], '/' ) === 0 )
            ? $atts['qr']
            : TGS_URI . '/assets/images/' . $atts['qr'];
    }

    $logo = '<svg class="tgs-wa-logo" viewBox="0 0 32 32" width="24" height="24" aria-hidden="true"><path fill="#25D366" d="M16 3C8.8 3 3 8.8 3 16c0 2.3.6 4.5 1.8 6.5L3 29l6.7-1.8C11.6 28.4 13.8 29 16 29c7.2 0 13-5.8 13-13S23.2 3 16 3z"/><path fill="#fff" d="M22.5 19.3c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1c-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-2-1.8-2.3s0-.5.1-.7c.1-.1.3-.4.5-.6.1-.2.2-.3.3-.5s0-.4 0-.6c-.1-.2-.7-1.7-1-2.3-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4s-1 1-1 2.5 1.1 2.9 1.2 3.1c.2.2 2.1 3.3 5.1 4.6.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.9-.8 2.1-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.2-.6-.4z"/></svg>';

    $has_qr = ( $qr_url !== '' );
    $cls = 'tgs-wa' . ( $has_qr ? '' : ' tgs-wa--kompakt' );

    $html  = '<div class="' . $cls . '">';
    $html .= '<div class="tgs-wa-main">';
    $html .= '<div class="tgs-wa-head">' . $logo . '<span class="tgs-wa-title">' . esc_html( $atts['titel'] ) . '</span></div>';
    if ( $atts['text'] !== '' ) {
        $pitch = esc_html( $atts['text'] );
        if ( $atts['mitglieder'] !== '' ) {
            $pitch = str_replace( '{mitglieder}', '<b>' . esc_html( $atts['mitglieder'] ) . '</b>', $pitch );
        }
        $html .= '<p class="tgs-wa-pitch">' . $pitch . '</p>';
    }
    $html .= '<a class="tgs-wa-btn" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . $logo . '<span>' . esc_html( $atts['button'] ) . '</span></a>';
    $html .= '</div>';
    if ( $has_qr ) {
        $html .= '<div class="tgs-wa-qr"><img src="' . esc_url( $qr_url ) . '" alt="QR-Code WhatsApp" loading="lazy"><span>Mit dem Handy scannen</span></div>';
    }
    return $html . '</div>';
}
add_shortcode( 'tgs_whatsapp', 'tgs_shortcode_whatsapp' );

/**
 * [tgs_hsg] — Spielgemeinschafts-Banner (z. B. Handball / HSG EppLa).
 *
 * Attribute:
 *   stamm1  — erster Stammverein (Default: "TGS Langenhain")
 *   stamm2  — zweiter Stammverein (Default: "TSG Eppstein")
 *   name    — Name der Spielgemeinschaft (Default: "HSG EppLa")
 *   titel   — Überschrift (Default: "Handball spielen wir gemeinsam")
 *   text    — Kurzbeschreibung
 *   button  — Button-Text (Default: "Zur HSG EppLa →")
 *   url     — Ziel (Default: https://hsg-eppla.de)
 */
function tgs_shortcode_hsg( $atts ) {
    $atts = shortcode_atts( array(
        'stamm1' => 'TGS Langenhain',
        'stamm2' => 'TSG Eppstein',
        'name'   => 'HSG EppLa',
        'titel'  => 'Handball spielen wir gemeinsam',
        'text'   => 'Als Spielgemeinschaft treten wir gemeinsam an — mit Teams von den Minis bis zu den Aktiven. Alle Mannschaften, Trainingszeiten und News gibt es bei der Spielgemeinschaft.',
        'button' => 'Zur HSG EppLa →',
        'url'    => 'https://hsg-eppla.de',
    ), $atts );

    ob_start();
    ?>
    <div class="tgs-hsg">
        <div class="tgs-hsg-inner">
            <span class="tgs-hsg-kicker">Handball-Spielgemeinschaft</span>
            <h3 class="tgs-hsg-title"><?php echo esc_html( $atts['titel'] ); ?></h3>
            <div class="tgs-hsg-union">
                <span class="tgs-hsg-club"><?php echo esc_html( $atts['stamm1'] ); ?></span>
                <span class="tgs-hsg-op">✕</span>
                <span class="tgs-hsg-club"><?php echo esc_html( $atts['stamm2'] ); ?></span>
                <span class="tgs-hsg-op tgs-hsg-arrow">→</span>
                <span class="tgs-hsg-name"><?php echo esc_html( $atts['name'] ); ?></span>
            </div>
            <?php if ( $atts['text'] !== '' ) : ?><p class="tgs-hsg-text"><?php echo esc_html( $atts['text'] ); ?></p><?php endif; ?>
            <a class="tgs-hsg-btn" href="<?php echo esc_url( $atts['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $atts['button'] ); ?></a>
        </div>
    </div>
    <?php
    return preg_replace( '/>\s+</', '><', ob_get_clean() );
}
add_shortcode( 'tgs_hsg', 'tgs_shortcode_hsg' );
