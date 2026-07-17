<?php
/**
 * Touren — Frontend.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Karten-Fassade.
 *
 * Vor dem Klick: selbst gerenderter Streckenverlauf als SVG — null externe
 * Requests, lädt sofort, funktioniert ohne JavaScript. Erst auf Klick werden
 * Leaflet (aus dem Theme) und die Kartenkacheln (von OpenStreetMap) geladen.
 * Dasselbe Muster wie beim Imagefilm: die Entscheidung liegt beim Nutzer.
 */
function tgs_tour_karte_html( $tour_id ) {
    if ( ! tgs_tour_hat_track( $tour_id ) ) return '';
    $d = tgs_tour_daten( $tour_id );

    ob_start();
    ?>
    <div class="tgs-tour-map"
         data-track="<?php echo esc_attr( wp_json_encode( $d['track'] ) ); ?>"
         data-bounds="<?php echo esc_attr( wp_json_encode( $d['bounds'] ) ); ?>"
         data-start="<?php echo esc_attr( wp_json_encode( $d['start'] ) ); ?>"
         data-ende="<?php echo esc_attr( wp_json_encode( $d['ende'] ) ); ?>"
         data-rund="<?php echo $d['rund'] ? '1' : '0'; ?>"
         data-css="<?php echo esc_attr( TGS_URI . '/assets/vendor/leaflet/leaflet.css?v=' . TGS_VERSION ); ?>"
         data-js="<?php echo esc_attr( TGS_URI . '/assets/vendor/leaflet/leaflet.js?v=' . TGS_VERSION ); ?>"
         data-title="<?php echo esc_attr( get_the_title( $tour_id ) ); ?>">
        <div class="tgs-tour-map-preview"><?php echo tgs_tour_track_svg( $tour_id ); ?></div>
        <div class="tgs-tour-map-overlay">
            <button type="button" class="tgs-tour-map-load">Interaktive Karte laden</button>
            <p class="tgs-tour-map-note">Erst beim Klick werden Kartenkacheln von OpenStreetMap geladen — dabei wird deine IP-Adresse an OpenStreetMap übertragen. Vorher verlässt nichts diese Seite.</p>
        </div>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

/**
 * [tgs_tour_detail] — die Tourseite.
 */
function tgs_tour_detail_shortcode() {
    $id = get_the_ID();
    if ( ! $id || get_post_type( $id ) !== 'tgs_tour' ) return '';
    $d      = tgs_tour_daten( $id );
    $arten  = tgs_tour_arten();
    $levels = tgs_tour_level();

    ob_start();
    ?>
    <div class="tgs-section tgs-kd">
        <div class="tgs-kd-grid">

            <div class="tgs-kd-main">
                <h1 class="tgs-kd-title"><?php echo esc_html( get_the_title( $id ) ); ?></h1>

                <?php
                $badges = array();
                if ( isset( $arten[ $d['art'] ] ) )   $badges[] = $arten[ $d['art'] ];
                if ( isset( $levels[ $d['level'] ] ) ) $badges[] = $levels[ $d['level'] ];
                if ( $d['rund'] ) $badges[] = 'Rundkurs';
                if ( $badges ) {
                    echo '<div class="tgs-tour-badges">';
                    foreach ( $badges as $b ) echo '<span class="tgs-tour-badge">' . esc_html( $b ) . '</span>';
                    echo '</div>';
                }
                ?>

                <?php echo tgs_tour_karte_html( $id ); ?>
                <?php echo tgs_tour_fakten_html( $id ); ?>

                <?php if ( $d['profil'] ) : ?>
                    <div class="tgs-tour-profil-box">
                        <div class="tgs-tour-profil-h">Höhenprofil</div>
                        <?php echo tgs_tour_profil_svg( $id ); ?>
                    </div>
                <?php endif; ?>

                <div class="tgs-kd-desc"><?php echo apply_filters( 'the_content', get_post_field( 'post_content', $id ) ); ?></div>

                <?php
                // Die Verknüpfung, die Komoot nicht kann: Die Tour gehört zu
                // einer echten Gruppe, die sie fährt.
                if ( $d['kurs_id'] && get_post_status( $d['kurs_id'] ) === 'publish' ) :
                    $termin = tgs_kurs_termin( $d['kurs_id'] );
                    $wann   = trim( $termin['tag'] . ' ' . tgs_zeit_display( $termin['zeit'], $termin['ende'] ) );
                ?>
                <div class="tgs-tour-gruppe">
                    <div class="tgs-tour-gruppe-txt">
                        <strong>Diese Runde fahren wir gemeinsam</strong>
                        <span><?php echo esc_html( get_the_title( $d['kurs_id'] ) ); ?><?php echo $wann ? ' · ' . esc_html( $wann ) : ''; ?></span>
                    </div>
                    <a class="tgs-abo-btn" href="<?php echo esc_url( get_permalink( $d['kurs_id'] ) ); ?>">Zur Gruppe</a>
                </div>
                <?php endif; ?>

                <?php echo tgs_render_tour_bewertungen( $id ); ?>
            </div>

            <div class="tgs-kd-sidebar">
                <div class="tgs-kd-info-box">
                    <div class="tgs-kd-info-title">Auf einen Blick</div>
                    <?php if ( $d['km'] > 0 ) : ?><div class="tgs-kd-info-row"><strong>Distanz</strong><span><?php echo esc_html( number_format_i18n( $d['km'], 1 ) ); ?> km</span></div><?php endif; ?>
                    <?php if ( $d['hm_auf'] > 0 ) : ?><div class="tgs-kd-info-row"><strong>Bergauf</strong><span><?php echo esc_html( number_format_i18n( $d['hm_auf'] ) ); ?> m</span></div><?php endif; ?>
                    <?php if ( $d['ele_max'] > 0 ) : ?><div class="tgs-kd-info-row"><strong>Höhe</strong><span><?php echo esc_html( $d['ele_min'] ); ?>–<?php echo esc_html( $d['ele_max'] ); ?> m</span></div><?php endif; ?>
                    <?php if ( $d['dauer'] ) : ?><div class="tgs-kd-info-row"><strong>Dauer</strong><span><?php echo esc_html( $d['dauer'] ); ?></span></div><?php endif; ?>
                    <?php if ( isset( $levels[ $d['level'] ] ) ) : ?><div class="tgs-kd-info-row"><strong>Level</strong><span><?php echo esc_html( $levels[ $d['level'] ] ); ?></span></div><?php endif; ?>
                    <?php if ( $d['start_id'] && get_post_status( $d['start_id'] ) === 'publish' ) : ?>
                        <div class="tgs-kd-info-row"><strong>Start</strong><span><a href="<?php echo esc_url( get_permalink( $d['start_id'] ) ); ?>"><?php echo esc_html( get_the_title( $d['start_id'] ) ); ?></a></span></div>
                    <?php endif; ?>
                    <?php if ( $d['einkehr'] ) : ?><div class="tgs-kd-info-row"><strong>Einkehr</strong><span><?php echo esc_html( $d['einkehr'] ); ?></span></div><?php endif; ?>
                </div>

                <div class="tgs-kd-info-box">
                    <div class="tgs-kd-info-title">Mitnehmen</div>
                    <div class="tgs-abo">
                        <?php $gpx = tgs_tour_gpx_url( $id ); if ( $gpx ) : ?>
                            <a class="tgs-abo-btn" href="<?php echo esc_url( $gpx ); ?>" download>
                                <span class="tgs-abo-icon" aria-hidden="true">⤓</span><span>GPX herunterladen</span>
                            </a>
                        <?php endif; ?>
                        <?php if ( $d['komoot'] ) : ?>
                            <a class="tgs-abo-copy" href="<?php echo esc_url( $d['komoot'] ); ?>" target="_blank" rel="noopener noreferrer">Bei Komoot ansehen ↗</a>
                        <?php endif; ?>
                        <p class="tgs-abo-hint">Die GPX läuft auf Garmin, Wahoo, Komoot &amp; Co. — und bleibt deine, auch offline.</p>
                    </div>
                </div>

                <?php
                $weitere = get_posts( array(
                    'post_type' => 'tgs_tour', 'posts_per_page' => 4, 'post__not_in' => array( $id ),
                    'meta_query' => $d['art'] ? array( array( 'key' => '_tgs_tour_art', 'value' => $d['art'] ) ) : array(),
                ) );
                if ( $weitere ) :
                ?>
                <div class="tgs-kd-info-box">
                    <div class="tgs-kd-info-title">Weitere Touren</div>
                    <div class="tgs-kd-related"><?php foreach ( $weitere as $w ) :
                        $wd = tgs_tour_daten( $w->ID );
                    ?><a href="<?php echo esc_url( get_permalink( $w->ID ) ); ?>" class="tgs-kd-related-item"><?php echo esc_html( $w->post_title ); ?><span><?php echo $wd['km'] > 0 ? esc_html( number_format_i18n( $wd['km'], 1 ) . ' km' ) : ''; ?></span></a><?php endforeach; ?></div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_tour_detail', 'tgs_tour_detail_shortcode' );

/**
 * [tgs_touren] — Tourenliste mit Filter.
 * Attribute: art="mtb" · level="leicht" · anzahl="12" · filter="1"
 */
function tgs_touren_shortcode( $atts ) {
    $a = shortcode_atts( array( 'art' => '', 'level' => '', 'anzahl' => 12, 'filter' => '1' ), $atts );

    $mq = array();
    if ( $a['art'] )   $mq[] = array( 'key' => '_tgs_tour_art', 'value' => sanitize_key( $a['art'] ) );
    if ( $a['level'] ) $mq[] = array( 'key' => '_tgs_tour_level', 'value' => sanitize_key( $a['level'] ) );

    $touren = get_posts( array(
        'post_type' => 'tgs_tour', 'post_status' => 'publish',
        'posts_per_page' => intval( $a['anzahl'] ), 'orderby' => 'title', 'order' => 'ASC',
        'meta_query' => $mq,
    ) );
    if ( empty( $touren ) ) return '';

    $arten  = tgs_tour_arten();
    $levels = tgs_tour_level();

    // Nur Filter anbieten, die auch vorkommen — leere Chips sind Frust.
    $vorhanden = array();
    foreach ( $touren as $t ) {
        $art = get_post_meta( $t->ID, '_tgs_tour_art', true );
        if ( $art ) $vorhanden[ $art ] = true;
    }

    ob_start();
    ?>
    <div class="tgs-touren">
        <?php if ( $a['filter'] === '1' && count( $vorhanden ) > 1 && ! $a['art'] ) : ?>
        <div class="tgs-chip-row" data-filter-group="tourart">
            <button class="tgs-chip active" data-filter="alle">Alle</button>
            <?php foreach ( $arten as $slug => $lbl ) : if ( empty( $vorhanden[ $slug ] ) ) continue; ?>
                <button class="tgs-chip" data-filter="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $lbl ); ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="tgs-touren-grid"><?php foreach ( $touren as $t ) :
            $d = tgs_tour_daten( $t->ID );
        ?><a class="tgs-tour-card tgs-filter-item" data-tourart="<?php echo esc_attr( $d['art'] ); ?>" href="<?php echo esc_url( get_permalink( $t->ID ) ); ?>"><span class="tgs-tour-card-map"><?php echo tgs_tour_track_svg( $t->ID, 320, 180 ); ?></span><span class="tgs-tour-card-body"><span class="tgs-tour-card-title"><?php echo esc_html( $t->post_title ); ?></span><span class="tgs-tour-card-meta"><?php
            $m = array();
            if ( $d['km'] > 0 )     $m[] = number_format_i18n( $d['km'], 1 ) . ' km';
            if ( $d['hm_auf'] > 0 ) $m[] = '▲ ' . number_format_i18n( $d['hm_auf'] ) . ' m';
            if ( isset( $arten[ $d['art'] ] ) ) $m[] = $arten[ $d['art'] ];
            echo esc_html( implode( ' · ', $m ) );
        ?></span></span></a><?php endforeach; ?></div>
    </div>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}
add_shortcode( 'tgs_touren', 'tgs_touren_shortcode' );
