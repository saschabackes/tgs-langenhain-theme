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
function tgs_shortcode_kurstabelle( $atts ) {
    $atts = shortcode_atts( array(
        'limit'     => -1,
        'kategorie' => '',
        'kompakt'   => 'nein',
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

    ob_start();
    ?>
    <div class="tgs-kurstabelle-wrap">
        <?php if ( ! $is_kompakt && ! empty( $kategorien ) ) : ?>
        <div class="tgs-chip-row">
            <span class="tgs-chip active" data-filter="alle">Alle</span>
            <?php foreach ( $kategorien as $kat ) : ?>
                <span class="tgs-chip" data-filter="<?php echo esc_attr( $kat->slug ); ?>"><?php echo esc_html( $kat->name ); ?></span>
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
                    $status_class = $status === 'warteliste' ? 'tgs-status-warteliste' : 'tgs-status-frei';
                    $status_label = $status === 'warteliste' ? '⚠ Warteliste' : '✓ Freie Plätze';
                    $link_label   = $status === 'warteliste' ? 'Warteliste →' : 'Details →';
                ?>
                <tr class="tgs-kurs-row" data-kategorie="<?php echo esc_attr( $kat_slug ); ?>">
                    <?php if ( ! $is_kompakt ) : ?>
                    <td><span class="tgs-kurs-kategorie"><?php echo esc_html( $kat_name ); ?></span></td>
                    <?php endif; ?>
                    <td><a href="<?php echo get_permalink( $kurs->ID ); ?>" class="tgs-kurs-name"><?php echo esc_html( $kurs->post_title ); ?></a></td>
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

    ob_start();
    ?>
    <div class="tgs-abt-grid">
        <?php foreach ( $abteilungen as $abt ) :
            $icon  = get_post_meta( $abt->ID, '_tgs_abt_icon', true ) ?: '🏅';
            $excerpt = get_the_excerpt( $abt->ID );
        ?>
        <a href="<?php echo get_permalink( $abt->ID ); ?>" class="tgs-abt-card">
            <div class="tgs-abt-icon"><?php echo esc_html( $icon ); ?></div>
            <div class="tgs-abt-name"><?php echo esc_html( $abt->post_title ); ?></div>
            <div class="tgs-abt-desc"><?php echo esc_html( $excerpt ); ?></div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
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

    ob_start();
    ?>
    <div class="tgs-kontakt-grid">
        <?php foreach ( $abteilungen as $abt ) :
            $name  = get_post_meta( $abt->ID, '_tgs_abt_leitung', true );
            $email = get_post_meta( $abt->ID, '_tgs_abt_email', true );
            if ( ! $name ) continue;
        ?>
        <div class="tgs-kontakt-card">
            <div class="tgs-kontakt-role"><?php echo esc_html( $abt->post_title ); ?></div>
            <div class="tgs-kontakt-name"><?php echo esc_html( $name ); ?></div>
            <?php if ( $email ) : ?>
                <a href="mailto:<?php echo esc_attr( $email ); ?>" class="tgs-kontakt-mail"><?php echo esc_html( $email ); ?></a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_ansprechpartner', 'tgs_shortcode_ansprechpartner' );

/**
 * [tgs_sponsoren] — Sponsorenleiste
 * 
 * Erstmal statisch, kann später als eigener CPT oder Widget umgebaut werden.
 */
function tgs_shortcode_sponsoren() {
    // TODO: Später als CPT oder Options-Page. Erstmal hardcoded.
    $sponsoren = array(
        array( 'name' => 'Mobau Braun', 'url' => 'https://mobau-braun.de' ),
        array( 'name' => 'KP International', 'url' => 'https://www.kp-international.de' ),
        array( 'name' => 'Domotec', 'url' => 'http://www.hm-domotec.de' ),
        array( 'name' => 'Dörr & Neumann', 'url' => 'https://www.doerrundneumann.de' ),
        array( 'name' => 'Optik Waller', 'url' => 'https://www.optik-waller.de' ),
        array( 'name' => 'Simon Haustechnik', 'url' => 'https://www.haustechnik-simon.de' ),
    );

    ob_start();
    ?>
    <div class="tgs-sponsor-section">
        <div class="tgs-sponsor-hd">Unsere Partner &amp; Sponsoren</div>
        <div class="tgs-sponsor-row">
            <?php foreach ( $sponsoren as $s ) : ?>
                <a href="<?php echo esc_url( $s['url'] ); ?>" class="tgs-sponsor" target="_blank" rel="noopener"><?php echo esc_html( $s['name'] ); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
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
    $zielgr    = get_post_meta( $post_id, '_tgs_zielgruppe', true );
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
            <a href="#tgs-anmeldung" class="tgs-kd-btn"><?php echo $btn_label; ?></a>
            <div class="<?php echo $status_class; ?>"><?php echo $status_label; ?></div>
        </div>
    </div>

    <div class="tgs-kd-body">
        <div class="tgs-kd-content">
            <?php the_content(); ?>

            <?php if ( $ap_name ) : ?>
            <div class="tgs-kd-ap">
                <strong>Ansprechpartner:</strong><br>
                <?php echo esc_html( $ap_name ); ?>
                <?php if ( $ap_email ) : ?> · <a href="mailto:<?php echo esc_attr( $ap_email ); ?>"><?php echo esc_html( $ap_email ); ?></a><?php endif; ?>
                <?php if ( $ap_tel ) : ?> · <?php echo esc_html( $ap_tel ); ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="tgs-kd-sidebar">
            <div class="tgs-kd-info-box">
                <div class="tgs-kd-info-title">Auf einen Blick</div>
                <?php if ( $tag ) : ?><div class="tgs-kd-info-row"><strong>Tag</strong><span><?php echo esc_html( $tag ); ?></span></div><?php endif; ?>
                <?php if ( $zeit ) : ?><div class="tgs-kd-info-row"><strong>Uhrzeit</strong><span><?php echo esc_html( $zeit_display ); ?></span></div><?php endif; ?>
                <?php if ( $ort ) : ?><div class="tgs-kd-info-row"><strong>Ort</strong><span><?php echo esc_html( $ort ); ?></span></div><?php endif; ?>
                <?php if ( $zielgr ) : ?><div class="tgs-kd-info-row"><strong>Zielgruppe</strong><span><?php echo esc_html( $zielgr ); ?></span></div><?php endif; ?>
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
