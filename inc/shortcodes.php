<?php
/**
 * Shortcodes für dynamischen Content
 * 
 * [tgs_kurstabelle]       — Kurstabelle mit Filterchips
 * [tgs_abteilungen]       — Abteilungen-Grid (4 Karten)
 * [tgs_ansprechpartner]   — Ansprechpartner-Grid
 * [tgs_sponsoren]         — Sponsorenleiste
 * [tgs_kurse_in_ort]      — Belegungstabelle für eine Sportstätte
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [tgs_kurstabelle] — Alle Kurse als filterbare Tabelle
 * 
 * Attribute:
 *   limit     — Max. Anzahl (default: -1 = alle)
 *   kategorie — Nur bestimmte Kategorie (Slug)
 *   kompakt   — "ja" für Startseiten-Variante ohne Kategorie-Spalte
 */
/**
 * Rendert einen Filter-Chip — im Teaser-Modus als Deep-Link zur vollen
 * Kursseite (#gruppe=slug), sonst als In-Place-Filter-Span für theme.js.
 */
function tgs_kurs_filter_chip( $label, $filter, $active, $teaser, $ziel, $group ) {
    $cls = 'tgs-chip' . ( $active ? ' active' : '' );
    if ( $teaser ) {
        $href = ( $filter === 'alle' ) ? $ziel : $ziel . '#' . $group . '=' . $filter;
        return sprintf( '<a class="%s" href="%s">%s</a>', esc_attr( $cls ), esc_url( $href ), esc_html( $label ) );
    }
    return sprintf( '<span class="%s" data-filter="%s">%s</span>', esc_attr( $cls ), esc_attr( $filter ), esc_html( $label ) );
}

function tgs_shortcode_kurstabelle( $atts ) {
    $atts = shortcode_atts( array(
        'limit'     => -1,
        'kategorie' => '',
        'kompakt'   => 'nein',
        'ziel_url'  => '',   // Ziel der Filter-Links im Teaser-Modus
    ), $atts );

    $args = array(
        'post_type'      => 'tgs_kurs',
        'posts_per_page' => intval( $atts['limit'] ),
        'orderby'        => 'meta_value',
        'meta_key'       => '_tgs_wochentag',
        'order'          => 'ASC',
    );

    if ( $atts['kategorie'] ) {
        $args['tax_query'] = array( array(
            'taxonomy' => 'tgs_kurs_kategorie',
            'field'    => 'slug',
            'terms'    => $atts['kategorie'],
        ) );
    }

    $kurse = get_posts( $args );
    if ( empty( $kurse ) ) {
        return '<p class="tgs-no-kurse">Aktuell keine Kurse vorhanden.</p>';
    }

    // Get all categories for filter chips
    $kategorien = get_terms( array(
        'taxonomy'   => 'tgs_kurs_kategorie',
        'hide_empty' => true,
    ) );

    $is_kompakt = $atts['kompakt'] === 'ja';

    // Teaser (begrenzte Tabelle): Filter-Chips verlinken auf die volle Kursseite.
    $is_teaser = intval( $atts['limit'] ) > 0;
    $ziel_url  = $atts['ziel_url'];
    if ( $is_teaser && ! $ziel_url ) {
        $ziel_url = get_post_type_archive_link( 'tgs_kurs' );
        if ( ! $ziel_url ) $ziel_url = '/kurse';
    }

    // Vorhandene Zielgruppen sammeln (Teaser: über ALLE Kurse, damit z.B.
    // "Frauen" auch als Link erscheint, wenn nicht unter den ersten Zeilen).
    $zg_map  = function_exists( 'tgs_zielgruppen' ) ? tgs_zielgruppen() : array();
    $zg_used = array();
    if ( ! $is_kompakt && $zg_map ) {
        if ( $is_teaser ) {
            $all_ids = get_posts( array( 'post_type' => 'tgs_kurs', 'posts_per_page' => -1, 'fields' => 'ids' ) );
            foreach ( $all_ids as $iid ) {
                foreach ( tgs_kurs_zielgruppen( $iid ) as $slug ) $zg_used[ $slug ] = true;
            }
        } else {
            foreach ( $kurse as $k ) {
                foreach ( tgs_kurs_zielgruppen( $k->ID ) as $slug ) $zg_used[ $slug ] = true;
            }
        }
    }

    ob_start();
    ?>
    <div class="tgs-kurstabelle-wrap">
        <?php if ( ! $is_kompakt && ! empty( $kategorien ) ) : ?>
        <div class="tgs-chip-row"<?php echo $is_teaser ? '' : ' data-filter-group="kategorie"'; ?>>
            <?php echo tgs_kurs_filter_chip( 'Alle', 'alle', true, $is_teaser, $ziel_url, 'kategorie' ); ?>
            <?php foreach ( $kategorien as $kat ) : ?>
                <?php echo tgs_kurs_filter_chip( $kat->name, $kat->slug, false, $is_teaser, $ziel_url, 'kategorie' ); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( ! $is_kompakt && ! empty( $zg_used ) ) : ?>
        <div class="tgs-chip-row tgs-chip-row--zielgruppe"<?php echo $is_teaser ? '' : ' data-filter-group="zielgruppe"'; ?>>
            <span class="tgs-chip-label">Für wen?</span>
            <?php echo tgs_kurs_filter_chip( 'Alle', 'alle', true, $is_teaser, $ziel_url, 'zielgruppe' ); ?>
            <?php foreach ( $zg_map as $slug => $label ) : if ( empty( $zg_used[ $slug ] ) ) continue; ?>
                <?php echo tgs_kurs_filter_chip( $label, $slug, false, $is_teaser, $ziel_url, 'zielgruppe' ); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <table class="tgs-kurs-tabelle">
            <thead>
                <tr>
                    <?php if ( ! $is_kompakt ) : ?><th>Kategorie</th><?php endif; ?>
                    <th>Kurs</th>
                    <th>Tag</th>
                    <th>Zeit</th>
                    <th>Ort</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $kurse as $kurs ) :
                    $tag    = get_post_meta( $kurs->ID, '_tgs_wochentag', true );
                    $zeit   = get_post_meta( $kurs->ID, '_tgs_uhrzeit', true );
                    $ort    = get_post_meta( $kurs->ID, '_tgs_ort', true );
                    $status = get_post_meta( $kurs->ID, '_tgs_status', true );
                    $terms  = get_the_terms( $kurs->ID, 'tgs_kurs_kategorie' );
                    $kat_name = $terms ? $terms[0]->name : '';
                    $kat_slug = $terms ? $terms[0]->slug : '';
                    $zg_slugs = implode( ' ', tgs_kurs_zielgruppen( $kurs->ID ) );
                    $status_class = $status === 'warteliste' ? 'tgs-status-warteliste' : 'tgs-status-frei';
                    $status_label = $status === 'warteliste' ? '⚠ Warteliste' : '✓ Freie Plätze';
                    $link_label   = $status === 'warteliste' ? 'Warteliste →' : 'Details →';
                    if ( function_exists( 'tgs_kurs_ist_offen' ) && tgs_kurs_ist_offen( $kurs->ID ) ) {
                        $status_class = 'tgs-status-frei'; $status_label = '✓ Offen'; $link_label = 'Details →';
                    }
                ?>
                <tr class="tgs-kurs-row" data-kategorie="<?php echo esc_attr( $kat_slug ); ?>" data-zielgruppe="<?php echo esc_attr( $zg_slugs ); ?>">
                    <?php if ( ! $is_kompakt ) : ?>
                    <td><span class="tgs-kurs-kategorie"><?php echo esc_html( $kat_name ); ?></span></td>
                    <?php endif; ?>
                    <td><a href="<?php echo get_permalink( $kurs->ID ); ?>" class="tgs-kurs-name"><?php echo esc_html( $kurs->post_title ); ?></a><?php if ( function_exists( 'tgs_kurs_meldung_badge' ) ) echo ' ' . tgs_kurs_meldung_badge( $kurs->ID ); ?></td>
                    <td class="tgs-kurs-meta"><?php echo esc_html( $tag ); ?></td>
                    <td class="tgs-kurs-meta"><?php echo esc_html( $zeit ); ?></td>
                    <td class="tgs-kurs-meta"><?php echo esc_html( $ort ); ?></td>
                    <td><span class="<?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                    <td><a href="<?php echo get_permalink( $kurs->ID ); ?>" class="tgs-kurs-link"><?php echo $link_label; ?></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_kurstabelle', 'tgs_shortcode_kurstabelle' );

/**
 * [tgs_abteilungen] — Abteilungen als 4-Karten-Grid
 */
function tgs_shortcode_abteilungen() {
    $abteilungen = get_posts( array(
        'post_type'      => 'tgs_abteilung',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ) );

    if ( empty( $abteilungen ) ) return '';

    $html = '<div class="tgs-abt-grid">';
    foreach ( $abteilungen as $abt ) {
        $icon    = get_post_meta( $abt->ID, '_tgs_abt_icon', true ) ?: '🏅';
        $excerpt = get_the_excerpt( $abt->ID );
        $url     = get_permalink( $abt->ID );
        $html .= '<div class="tgs-abt-card" onclick="window.location=\'' . esc_url( $url ) . '\'">';
        $html .= '<span class="tgs-abt-icon">' . tgs_abteilung_icon_html( $abt->ID ) . '</span>';
        $html .= '<span class="tgs-abt-name">' . esc_html( $abt->post_title ) . '</span>';
        $html .= '<span class="tgs-abt-desc">' . esc_html( $excerpt ) . '</span>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}
add_shortcode( 'tgs_abteilungen', 'tgs_shortcode_abteilungen' );

/**
 * [tgs_ansprechpartner] — Kontakt-Karten-Grid
 * 
 * Zeigt Ansprechpartner aus Abteilungen + optional manuell hinzugefügte.
 */
function tgs_shortcode_ansprechpartner() {
    $abteilungen = get_posts( array(
        'post_type'      => 'tgs_abteilung',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ) );

    if ( empty( $abteilungen ) ) return '';

    $html = '<div class="tgs-kontakt-grid">';
    foreach ( $abteilungen as $abt ) {
        $name  = get_post_meta( $abt->ID, '_tgs_abt_leitung', true );
        $email = get_post_meta( $abt->ID, '_tgs_abt_email', true );
        if ( ! $name ) continue;
        $html .= '<div class="tgs-kontakt-card">';
        $html .= '<div class="tgs-kontakt-role">' . esc_html( $abt->post_title ) . '</div>';
        $html .= '<div class="tgs-kontakt-name">' . esc_html( $name ) . '</div>';
        if ( $email ) {
            $html .= '<a href="mailto:' . esc_attr( $email ) . '" class="tgs-kontakt-mail">' . esc_html( $email ) . '</a>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}
add_shortcode( 'tgs_ansprechpartner', 'tgs_shortcode_ansprechpartner' );

/**
 * [tgs_sponsoren] — Sponsorenleiste
 * 
 * Erstmal statisch, kann später als eigener CPT oder Widget umgebaut werden.
 */
function tgs_shortcode_sponsoren() {
    $sponsoren = array(
        array( 'name' => 'Mobau Braun', 'url' => 'https://mobau-braun.de' ),
        array( 'name' => 'KP International', 'url' => 'https://www.kp-international.de' ),
        array( 'name' => 'Domotec', 'url' => 'http://www.hm-domotec.de' ),
        array( 'name' => 'Dörr &amp; Neumann', 'url' => 'https://www.doerrundneumann.de' ),
        array( 'name' => 'Optik Waller', 'url' => 'https://www.optik-waller.de' ),
        array( 'name' => 'Simon Haustechnik', 'url' => 'https://www.haustechnik-simon.de' ),
    );

    $html = '<div class="tgs-sponsor-section">';
    $html .= '<div class="tgs-sponsor-hd">Unsere Partner &amp; Sponsoren</div>';
    $html .= '<div class="tgs-sponsor-row">';
    foreach ( $sponsoren as $s ) {
        $html .= '<a href="' . esc_url( $s['url'] ) . '" class="tgs-sponsor" target="_blank" rel="noopener">' . $s['name'] . '</a>';
    }
    $html .= '</div></div>';
    return $html;
}
add_shortcode( 'tgs_sponsoren', 'tgs_shortcode_sponsoren' );

/**
 * [tgs_kurse_in_ort ort="Wilhelm-Busch-Halle"] — Belegungsplan für eine Sportstätte
 */
function tgs_shortcode_kurse_in_ort( $atts ) {
    $atts = shortcode_atts( array( 'ort' => '' ), $atts );
    if ( ! $atts['ort'] ) return '';

    $kurse = get_posts( array(
        'post_type'      => 'tgs_kurs',
        'posts_per_page' => -1,
        'meta_key'       => '_tgs_wochentag',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array( array(
            'key'     => '_tgs_ort',
            'value'   => $atts['ort'],
            'compare' => 'LIKE',
        ) ),
    ) );

    if ( empty( $kurse ) ) return '<p>Keine Kurse an diesem Ort.</p>';

    // Sort by weekday
    $day_order = array( 'Mo' => 1, 'Di' => 2, 'Mi' => 3, 'Do' => 4, 'Fr' => 5, 'Sa' => 6, 'So' => 7 );
    usort( $kurse, function( $a, $b ) use ( $day_order ) {
        $da = $day_order[ get_post_meta( $a->ID, '_tgs_wochentag', true ) ] ?? 8;
        $db = $day_order[ get_post_meta( $b->ID, '_tgs_wochentag', true ) ] ?? 8;
        if ( $da === $db ) {
            return strcmp(
                get_post_meta( $a->ID, '_tgs_uhrzeit', true ),
                get_post_meta( $b->ID, '_tgs_uhrzeit', true )
            );
        }
        return $da - $db;
    } );

    ob_start();
    ?>
    <table class="tgs-kurs-tabelle">
        <thead><tr><th>Tag</th><th>Zeit</th><th>Kurs</th><th>Kategorie</th><th></th></tr></thead>
        <tbody>
            <?php foreach ( $kurse as $kurs ) :
                $tag   = get_post_meta( $kurs->ID, '_tgs_wochentag', true );
                $zeit  = get_post_meta( $kurs->ID, '_tgs_uhrzeit', true );
                $terms = get_the_terms( $kurs->ID, 'tgs_kurs_kategorie' );
                $kat   = $terms ? $terms[0]->name : '';
            ?>
            <tr>
                <td class="tgs-kurs-meta"><strong><?php echo esc_html( $tag ); ?></strong></td>
                <td class="tgs-kurs-meta"><?php echo esc_html( $zeit ); ?></td>
                <td><a href="<?php echo get_permalink( $kurs->ID ); ?>" class="tgs-kurs-name"><?php echo esc_html( $kurs->post_title ); ?></a></td>
                <td><span class="tgs-kurs-kategorie"><?php echo esc_html( $kat ); ?></span></td>
                <td><a href="<?php echo get_permalink( $kurs->ID ); ?>" class="tgs-kurs-link">Details →</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_kurse_in_ort', 'tgs_shortcode_kurse_in_ort' );

/**
 * [tgs_kurs_detail] — Kurs-Steckbrief (für Kurs-Detailseite)
 * Rendert automatisch den aktuellen Kurs-Post.
 */
function tgs_shortcode_kurs_detail() {
    $post_id = get_the_ID();
    if ( get_post_type( $post_id ) !== 'tgs_kurs' ) return '';

    $tag       = get_post_meta( $post_id, '_tgs_wochentag', true );
    $zeit      = get_post_meta( $post_id, '_tgs_uhrzeit', true );
    $zeit_ende = get_post_meta( $post_id, '_tgs_uhrzeit_ende', true );
    $ort       = get_post_meta( $post_id, '_tgs_ort', true );
    $status    = get_post_meta( $post_id, '_tgs_status', true );
    $max_tn    = get_post_meta( $post_id, '_tgs_max_teilnehmer', true );
    $zielgr    = function_exists( 'tgs_kurs_zielgruppen_labels' ) ? implode( ', ', tgs_kurs_zielgruppen_labels( $post_id ) ) : '';
    $mitbr     = get_post_meta( $post_id, '_tgs_mitbringen', true );
    $ap_name   = get_post_meta( $post_id, '_tgs_ansprechpartner', true );
    $ap_email  = get_post_meta( $post_id, '_tgs_ansprechpartner_email', true );
    $ap_tel    = get_post_meta( $post_id, '_tgs_ansprechpartner_tel', true );
    $terms     = get_the_terms( $post_id, 'tgs_kurs_kategorie' );
    $kat       = $terms ? $terms[0]->name : '';

    $status_label = $status === 'warteliste' ? '⚠ Warteliste' : '✓ Freie Plätze verfügbar';
    $status_class = $status === 'warteliste' ? 'tgs-status-warteliste' : 'tgs-status-frei';
    $btn_label    = $status === 'warteliste' ? 'Auf Warteliste setzen' : 'Jetzt anmelden';
    $zeit_display = $zeit . ( $zeit_ende ? ' – ' . $zeit_ende . ' Uhr' : ' Uhr' );
    $offen = function_exists( 'tgs_kurs_ist_offen' ) && tgs_kurs_ist_offen( $post_id );
    if ( $offen ) { $status_label = '✓ Offen – keine Anmeldung nötig'; $status_class = 'tgs-status-frei'; }

    ob_start();
    ?>
    <div class="tgs-kd-header">
        <div class="tgs-kd-header-l">
            <span class="tgs-kd-tag"><?php echo esc_html( $kat ); ?></span>
            <h1 class="tgs-kd-h1"><?php echo esc_html( get_the_title() ); ?></h1>
            <div class="tgs-kd-meta-row">
                <span>📅 <strong><?php echo esc_html( $tag ); ?></strong></span>
                <span>🕐 <strong><?php echo esc_html( $zeit_display ); ?></strong></span>
                <span>📍 <strong><?php echo esc_html( $ort ); ?></strong></span>
            </div>
        </div>
        <div class="tgs-kd-cta">
            <?php if ( ! $offen ) : ?><a href="#tgs-anmeldung" class="tgs-kd-btn"><?php echo $btn_label; ?></a><?php endif; ?>
            <div class="<?php echo $status_class; ?>"><?php echo $status_label; ?></div>
        </div>
    </div>

    <div class="tgs-kd-body">
        <div class="tgs-kd-content">
            <?php if ( function_exists( 'tgs_render_kurs_meldungen' ) ) echo tgs_render_kurs_meldungen( $post_id ); ?>
            <?php the_content(); ?>

            <?php if ( $ap_name ) : ?>
            <div class="tgs-kd-ap">
                <strong>Ansprechpartner:</strong><br>
                <?php echo esc_html( $ap_name ); ?>
                <?php if ( $ap_email ) : ?> · <a href="mailto:<?php echo esc_attr( $ap_email ); ?>"><?php echo esc_html( $ap_email ); ?></a><?php endif; ?>
                <?php if ( $ap_tel ) : ?> · <?php echo esc_html( $ap_tel ); ?><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ( function_exists( 'tgs_render_kurs_bewertungen' ) ) echo tgs_render_kurs_bewertungen( $post_id ); ?>
            <div class="tgs-kd-anmeldung"><?php echo do_shortcode( '[tgs_anmeldung]' ); ?></div>
        </div>

        <div class="tgs-kd-sidebar">
            <div class="tgs-kd-info-box">
                <div class="tgs-kd-info-title">Auf einen Blick</div>
                <?php if ( $tag ) : ?><div class="tgs-kd-info-row"><strong>Tag</strong><span><?php echo esc_html( $tag ); ?></span></div><?php endif; ?>
                <?php if ( $zeit ) : ?><div class="tgs-kd-info-row"><strong>Uhrzeit</strong><span><?php echo esc_html( $zeit_display ); ?></span></div><?php endif; ?>
                <?php if ( $ort ) : ?><div class="tgs-kd-info-row"><strong>Ort</strong><span><?php echo esc_html( $ort ); ?></span></div><?php endif; ?>
                <?php if ( $zielgr ) : ?><div class="tgs-kd-info-row"><strong>Zielgruppe</strong><span><?php echo esc_html( $zielgr ); ?></span></div><?php endif; ?>
                <?php
                if ( function_exists( 'tgs_kurs_altersgrenzen' ) ) {
                    $kd_alter = tgs_kurs_altersgrenzen( $post_id );
                    if ( $kd_alter['has'] ) {
                        echo '<div class="tgs-kd-info-row"><strong>Alter</strong><span>' . esc_html( tgs_alter_hinweis( $kd_alter['min'], $kd_alter['max'] ) ) . '</span></div>';
                    }
                }
                ?>
                <?php if ( $mitbr ) : ?><div class="tgs-kd-info-row"><strong>Mitbringen</strong><span><?php echo esc_html( $mitbr ); ?></span></div><?php endif; ?>
                <?php if ( $max_tn ) : ?><div class="tgs-kd-info-row"><strong>Max. Teiln.</strong><span><?php echo esc_html( $max_tn ); ?></span></div><?php endif; ?>
            </div>

            <?php
            // Verwandte Kurse aus gleicher Kategorie
            if ( $terms ) :
                $related = get_posts( array(
                    'post_type'      => 'tgs_kurs',
                    'posts_per_page' => 4,
                    'post__not_in'   => array( $post_id ),
                    'tax_query'      => array( array(
                        'taxonomy' => 'tgs_kurs_kategorie',
                        'terms'    => $terms[0]->term_id,
                    ) ),
                ) );
                if ( $related ) :
            ?>
            <div class="tgs-kd-info-box">
                <div class="tgs-kd-info-title">Weitere <?php echo esc_html( $kat ); ?></div>
                <div class="tgs-kd-related">
                    <?php foreach ( $related as $rel ) : ?>
                        <a href="<?php echo get_permalink( $rel->ID ); ?>" class="tgs-kd-related-item">
                            <?php echo esc_html( $rel->post_title ); ?>
                            <span><?php echo esc_html( get_post_meta( $rel->ID, '_tgs_wochentag', true ) ); ?> <?php echo esc_html( get_post_meta( $rel->ID, '_tgs_uhrzeit', true ) ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_kurs_detail', 'tgs_shortcode_kurs_detail' );

/**
 * [tgs_navigation] — Hauptnavigation aus klassischem WP-Menü
 */
function tgs_shortcode_navigation() {
    // Versuche zuerst das registrierte Menü
    if ( has_nav_menu( 'primary' ) ) {
        return wp_nav_menu( array(
            'theme_location' => 'primary',
            'container'      => 'nav',
            'container_class'=> 'tgs-main-nav',
            'menu_class'     => 'tgs-nav-list',
            'depth'          => 1,
            'echo'           => false,
        ) );
    }
    
    // Fallback: Menü nach Name suchen
    $menu = wp_get_nav_menu_object( 'Main' );
    if ( $menu ) {
        return wp_nav_menu( array(
            'menu'           => $menu->term_id,
            'container'      => 'nav',
            'container_class'=> 'tgs-main-nav',
            'menu_class'     => 'tgs-nav-list',
            'depth'          => 1,
            'echo'           => false,
        ) );
    }
    
    // Letzter Fallback: statische Links
    return '<nav class="tgs-main-nav"><ul class="tgs-nav-list">
        <li><a href="/kurse">Kurse</a></li>
        <li><a href="/abteilungen">Abteilungen</a></li>
        <li><a href="/sportstaetten">Sportstätten</a></li>
        <li><a href="/kontakt">Ansprechpartner</a></li>
    </ul></nav>';
}
add_shortcode( 'tgs_navigation', 'tgs_shortcode_navigation' );

/**
 * [tgs_sportstaette_detail] — Sportstätten-Detailseite
 */
function tgs_shortcode_sportstaette_detail() {
    $post_id = get_the_ID();
    if ( get_post_type( $post_id ) !== 'tgs_sportstaette' ) return '';

    $typ      = get_post_meta( $post_id, '_tgs_ss_typ', true );
    $adresse  = get_post_meta( $post_id, '_tgs_adresse', true );
    $plz_ort  = get_post_meta( $post_id, '_tgs_plz_ort', true );
    $maps     = get_post_meta( $post_id, '_tgs_maps_link', true );
    $zugang   = get_post_meta( $post_id, '_tgs_ss_zugang', true );
    $kosten   = get_post_meta( $post_id, '_tgs_ss_kosten', true );
    $ausst    = get_post_meta( $post_id, '_tgs_ausstattung', true );
    $barr     = get_post_meta( $post_id, '_tgs_barrierefreiheit', true );
    $park     = get_post_meta( $post_id, '_tgs_parkplaetze', true );
    $ort_name = get_the_title( $post_id );
    $bild      = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'large' ) : '';
    $app_name  = get_post_meta( $post_id, '_tgs_ss_app_name', true );
    $app_desc  = get_post_meta( $post_id, '_tgs_ss_app_desc', true );
    $app_ios   = get_post_meta( $post_id, '_tgs_ss_app_ios', true );
    $app_andr  = get_post_meta( $post_id, '_tgs_ss_app_android', true );

    // Ausstattung in Listenpunkte zerlegen (Zeilenweise; Altdaten mit Kommas werden gesplittet)
    $ausst_items = array();
    foreach ( preg_split( '/\r\n|\r|\n/', (string) $ausst ) as $line ) {
        $line = trim( $line );
        if ( $line !== '' ) $ausst_items[] = $line;
    }
    if ( count( $ausst_items ) === 1 && strpos( $ausst_items[0], ',' ) !== false ) {
        $ausst_items = array_filter( array_map( 'trim', explode( ',', $ausst_items[0] ) ) );
    }

    // Kurse an diesem Ort vorhanden?
    $kurse_hier = get_posts( array(
        'post_type'      => 'tgs_kurs',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array( array( 'key' => '_tgs_ort', 'value' => $ort_name, 'compare' => 'LIKE' ) ),
    ) );
    $hat_kurse = ! empty( $kurse_hier );

    ob_start();
    ?>
    <div class="tgs-ss-hero<?php echo $bild ? ' tgs-ss-hero--photo' : ''; ?>"<?php if ( $bild ) echo ' style="background-image:linear-gradient(rgba(20,32,22,.15),rgba(20,32,22,.72)),url(' . esc_url( $bild ) . ');"'; ?>>
        <div class="tgs-ss-hero-inner">
            <?php if ( $typ ) : ?><span class="tgs-ss-typ"><?php echo esc_html( $typ ); ?></span><?php endif; ?>
            <h1 class="tgs-ss-h1"><?php echo esc_html( $ort_name ); ?></h1>
            <div class="tgs-ss-addr">
                <?php if ( $adresse ) echo esc_html( $adresse ) . ' · '; ?>
                <?php if ( $plz_ort ) echo esc_html( $plz_ort ); ?>
            </div>
            <?php if ( $maps ) : ?>
                <a href="<?php echo esc_url( $maps ); ?>" class="tgs-ss-maps-btn" target="_blank" rel="noopener">📍 Anfahrt in Google Maps öffnen →</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( $zugang || $kosten || $park || $barr ) : ?>
    <div class="tgs-ss-facts">
        <?php if ( $zugang ) : ?>
        <div class="tgs-ss-fact"><span class="tgs-ss-fact-ic">🕐</span><span class="tgs-ss-fact-k">Zugang</span><span class="tgs-ss-fact-v"><?php echo esc_html( $zugang ); ?></span></div>
        <?php endif; ?>
        <?php if ( $kosten ) : ?>
        <div class="tgs-ss-fact"><span class="tgs-ss-fact-ic">✓</span><span class="tgs-ss-fact-k">Kosten</span><span class="tgs-ss-fact-v"><?php echo esc_html( $kosten ); ?></span></div>
        <?php endif; ?>
        <?php if ( $park ) : ?>
        <div class="tgs-ss-fact"><span class="tgs-ss-fact-ic">🅿️</span><span class="tgs-ss-fact-k">Parkplätze</span><span class="tgs-ss-fact-v"><?php echo esc_html( $park ); ?></span></div>
        <?php endif; ?>
        <?php if ( $barr ) : ?>
        <div class="tgs-ss-fact"><span class="tgs-ss-fact-ic">♿</span><span class="tgs-ss-fact-k">Barrierefreiheit</span><span class="tgs-ss-fact-v"><?php echo esc_html( $barr ); ?></span></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php
    // Content (Freitext + Galerie) und Ausstattung nebeneinander
    $content = get_the_content();
    $two_col = $content && $ausst_items;
    if ( $content || $ausst_items ) :
    ?>
    <div class="tgs-section tgs-ss-body<?php echo $two_col ? '' : ' tgs-ss-body--single'; ?>">
        <?php if ( $content ) : ?>
        <div class="tgs-ss-content"><?php echo apply_filters( 'the_content', $content ); ?></div>
        <?php endif; ?>
        <?php if ( $ausst_items ) : ?>
        <aside class="tgs-ss-ausst">
            <div class="tgs-ss-ausst-title">🏋️ Ausstattung & Möglichkeiten</div>
            <ul class="tgs-ss-ausst-list">
                <?php foreach ( $ausst_items as $item ) : ?>
                    <li><?php echo esc_html( $item ); ?></li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ( $app_name && ( $app_ios || $app_andr ) ) : ?>
    <div class="tgs-section">
        <div class="tgs-ss-app">
            <div class="tgs-ss-app-ic">📱</div>
            <div class="tgs-ss-app-body">
                <div class="tgs-ss-app-name"><?php echo esc_html( $app_name ); ?></div>
                <?php if ( $app_desc ) : ?><div class="tgs-ss-app-desc"><?php echo esc_html( $app_desc ); ?></div><?php endif; ?>
            </div>
            <div class="tgs-ss-app-btns">
                <?php if ( $app_ios ) : ?><a href="<?php echo esc_url( $app_ios ); ?>" class="tgs-ss-app-btn" target="_blank" rel="noopener"> App&nbsp;Store</a><?php endif; ?>
                <?php if ( $app_andr ) : ?><a href="<?php echo esc_url( $app_andr ); ?>" class="tgs-ss-app-btn" target="_blank" rel="noopener">▶ Google&nbsp;Play</a><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $hat_kurse ) : ?>
    <div class="tgs-section">
        <div class="tgs-section-hd">
            <p class="tgs-section-title"><strong>Kurse & Trainings <?php echo $ort_name !== 'Wilhelm-Busch-Halle' ? 'am ' : 'in der '; echo esc_html( $ort_name ); ?></strong></p>
            <p class="tgs-section-more"><a href="/kurse">Alle Kurse →</a></p>
        </div>
        <?php echo do_shortcode( '[tgs_kurse_in_ort ort="' . esc_attr( $ort_name ) . '"]' ); ?>
    </div>
    <?php endif; ?>

    <?php
    // Weitere Sportstätten
    $andere = get_posts( array(
        'post_type'      => 'tgs_sportstaette',
        'posts_per_page' => -1,
        'post__not_in'   => array( $post_id ),
    ) );
    if ( $andere ) :
    ?>
    <div class="tgs-section-alt">
        <div class="tgs-section-hd">
            <p class="tgs-section-title"><strong>Weitere Sportstätten</strong></p>
        </div>
        <div class="tgs-ss-andere-grid">
            <?php foreach ( $andere as $a ) : ?>
            <a href="<?php echo get_permalink( $a->ID ); ?>" class="tgs-ss-andere-card">
                <div class="tgs-ss-andere-name"><?php echo esc_html( $a->post_title ); ?></div>
                <div class="tgs-ss-andere-addr"><?php echo esc_html( get_post_meta( $a->ID, '_tgs_adresse', true ) ); ?></div>
                <span class="tgs-ss-andere-link">Zur Sportstätte →</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_sportstaette_detail', 'tgs_shortcode_sportstaette_detail' );

/**
 * [tgs_sportstaetten_liste] — Alle Sportstätten als Karten
 */
function tgs_shortcode_sportstaetten_liste() {
    $orte = get_posts( array(
        'post_type'      => 'tgs_sportstaette',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    if ( empty( $orte ) ) return '<p>Keine Sportstätten vorhanden.</p>';

    ob_start();
    ?>
    <div class="tgs-ss-liste-grid">
        <?php foreach ( $orte as $ort ) :
            $adresse = get_post_meta( $ort->ID, '_tgs_adresse', true );
            $plz     = get_post_meta( $ort->ID, '_tgs_plz_ort', true );
            $typ     = get_post_meta( $ort->ID, '_tgs_ss_typ', true );
            $zugang  = get_post_meta( $ort->ID, '_tgs_ss_zugang', true );
        ?>
        <a href="<?php echo get_permalink( $ort->ID ); ?>" class="tgs-ss-liste-card">
            <?php if ( $typ ) : ?><span class="tgs-ss-liste-typ"><?php echo esc_html( $typ ); ?></span><?php endif; ?>
            <div class="tgs-ss-liste-name"><?php echo esc_html( $ort->post_title ); ?></div>
            <div class="tgs-ss-liste-addr"><?php echo esc_html( $adresse ); ?><?php if ($plz) echo '<br>' . esc_html($plz); ?></div>
            <?php if ( $zugang ) : ?>
                <div class="tgs-ss-liste-desc">🕐 <?php echo esc_html( $zugang ); ?></div>
            <?php endif; ?>
            <span class="tgs-ss-liste-link">Details ansehen →</span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_sportstaetten_liste', 'tgs_shortcode_sportstaetten_liste' );

/**
 * [tgs_breadcrumb] — dynamische Brotkrumen mit echtem Seitentitel
 */
function tgs_shortcode_breadcrumb() {
    $post_id = get_the_ID();
    if ( ! $post_id ) return '';

    $items = array( '<a href="' . esc_url( home_url( '/' ) ) . '">Startseite</a>' );

    $map = array(
        'tgs_sportstaette' => array( 'Sportstätten', '/sportstaetten' ),
        'tgs_abteilung'    => array( 'Abteilungen', '/abteilungen' ),
        'tgs_kurs'         => array( 'Kurse', '/kurse' ),
    );
    $pt = get_post_type( $post_id );
    if ( isset( $map[ $pt ] ) ) {
        $items[] = '<a href="' . esc_url( home_url( $map[ $pt ][1] ) ) . '">' . esc_html( $map[ $pt ][0] ) . '</a>';
    }

    $items[] = '<span class="tgs-bc-current">' . esc_html( get_the_title( $post_id ) ) . '</span>';

    return '<nav class="tgs-breadcrumb" aria-label="Brotkrumen"><p>' . implode( ' › ', $items ) . '</p></nav>';
}
add_shortcode( 'tgs_breadcrumb', 'tgs_shortcode_breadcrumb' );

/**
 * [tgs_abteilung_detail] — Abteilungs-Detailseite
 */
function tgs_shortcode_abteilung_detail() {
    $post_id = get_the_ID();
    if ( get_post_type( $post_id ) !== 'tgs_abteilung' ) return '';

    $icon     = get_post_meta( $post_id, '_tgs_abt_icon', true ) ?: '🏅';
    $leitung  = get_post_meta( $post_id, '_tgs_abt_leitung', true );
    $email    = get_post_meta( $post_id, '_tgs_abt_email', true );
    $stv      = get_post_meta( $post_id, '_tgs_abt_stv', true );
    $abt_name = get_the_title( $post_id );

    // Kurse dieser Abteilung finden (über Kategorie-Matching)
    $slug_map = array(
        'Fitness & Turnen' => array( 'fitness-kurse', 'fitness-training' ),
        'Handball'         => array(),
        'Tischtennis'      => array(),
        'Radsport'         => array( 'radsport', 'kinder-jugend' ),
    );

    ob_start();
    ?>
    <div class="tgs-abt-hero">
        <div class="tgs-abt-hero-inner">
            <div class="tgs-abt-hero-badge"><?php echo tgs_abteilung_icon_html( $post_id ); ?></div>
            <h1 class="tgs-abt-hero-h1"><?php echo esc_html( $abt_name ); ?></h1>
            <p class="tgs-abt-hero-sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
        </div>
    </div>

    <div class="tgs-abt-detail-layout">
        <div class="tgs-abt-detail-content">
            <?php the_content(); ?>
        </div>
        <div class="tgs-abt-detail-sidebar">
            <?php if ( $leitung ) : ?>
            <div class="tgs-kd-info-box">
                <div class="tgs-kd-info-title">Ansprechpartner</div>
                <div style="font-size:13px;font-weight:700;color:#1A2A1E;margin-bottom:.2rem;"><?php echo esc_html( $leitung ); ?></div>
                <div style="font-size:11px;color:#999;margin-bottom:.3rem;">Abteilungsleitung</div>
                <?php if ( $email ) : ?>
                    <a href="mailto:<?php echo esc_attr( $email ); ?>" style="font-size:11px;color:#3D5A40;font-weight:600;">✉️ <?php echo esc_html( $email ); ?></a>
                <?php endif; ?>
                <?php if ( $stv ) : ?>
                    <div style="margin-top:.6rem;padding-top:.5rem;border-top:1px solid #E4DDD0;">
                        <div style="font-size:12px;font-weight:600;color:#1A2A1E;"><?php echo esc_html( $stv ); ?></div>
                        <div style="font-size:10px;color:#999;">Stellvertreter</div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="tgs-kd-info-box">
                <div class="tgs-kd-info-title">Weitere Abteilungen</div>
                <?php
                $andere = get_posts( array(
                    'post_type'      => 'tgs_abteilung',
                    'posts_per_page' => -1,
                    'post__not_in'   => array( $post_id ),
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC',
                ) );
                foreach ( $andere as $a ) : ?>
                    <a href="<?php echo get_permalink( $a->ID ); ?>" class="tgs-kd-related-item">
                        <span class="tgs-rel-label"><?php echo tgs_abteilung_icon_html( $a->ID ); ?><?php echo esc_html( $a->post_title ); ?></span>
                        <span>→</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_abteilung_detail', 'tgs_shortcode_abteilung_detail' );

/**
 * [tgs_abteilungen_detail_liste] — Alle Abteilungen als ausführliche Karten
 */
function tgs_shortcode_abteilungen_detail_liste() {
    $abteilungen = get_posts( array(
        'post_type'      => 'tgs_abteilung',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ) );
    if ( empty( $abteilungen ) ) return '<p>Keine Abteilungen vorhanden.</p>';

    $html = '<div class="tgs-abt-liste">';
    foreach ( $abteilungen as $abt ) {
        $icon    = get_post_meta( $abt->ID, '_tgs_abt_icon', true ) ?: '🏅';
        $leitung = get_post_meta( $abt->ID, '_tgs_abt_leitung', true );
        $excerpt = get_the_excerpt( $abt->ID );
        $url     = get_permalink( $abt->ID );

        $html .= '<div class="tgs-abt2-card" onclick="window.location=\'' . esc_url( $url ) . '\'">';
        $html .= '<span class="tgs-abt2-top">';
        $html .= '<span class="tgs-abt2-badge">' . tgs_abteilung_icon_html( $abt->ID ) . '</span>';
        $html .= '<span class="tgs-abt2-name">' . esc_html( $abt->post_title ) . '</span>';
        $html .= '</span>';
        if ( $excerpt ) {
            $html .= '<span class="tgs-abt2-desc">' . esc_html( $excerpt ) . '</span>';
        }
        $html .= '<span class="tgs-abt2-foot">';
        if ( $leitung ) {
            $html .= '<span class="tgs-abt2-ap">Ansprechpartner: <b>' . esc_html( $leitung ) . '</b></span>';
        } else {
            $html .= '<span class="tgs-abt2-ap"></span>';
        }
        $html .= '<span class="tgs-abt2-link">Zur Abteilung →</span>';
        $html .= '</span>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}
add_shortcode( 'tgs_abteilungen_detail_liste', 'tgs_shortcode_abteilungen_detail_liste' );
