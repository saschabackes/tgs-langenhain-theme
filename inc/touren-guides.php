<?php
/**
 * Guides — Menschen, die Touren empfehlen.
 *
 * Der Gedanke: Eine Tour ist eine Linie auf der Karte. „Olafs Lieblingsrunde,
 * weil oben die Aussicht kommt" ist eine Empfehlung von einem Nachbarn — das
 * kann keine Tourenplattform.
 *
 * Rotation: Der Tipp wechselt automatisch pro Kalenderwoche — rein rechnerisch,
 * ohne Cronjob und ohne dass jemand wöchentlich etwas umstellen muss. Alles,
 * was Handarbeit braucht, schläft in einem Verein nach drei Wochen ein.
 *
 * Datenschutz: Guide-Namen laufen durch dieselbe Schutzfunktion wie die
 * Kursleitungen (Name nicht im Quelltext, erst per JavaScript) — ein Guide mit
 * Foto, Name UND seinen Hausrunden wäre sonst ein fertiges Profil.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * CPT
 * ========================================================================= */
function tgs_register_cpt_guide() {
    register_post_type( 'tgs_guide', array(
        'labels' => array(
            'name'          => 'Guides',
            'singular_name' => 'Guide',
            'menu_name'     => 'Guides',
            'add_new'       => 'Neuen Guide anlegen',
            'add_new_item'  => 'Neuen Guide anlegen',
            'edit_item'     => 'Guide bearbeiten',
            'all_items'     => 'Alle Guides',
        ),
        'public'        => true,
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'guides' ),
        'has_archive'   => false,
        'menu_position' => 7,
        'menu_icon'     => 'dashicons-groups',
        'supports'      => array( 'title', 'editor', 'thumbnail' ),
    ) );
}
add_action( 'init', 'tgs_register_cpt_guide' );

/** Guides, die mindestens eine veröffentlichte Tour empfehlen. */
function tgs_guides_mit_touren() {
    $guides = get_posts( array(
        'post_type' => 'tgs_guide', 'post_status' => 'publish',
        'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC',
    ) );
    $out = array();
    foreach ( $guides as $g ) {
        if ( tgs_guide_touren( $g->ID ) ) $out[] = $g;
    }
    return $out;
}

/** Touren eines Guides. */
function tgs_guide_touren( $guide_id, $limit = -1 ) {
    return get_posts( array(
        'post_type' => 'tgs_tour', 'post_status' => 'publish',
        'numberposts' => $limit, 'orderby' => 'date', 'order' => 'DESC',
        'meta_query' => array( array( 'key' => '_tgs_tour_guide_id', 'value' => $guide_id ) ),
    ) );
}

/**
 * Fortlaufende Wochennummer.
 *
 * Bewusst berechnet statt gespeichert: gleiche Woche = gleicher Tipp für alle,
 * neue Woche = neuer Tipp. Kein Cron, kein Zustand, nichts, was hängen bleibt.
 *
 * Gezählt wird durchgehend seit 1970, NICHT als Kalenderwoche pro Jahr: Ein
 * Jahr hat mal 52, mal 53 Wochen — bei einer Formel aus Jahr+KW bliebe der
 * Tipp am Jahreswechsel je nach Anzahl der Guides zwei Wochen lang derselbe.
 * Der Versatz um 3 Tage schiebt den Wechsel auf Montag (der 1.1.1970 war ein
 * Donnerstag).
 */
function tgs_wochen_zaehler() {
    $t = (int) current_time( 'timestamp' ) - 3 * DAY_IN_SECONDS;
    return (int) floor( $t / WEEK_IN_SECONDS );
}

/** Rotationsindex aus einem beliebigen Zähler. */
function tgs_rotation( $zaehler, $anzahl ) {
    if ( $anzahl < 1 ) return 0;
    return ( ( (int) $zaehler % $anzahl ) + $anzahl ) % $anzahl;
}

/** Guide-Name crawler-geschützt — wie bei den Kursleitungen. */
function tgs_guide_name_html( $guide_id ) {
    return tgs_trainer_name_html( get_the_title( $guide_id ), 'unserem Guide' );
}

/* =========================================================================
 * [tgs_tour_tipp] — der wechselnde Tourentipp
 * ========================================================================= */
/**
 * Attribute:
 *   guide=""      feste Guide-ID statt Rotation
 *   touren="3"    wie viele Touren des Guides gezeigt werden
 */
function tgs_tour_tipp_shortcode( $atts ) {
    $a = shortcode_atts( array( 'guide' => '', 'touren' => 3 ), $atts );

    $guides = tgs_guides_mit_touren();
    if ( empty( $guides ) ) return '';

    $zaehler = tgs_wochen_zaehler();

    if ( $a['guide'] ) {
        $guide = get_post( intval( $a['guide'] ) );
        if ( ! $guide || $guide->post_type !== 'tgs_guide' ) return '';
        $rotiert = false;
        $runde   = $zaehler;
    } else {
        $guide   = $guides[ tgs_rotation( $zaehler, count( $guides ) ) ];
        $rotiert = count( $guides ) > 1;
        // Wie oft war dieser Guide schon dran? Nur so bekommt er beim nächsten
        // Mal eine ANDERE Lieblingstour. Würde der Tour-Versatz direkt aus der
        // Woche kommen, liefe er mit der Guide-Rotation im Gleichschritt — und
        // jeder Guide zeigte für immer dieselbe Tour.
        $runde = (int) floor( $zaehler / max( 1, count( $guides ) ) );
    }

    $touren = tgs_guide_touren( $guide->ID );
    if ( empty( $touren ) ) return '';

    $versatz = tgs_rotation( $runde, count( $touren ) );
    if ( $versatz > 0 ) {
        $touren = array_merge( array_slice( $touren, $versatz ), array_slice( $touren, 0, $versatz ) );
    }
    $rotiert = $rotiert || count( $touren ) > 1;

    $held    = array_shift( $touren );
    $weitere = array_slice( $touren, 0, max( 0, intval( $a['touren'] ) - 1 ) );

    $d      = tgs_tour_daten( $held->ID );
    $arten  = tgs_tour_arten();
    $zitat  = get_post_meta( $held->ID, '_tgs_tour_zitat', true );
    $foto   = get_post_thumbnail_id( $guide->ID );
    $intro  = trim( wp_strip_all_tags( get_post_field( 'post_content', $guide->ID ) ) );

    ob_start();
    ?>
    <div class="tgs-tipp">
        <div class="tgs-tipp-head">
            <?php if ( $foto ) : ?>
                <div class="tgs-tipp-foto"><?php echo wp_get_attachment_image( $foto, array( 96, 96 ), false, array( 'alt' => 'Guide', 'loading' => 'lazy' ) ); ?></div>
            <?php endif; ?>
            <div class="tgs-tipp-head-txt">
                <?php if ( $rotiert ) : ?><span class="tgs-tipp-label">Tourentipp der Woche</span><?php endif; ?>
                <h2 class="tgs-tipp-title">Lieblingstouren von <?php echo tgs_guide_name_html( $guide->ID ); ?></h2>
                <?php if ( $intro ) : ?><p class="tgs-tipp-intro"><?php echo esc_html( wp_trim_words( $intro, 28 ) ); ?></p><?php endif; ?>
            </div>
        </div>

        <a class="tgs-tipp-held" href="<?php echo esc_url( get_permalink( $held->ID ) ); ?>">
            <span class="tgs-tipp-held-map"><?php echo tgs_tour_track_svg( $held->ID, 420, 260 ); ?></span>
            <span class="tgs-tipp-held-body">
                <span class="tgs-tipp-held-title"><?php echo esc_html( $held->post_title ); ?></span>
                <span class="tgs-tipp-held-meta"><?php
                    $m = array();
                    if ( $d['km'] > 0 )     $m[] = number_format_i18n( $d['km'], 1 ) . ' km';
                    if ( $d['hm_auf'] > 0 ) $m[] = '▲ ' . number_format_i18n( $d['hm_auf'] ) . ' m';
                    if ( isset( $arten[ $d['art'] ] ) ) $m[] = $arten[ $d['art'] ];
                    echo esc_html( implode( ' · ', $m ) );
                ?></span>
                <?php if ( $zitat ) : ?>
                    <span class="tgs-tipp-zitat">„<?php echo esc_html( $zitat ); ?>"</span>
                <?php endif; ?>
                <span class="tgs-tipp-mehr">Tour ansehen →</span>
            </span>
        </a>

        <?php if ( $weitere ) : ?>
        <div class="tgs-tipp-weitere">
            <?php foreach ( $weitere as $w ) : $wd = tgs_tour_daten( $w->ID ); ?>
                <a class="tgs-tipp-mini" href="<?php echo esc_url( get_permalink( $w->ID ) ); ?>">
                    <span class="tgs-tipp-mini-map"><?php echo tgs_tour_track_svg( $w->ID, 200, 110 ); ?></span>
                    <span class="tgs-tipp-mini-txt">
                        <span class="tgs-tipp-mini-title"><?php echo esc_html( $w->post_title ); ?></span>
                        <span class="tgs-tipp-mini-meta"><?php echo $wd['km'] > 0 ? esc_html( number_format_i18n( $wd['km'], 1 ) . ' km' ) : ''; ?><?php echo $wd['hm_auf'] > 0 ? esc_html( ' · ▲ ' . number_format_i18n( $wd['hm_auf'] ) . ' m' ) : ''; ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $rotiert ) : ?>
            <p class="tgs-tipp-foot">Wechselt jede Woche · <a href="<?php echo esc_url( get_post_type_archive_link( 'tgs_tour' ) ); ?>">alle Touren ansehen</a></p>
        <?php else : ?>
            <p class="tgs-tipp-foot"><a href="<?php echo esc_url( get_post_type_archive_link( 'tgs_tour' ) ); ?>">Alle Touren ansehen</a></p>
        <?php endif; ?>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}
add_shortcode( 'tgs_tour_tipp', 'tgs_tour_tipp_shortcode' );

/* =========================================================================
 * Guide-Seite
 * ========================================================================= */
function tgs_guide_detail_shortcode() {
    $id = get_the_ID();
    if ( ! $id || get_post_type( $id ) !== 'tgs_guide' ) return '';
    $foto   = get_post_thumbnail_id( $id );
    $touren = tgs_guide_touren( $id );

    ob_start();
    ?>
    <div class="tgs-section">
        <div class="tgs-tipp-head">
            <?php if ( $foto ) : ?>
                <div class="tgs-tipp-foto"><?php echo wp_get_attachment_image( $foto, array( 96, 96 ), false, array( 'alt' => 'Guide', 'loading' => 'lazy' ) ); ?></div>
            <?php endif; ?>
            <div class="tgs-tipp-head-txt">
                <span class="tgs-tipp-label">Guide</span>
                <h1 class="tgs-tipp-title"><?php echo tgs_guide_name_html( $id ); ?></h1>
            </div>
        </div>
        <div class="tgs-kd-desc"><?php echo apply_filters( 'the_content', get_post_field( 'post_content', $id ) ); ?></div>
        <?php if ( $touren ) : ?>
            <h2 class="tgs-bew-h">Lieblingstouren</h2>
            <div class="tgs-touren-grid"><?php foreach ( $touren as $t ) : $td = tgs_tour_daten( $t->ID ); ?><a class="tgs-tour-card" href="<?php echo esc_url( get_permalink( $t->ID ) ); ?>"><span class="tgs-tour-card-map"><?php echo tgs_tour_track_svg( $t->ID, 320, 180 ); ?></span><span class="tgs-tour-card-body"><span class="tgs-tour-card-title"><?php echo esc_html( $t->post_title ); ?></span><span class="tgs-tour-card-meta"><?php
                $m = array();
                if ( $td['km'] > 0 )     $m[] = number_format_i18n( $td['km'], 1 ) . ' km';
                if ( $td['hm_auf'] > 0 ) $m[] = '▲ ' . number_format_i18n( $td['hm_auf'] ) . ' m';
                echo esc_html( implode( ' · ', $m ) );
            ?></span></span></a><?php endforeach; ?></div>
        <?php endif; ?>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}
add_shortcode( 'tgs_guide_detail', 'tgs_guide_detail_shortcode' );
