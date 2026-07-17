<?php
/**
 * Touren — GPX-basierte Rad-/Lauf-/Wanderrouten im Taunus.
 *
 * Grundgedanke: Die GPX-Datei ist das Vereinsgut und liegt bei uns. Komoot &
 * Co. sind nur zusätzliche Kanäle, nie die Quelle. Beim Speichern wird die
 * Datei EINMAL ausgewertet (Distanz, Höhenmeter, Streckenzug, Profil) und das
 * Ergebnis als Meta abgelegt — nicht bei jedem Seitenaufruf neu gerechnet.
 *
 * Datenschutz: Die Karte lädt erst auf Klick (wie beim Imagefilm) — vorher
 * geht keine IP an einen Kartenserver. Leaflet liegt selbst gehostet im Theme.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * CPT
 * ========================================================================= */
function tgs_register_cpt_tour() {
    register_post_type( 'tgs_tour', array(
        'labels' => array(
            'name'          => 'Touren',
            'singular_name' => 'Tour',
            'menu_name'     => 'Touren',
            'add_new'       => 'Neue Tour anlegen',
            'add_new_item'  => 'Neue Tour anlegen',
            'edit_item'     => 'Tour bearbeiten',
            'all_items'     => 'Alle Touren',
            'not_found'     => 'Keine Touren gefunden',
        ),
        'public'        => true,
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'touren' ),
        'has_archive'   => true,
        'menu_position' => 6,
        'menu_icon'     => 'dashicons-location-alt',
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
    ) );
}
add_action( 'init', 'tgs_register_cpt_tour' );

/** Tour-Arten (Slug => Label). Slugs werden auch für die Filter-Chips genutzt. */
function tgs_tour_arten() {
    return array(
        'mtb'    => 'MTB',
        'gravel' => 'Gravel',
        'rennrad'=> 'Rennrad',
        'ebike'  => 'E-Bike',
        'wandern'=> 'Wandern',
        'laufen' => 'Laufen',
    );
}

/** Schwierigkeit (Slug => Label). Vereinssprache statt Zahlen. */
function tgs_tour_level() {
    return array(
        'leicht'  => 'Leicht · familientauglich',
        'mittel'  => 'Mittel',
        'schwer'  => 'Anspruchsvoll',
    );
}

/* =========================================================================
 * GPX auswerten
 * ========================================================================= */

/** Entfernung zweier Punkte in Metern (Haversine). */
function tgs_geo_dist( $lat1, $lon1, $lat2, $lon2 ) {
    $r = 6371000.0;
    $p1 = deg2rad( $lat1 ); $p2 = deg2rad( $lat2 );
    $dp = deg2rad( $lat2 - $lat1 ); $dl = deg2rad( $lon2 - $lon1 );
    $a = sin( $dp / 2 ) * sin( $dp / 2 ) + cos( $p1 ) * cos( $p2 ) * sin( $dl / 2 ) * sin( $dl / 2 );
    return $r * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
}

/**
 * Punkte aus einer GPX-Datei lesen.
 * Unterstützt <trkpt> (aufgezeichnet/geplant) und ersatzweise <rtept> (Route).
 *
 * @return array Liste von array( lat, lon, ele|null )
 */
function tgs_gpx_punkte( $pfad ) {
    if ( ! file_exists( $pfad ) || ! is_readable( $pfad ) ) return array();

    // LIBXML_NONET + kein NOENT: keine externen Entities/Netzwerkzugriffe (XXE).
    $xml = @simplexml_load_file( $pfad, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA );
    if ( ! $xml ) return array();

    $pts = array();
    $lese = function ( $knoten ) use ( &$pts ) {
        foreach ( $knoten as $p ) {
            $lat = isset( $p['lat'] ) ? (float) $p['lat'] : null;
            $lon = isset( $p['lon'] ) ? (float) $p['lon'] : null;
            if ( $lat === null || $lon === null ) continue;
            if ( $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180 ) continue;
            $ele = ( isset( $p->ele ) && (string) $p->ele !== '' ) ? (float) $p->ele : null;
            $pts[] = array( $lat, $lon, $ele );
        }
    };

    foreach ( $xml->trk as $trk ) {
        foreach ( $trk->trkseg as $seg ) $lese( $seg->trkpt );
    }
    if ( empty( $pts ) ) {
        foreach ( $xml->rte as $rte ) $lese( $rte->rtept );
    }
    return $pts;
}

/**
 * Höhenmeter aus verrauschten GPS-Daten.
 *
 * Wichtig: Rohe GPX-Höhen schwanken um mehrere Meter, auch wenn man geradeaus
 * fährt. Summiert man jede kleine Schwankung, kommen aus einer 300-hm-Runde
 * schnell 800 hm — eine Zahl, der niemand mehr traut. Deshalb erst glätten
 * (gleitender Mittelwert), dann nur Anstiege über einer Schwelle zählen.
 *
 * @return array( auf, ab, min, max )
 */
function tgs_gpx_hoehenmeter( $eles, $fenster = 5, $schwelle = 2.0 ) {
    $eles = array_values( array_filter( $eles, function ( $e ) { return $e !== null; } ) );
    $n = count( $eles );
    if ( $n < 2 ) return array( 'auf' => 0, 'ab' => 0, 'min' => 0, 'max' => 0 );

    // Glätten
    $glatt = array();
    for ( $i = 0; $i < $n; $i++ ) {
        $von = max( 0, $i - $fenster ); $bis = min( $n - 1, $i + $fenster );
        $glatt[] = array_sum( array_slice( $eles, $von, $bis - $von + 1 ) ) / ( $bis - $von + 1 );
    }

    $auf = 0.0; $ab = 0.0; $ref = $glatt[0];
    for ( $i = 1; $i < $n; $i++ ) {
        $d = $glatt[ $i ] - $ref;
        if ( $d >= $schwelle )      { $auf += $d; $ref = $glatt[ $i ]; }
        elseif ( $d <= -$schwelle ) { $ab  += -$d; $ref = $glatt[ $i ]; }
    }
    return array(
        'auf' => (int) round( $auf ),
        'ab'  => (int) round( $ab ),
        'min' => (int) round( min( $glatt ) ),
        'max' => (int) round( max( $glatt ) ),
    );
}

/**
 * Streckenzug ausdünnen (Douglas-Peucker).
 * Eine aufgezeichnete Tour hat schnell 5.000 Punkte; für die Anzeige reichen
 * ein paar hundert — das spart Datenmenge und macht die Karte flüssig.
 * Die Original-GPX bleibt für den Download unangetastet.
 */
function tgs_geo_simplify( $pts, $epsilon = 0.00004 ) {
    $n = count( $pts );
    if ( $n < 3 ) return $pts;

    $dmax = 0.0; $index = 0;
    $a = $pts[0]; $b = $pts[ $n - 1 ];
    for ( $i = 1; $i < $n - 1; $i++ ) {
        $d = tgs_geo_perp_dist( $pts[ $i ], $a, $b );
        if ( $d > $dmax ) { $dmax = $d; $index = $i; }
    }

    if ( $dmax > $epsilon ) {
        $l = tgs_geo_simplify( array_slice( $pts, 0, $index + 1 ), $epsilon );
        $r = tgs_geo_simplify( array_slice( $pts, $index ), $epsilon );
        return array_merge( array_slice( $l, 0, count( $l ) - 1 ), $r );
    }
    return array( $a, $b );
}

/** Senkrechter Abstand eines Punktes zur Geraden a–b (in Grad, für Simplify). */
function tgs_geo_perp_dist( $p, $a, $b ) {
    $x = $p[1]; $y = $p[0];
    $x1 = $a[1]; $y1 = $a[0]; $x2 = $b[1]; $y2 = $b[0];
    $dx = $x2 - $x1; $dy = $y2 - $y1;
    if ( $dx == 0.0 && $dy == 0.0 ) return sqrt( pow( $x - $x1, 2 ) + pow( $y - $y1, 2 ) );
    $t = ( ( $x - $x1 ) * $dx + ( $y - $y1 ) * $dy ) / ( $dx * $dx + $dy * $dy );
    $t = max( 0, min( 1, $t ) );
    return sqrt( pow( $x - ( $x1 + $t * $dx ), 2 ) + pow( $y - ( $y1 + $t * $dy ), 2 ) );
}

/**
 * GPX komplett auswerten und als Meta ablegen.
 * Läuft beim Speichern der Tour, nicht beim Anzeigen.
 */
function tgs_tour_gpx_auswerten( $tour_id ) {
    $att_id = (int) get_post_meta( $tour_id, '_tgs_tour_gpx', true );
    if ( ! $att_id ) return false;
    $pfad = get_attached_file( $att_id );
    $pts  = tgs_gpx_punkte( $pfad );
    if ( count( $pts ) < 2 ) {
        update_post_meta( $tour_id, '_tgs_tour_fehler', 'GPX konnte nicht gelesen werden (keine Trackpunkte gefunden).' );
        return false;
    }
    delete_post_meta( $tour_id, '_tgs_tour_fehler' );

    // Optional Anfang/Ende kappen (Privatsphäre: Aufzeichnungen starten oft
    // an der Haustür des Aufzeichnenden).
    $trim = (int) get_post_meta( $tour_id, '_tgs_tour_trim', true );
    if ( $trim > 0 ) $pts = tgs_tour_trim( $pts, $trim );
    if ( count( $pts ) < 2 ) return false;

    // Distanz + Profil
    $dist = 0.0; $profil = array(); $eles = array();
    $prev = null;
    foreach ( $pts as $p ) {
        if ( $prev !== null ) $dist += tgs_geo_dist( $prev[0], $prev[1], $p[0], $p[1] );
        $prev = $p;
        $eles[] = $p[2];
        if ( $p[2] !== null ) $profil[] = array( round( $dist / 1000, 3 ), round( $p[2], 1 ) );
    }

    $hm = tgs_gpx_hoehenmeter( $eles );

    // Profil auf ~120 Stützstellen eindampfen — reicht für eine saubere Kurve.
    $profil = tgs_array_sample( $profil, 120 );

    // Streckenzug fürs Rendern
    $track = tgs_geo_simplify( $pts );
    if ( count( $track ) > 800 ) $track = tgs_array_sample( $track, 800 );
    $track = array_map( function ( $p ) {
        return array( round( $p[0], 5 ), round( $p[1], 5 ) );
    }, $track );

    $lats = array(); $lons = array();
    foreach ( $track as $p ) { $lats[] = $p[0]; $lons[] = $p[1]; }

    $start = $pts[0];
    $ende  = $pts[ count( $pts ) - 1 ];
    $rundkurs = tgs_geo_dist( $start[0], $start[1], $ende[0], $ende[1] ) < 250;

    update_post_meta( $tour_id, '_tgs_tour_km',     round( $dist / 1000, 1 ) );
    update_post_meta( $tour_id, '_tgs_tour_hm_auf', $hm['auf'] );
    update_post_meta( $tour_id, '_tgs_tour_hm_ab',  $hm['ab'] );
    update_post_meta( $tour_id, '_tgs_tour_ele_min', $hm['min'] );
    update_post_meta( $tour_id, '_tgs_tour_ele_max', $hm['max'] );
    update_post_meta( $tour_id, '_tgs_tour_rundkurs', $rundkurs ? '1' : '0' );
    update_post_meta( $tour_id, '_tgs_tour_punkte', count( $pts ) );
    update_post_meta( $tour_id, '_tgs_tour_track',  wp_json_encode( $track ) );
    update_post_meta( $tour_id, '_tgs_tour_profil', wp_json_encode( $profil ) );
    update_post_meta( $tour_id, '_tgs_tour_bounds', wp_json_encode( array(
        array( min( $lats ), min( $lons ) ), array( max( $lats ), max( $lons ) ),
    ) ) );
    update_post_meta( $tour_id, '_tgs_tour_start', wp_json_encode( array( round( $start[0], 5 ), round( $start[1], 5 ) ) ) );
    update_post_meta( $tour_id, '_tgs_tour_ende',  wp_json_encode( array( round( $ende[0], 5 ), round( $ende[1], 5 ) ) ) );
    return true;
}

/** Erste/letzte $meter einer Strecke abschneiden. */
function tgs_tour_trim( $pts, $meter ) {
    $n = count( $pts );
    // vorne
    $d = 0.0; $von = 0;
    for ( $i = 1; $i < $n; $i++ ) {
        $d += tgs_geo_dist( $pts[ $i - 1 ][0], $pts[ $i - 1 ][1], $pts[ $i ][0], $pts[ $i ][1] );
        if ( $d >= $meter ) { $von = $i; break; }
    }
    // hinten
    $d = 0.0; $bis = $n - 1;
    for ( $i = $n - 1; $i > 0; $i-- ) {
        $d += tgs_geo_dist( $pts[ $i - 1 ][0], $pts[ $i - 1 ][1], $pts[ $i ][0], $pts[ $i ][1] );
        if ( $d >= $meter ) { $bis = $i; break; }
    }
    if ( $bis <= $von ) return $pts;
    return array_slice( $pts, $von, $bis - $von + 1 );
}

/** Liste gleichmäßig auf max. $max Einträge reduzieren (erster/letzter bleiben). */
function tgs_array_sample( $arr, $max ) {
    $n = count( $arr );
    if ( $n <= $max || $max < 2 ) return $arr;
    $out = array();
    $step = ( $n - 1 ) / ( $max - 1 );
    for ( $i = 0; $i < $max; $i++ ) $out[] = $arr[ (int) round( $i * $step ) ];
    return $out;
}

/* =========================================================================
 * Anzeige-Helfer
 * ========================================================================= */

function tgs_tour_daten( $tour_id ) {
    $j = function ( $key, $default = array() ) use ( $tour_id ) {
        $v = get_post_meta( $tour_id, $key, true );
        if ( ! $v ) return $default;
        $d = json_decode( $v, true );
        return is_array( $d ) ? $d : $default;
    };
    return array(
        'km'      => (float) get_post_meta( $tour_id, '_tgs_tour_km', true ),
        'hm_auf'  => (int) get_post_meta( $tour_id, '_tgs_tour_hm_auf', true ),
        'hm_ab'   => (int) get_post_meta( $tour_id, '_tgs_tour_hm_ab', true ),
        'ele_min' => (int) get_post_meta( $tour_id, '_tgs_tour_ele_min', true ),
        'ele_max' => (int) get_post_meta( $tour_id, '_tgs_tour_ele_max', true ),
        'rund'    => get_post_meta( $tour_id, '_tgs_tour_rundkurs', true ) === '1',
        'track'   => $j( '_tgs_tour_track' ),
        'profil'  => $j( '_tgs_tour_profil' ),
        'bounds'  => $j( '_tgs_tour_bounds' ),
        'start'   => $j( '_tgs_tour_start' ),
        'ende'    => $j( '_tgs_tour_ende' ),
        'art'     => get_post_meta( $tour_id, '_tgs_tour_art', true ),
        'level'   => get_post_meta( $tour_id, '_tgs_tour_level', true ),
        'dauer'   => get_post_meta( $tour_id, '_tgs_tour_dauer', true ),
        'gpx'     => (int) get_post_meta( $tour_id, '_tgs_tour_gpx', true ),
        'komoot'  => get_post_meta( $tour_id, '_tgs_tour_komoot', true ),
        'start_id'=> (int) get_post_meta( $tour_id, '_tgs_tour_start_id', true ),
        'kurs_id' => (int) get_post_meta( $tour_id, '_tgs_tour_kurs_id', true ),
        'einkehr' => get_post_meta( $tour_id, '_tgs_tour_einkehr', true ),
    );
}

/** Hat die Tour auswertbare Geodaten? */
function tgs_tour_hat_track( $tour_id ) {
    $t = get_post_meta( $tour_id, '_tgs_tour_track', true );
    return ! empty( $t ) && $t !== '[]';
}

/**
 * Höhenprofil als SVG — selbst gerendert, kein Drittanbieter, skaliert
 * scharf und trägt eure Farben. Bei einer Taunus-Tour ist das Profil die
 * Information, die entscheidet, ob jemand mitfährt.
 */
function tgs_tour_profil_svg( $tour_id, $w = 640, $h = 140 ) {
    $d = tgs_tour_daten( $tour_id );
    $p = $d['profil'];
    if ( count( $p ) < 2 ) return '';

    $km_max = $p[ count( $p ) - 1 ][0];
    if ( $km_max <= 0 ) return '';
    $e_min = $d['ele_min']; $e_max = $d['ele_max'];
    if ( $e_max - $e_min < 20 ) { $e_max = $e_min + 20; } // flache Touren nicht überzeichnen

    $pad_l = 34; $pad_b = 18; $pad_t = 8; $pad_r = 6;
    $iw = $w - $pad_l - $pad_r; $ih = $h - $pad_t - $pad_b;

    $x = function ( $km ) use ( $pad_l, $iw, $km_max ) { return round( $pad_l + ( $km / $km_max ) * $iw, 1 ); };
    $y = function ( $e ) use ( $pad_t, $ih, $e_min, $e_max ) {
        return round( $pad_t + ( 1 - ( $e - $e_min ) / ( $e_max - $e_min ) ) * $ih, 1 );
    };

    $linie = ''; $flaeche = '';
    foreach ( $p as $i => $pt ) {
        $linie .= ( $i === 0 ? 'M' : 'L' ) . $x( $pt[0] ) . ' ' . $y( $pt[1] );
    }
    $flaeche = $linie . 'L' . $x( $km_max ) . ' ' . ( $pad_t + $ih ) . 'L' . $x( 0 ) . ' ' . ( $pad_t + $ih ) . 'Z';

    ob_start();
    ?>
    <svg class="tgs-tour-profil" viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" role="img"
         aria-label="Höhenprofil: <?php echo esc_attr( $d['hm_auf'] ); ?> Höhenmeter auf <?php echo esc_attr( number_format_i18n( $d['km'], 1 ) ); ?> Kilometern, <?php echo esc_attr( $e_min ); ?> bis <?php echo esc_attr( $d['ele_max'] ); ?> Meter.">
        <defs><linearGradient id="tgs-pg-<?php echo (int) $tour_id; ?>" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#3D5A40" stop-opacity=".38"/><stop offset="100%" stop-color="#3D5A40" stop-opacity=".04"/>
        </linearGradient></defs>
        <?php // Höhenlinien
        for ( $i = 0; $i <= 2; $i++ ) {
            $e  = $e_min + ( $e_max - $e_min ) * $i / 2;
            $yy = $y( $e );
            echo '<line class="tgs-pg-grid" x1="' . $pad_l . '" y1="' . $yy . '" x2="' . ( $w - $pad_r ) . '" y2="' . $yy . '"/>';
            echo '<text class="tgs-pg-lbl" x="' . ( $pad_l - 6 ) . '" y="' . ( $yy + 3.5 ) . '" text-anchor="end">' . (int) round( $e ) . '</text>';
        }
        ?>
        <path d="<?php echo esc_attr( $flaeche ); ?>" fill="url(#tgs-pg-<?php echo (int) $tour_id; ?>)"/>
        <path d="<?php echo esc_attr( $linie ); ?>" fill="none" stroke="#3D5A40" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>
        <text class="tgs-pg-lbl" x="<?php echo $pad_l; ?>" y="<?php echo $h - 5; ?>">0 km</text>
        <text class="tgs-pg-lbl" x="<?php echo $w - $pad_r; ?>" y="<?php echo $h - 5; ?>" text-anchor="end"><?php echo esc_html( number_format_i18n( $km_max, 1 ) ); ?> km</text>
    </svg>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

/**
 * Streckenverlauf als SVG — die Vorschau vor der Karte.
 * Ohne Grundkarte, dafür ohne jeden externen Request: die Form der Runde ist
 * erkennbar, die echte Karte kommt auf Klick.
 */
function tgs_tour_track_svg( $tour_id, $w = 640, $h = 360 ) {
    $d = tgs_tour_daten( $tour_id );
    $track = $d['track'];
    if ( count( $track ) < 2 ) return '';

    $lats = array(); $lons = array();
    foreach ( $track as $p ) { $lats[] = $p[0]; $lons[] = $p[1]; }
    $lat_min = min( $lats ); $lat_max = max( $lats );
    $lon_min = min( $lons ); $lon_max = max( $lons );

    // Längengrade werden Richtung Pol enger — sonst wird die Runde verzerrt.
    $k = cos( deg2rad( ( $lat_min + $lat_max ) / 2 ) );
    $bw = ( $lon_max - $lon_min ) * $k; $bh = $lat_max - $lat_min;
    if ( $bw <= 0 || $bh <= 0 ) return '';

    $pad = 18;
    $scale = min( ( $w - 2 * $pad ) / $bw, ( $h - 2 * $pad ) / $bh );
    $ox = ( $w - $bw * $scale ) / 2; $oy = ( $h - $bh * $scale ) / 2;

    $px = function ( $lon ) use ( $lon_min, $k, $scale, $ox ) { return round( $ox + ( $lon - $lon_min ) * $k * $scale, 1 ); };
    $py = function ( $lat ) use ( $lat_max, $scale, $oy ) { return round( $oy + ( $lat_max - $lat ) * $scale, 1 ); };

    $pfad = '';
    foreach ( $track as $i => $p ) $pfad .= ( $i === 0 ? 'M' : 'L' ) . $px( $p[1] ) . ' ' . $py( $p[0] );

    $s = $d['start'];
    ob_start();
    ?>
    <svg class="tgs-tour-shape" viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" aria-hidden="true">
        <path d="<?php echo esc_attr( $pfad ); ?>" fill="none" stroke="#3D5A40" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" opacity=".9"/>
        <?php if ( count( $s ) === 2 ) : ?>
        <circle cx="<?php echo $px( $s[1] ); ?>" cy="<?php echo $py( $s[0] ); ?>" r="5" fill="#c8873f" stroke="#fff" stroke-width="2"/>
        <?php endif; ?>
    </svg>
    <?php
    return tgs_strip_ws( ob_get_clean() );
}

/** Eckdaten-Zeile (km · hm · Dauer · Art). */
function tgs_tour_fakten_html( $tour_id ) {
    $d = tgs_tour_daten( $tour_id );
    $arten = tgs_tour_arten(); $level = tgs_tour_level();
    $f = array();
    if ( $d['km'] > 0 )     $f[] = array( 'Distanz', number_format_i18n( $d['km'], 1 ) . ' km' );
    if ( $d['hm_auf'] > 0 ) $f[] = array( 'Höhenmeter', '▲ ' . number_format_i18n( $d['hm_auf'] ) . ' m' );
    if ( $d['dauer'] )      $f[] = array( 'Dauer', $d['dauer'] );
    if ( isset( $arten[ $d['art'] ] ) ) $f[] = array( 'Art', $arten[ $d['art'] ] );
    if ( isset( $level[ $d['level'] ] ) ) $f[] = array( 'Level', $level[ $d['level'] ] );
    if ( empty( $f ) ) return '';

    $o = '<div class="tgs-tour-fakten">';
    foreach ( $f as $x ) {
        $o .= '<div class="tgs-tour-fakt"><span class="tgs-tour-fakt-l">' . esc_html( $x[0] ) . '</span>'
            . '<strong class="tgs-tour-fakt-v">' . esc_html( $x[1] ) . '</strong></div>';
    }
    return $o . '</div>';
}

/** Download-URL der Original-GPX. */
function tgs_tour_gpx_url( $tour_id ) {
    $id = (int) get_post_meta( $tour_id, '_tgs_tour_gpx', true );
    return $id ? wp_get_attachment_url( $id ) : '';
}
