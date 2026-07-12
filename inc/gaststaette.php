<?php
/**
 * Vereinsgaststätte „Zu den Eichen – Da Luca"
 *
 * [tgs_gaststaette]  — komplette Seite (Hero, Öffnungszeiten, Speisen, Bewertungen, Kontakt)
 *
 * Alle Angaben (Öffnungszeiten, Kontakt, Links, Bewertung) zentral unten in
 * tgs_gaststaette_daten() pflegbar.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Stammdaten der Gaststätte. Zum Aktualisieren einfach hier anpassen.
 * Öffnungszeiten: je Wochentag (1=Mo … 7=So) => [ Label, Anzeige-Text, Zeitfenster in Minuten ].
 */
function tgs_gaststaette_daten() {
    return array(
        'name'        => 'Zu den Eichen – Da Luca',
        'kueche'      => 'Italienische & deutsche Küche',
        'tagline'     => 'Gutbürgerlich, italienisch, herzlich – unsere Vereinsgaststätte am Waldrand, direkt am Panoramaweg Langenhain. Luca Carcione und sein Team freuen sich auf euch.',
        'wirt'        => 'Luca Carcione & Team',
        'tel'         => '06192 203555',
        'tel_link'    => '+496192203555',
        'adresse'     => 'Sportplatzstraße 13',
        'plz_ort'     => '65719 Hofheim-Langenhain',
        'maps'        => 'https://www.google.com/maps/search/?api=1&query=Zu%20den%20Eichen%20Da%20Luca%20Sportplatzstra%C3%9Fe%2013%20Hofheim',
        // Speisekarte als eigenes PDF: in die Mediathek hochladen und die interne
        // URL hier eintragen (z.B. .../wp-content/uploads/…/speisekarte.pdf).
        // Leer lassen = kein Speisekarten-Button (kein externer Link).
        'speisekarte' => '',
        'rating'      => '4,6',
        'rating_link' => 'https://www.google.com/maps/search/?api=1&query=Zu%20den%20Eichen%20Da%20Luca%20Sportplatzstra%C3%9Fe%2013%20Hofheim',
        'zeiten'      => array(
            1 => array( 'Montag',     '17:00–24:00 Uhr',              array( array( 1020, 1440 ) ) ),
            2 => array( 'Dienstag',   'Ruhetag',                      array() ),
            3 => array( 'Mittwoch',   '17:00–24:00 Uhr',              array( array( 1020, 1440 ) ) ),
            4 => array( 'Donnerstag', '17:00–24:00 Uhr',              array( array( 1020, 1440 ) ) ),
            5 => array( 'Freitag',    '17:00–24:00 Uhr',              array( array( 1020, 1440 ) ) ),
            6 => array( 'Samstag',    '17:00–24:00 Uhr',              array( array( 1020, 1440 ) ) ),
            7 => array( 'Sonntag',    '11:30–15:00 & 17:00–23:00 Uhr', array( array( 690, 900 ), array( 1020, 1380 ) ) ),
        ),
        'speisen' => array(
            array( '🍕', 'Pizza',                   'Klassiker und Spezialitäten' ),
            array( '🍝', 'Pasta',                   'Italienisch, hausgemacht' ),
            array( '🥩', 'Deutsche Klassiker',      'Schnitzel & herzhafte Gerichte' ),
            array( '🥗', 'Frische Salate',          'Leicht & knackig' ),
            array( '🇮🇹', 'Italienische Spezialitäten','Der Saison entsprechend' ),
            array( '🍰', 'Dolci & Desserts',        'Süßer Abschluss' ),
        ),
        'features' => array(
            array( '🌳', 'Große Terrasse',           'Draußen sitzen am Waldrand, direkt am Panoramaweg.' ),
            array( '🥾', 'Für Aktive',               'Willkommen für Wanderer, Mountainbiker, Motorradfahrer & Tagesgäste.' ),
            array( '🎉', 'Feiern & Events',          'Familienfeiern sowie Vereins- und Firmenveranstaltungen richten wir gern aus.' ),
            array( '👪', 'Familienfreundlich',       'Gemütliche Atmosphäre zu familienfreundlichen Preisen.' ),
        ),
    );
}

/** Ist die Gaststätte laut Zeitplan gerade geöffnet? */
function tgs_gaststaette_status( $zeiten ) {
    $today = (int) current_time( 'N' );          // 1=Mo … 7=So
    $now   = (int) current_time( 'G' ) * 60 + (int) current_time( 'i' );
    $open  = false;
    if ( isset( $zeiten[ $today ] ) ) {
        foreach ( $zeiten[ $today ][2] as $r ) {
            if ( $now >= $r[0] && $now < $r[1] ) { $open = true; break; }
        }
    }
    return array( 'today' => $today, 'open' => $open, 'heute_text' => $zeiten[ $today ][1] ?? '' );
}

/**
 * [tgs_gaststaette] — komplette Seite
 */
function tgs_shortcode_gaststaette() {
    $d      = tgs_gaststaette_daten();
    $status = tgs_gaststaette_status( $d['zeiten'] );
    ob_start();
    ?>
    <div class="tgs-gs">
        <!-- HERO -->
        <div class="tgs-gs-hero">
            <div class="tgs-gs-hero-inner">
                <span class="tgs-gs-kicker">Unsere Vereinsgaststätte</span>
                <h1 class="tgs-gs-h1"><?php echo esc_html( $d['name'] ); ?></h1>
                <p class="tgs-gs-sub"><?php echo esc_html( $d['kueche'] ); ?></p>
                <div class="tgs-gs-hero-badges">
                    <a href="<?php echo esc_url( $d['rating_link'] ); ?>" class="tgs-gs-rating" target="_blank" rel="noopener">★ <?php echo esc_html( $d['rating'] ); ?> <span>auf Google</span></a>
                    <span class="tgs-gs-status tgs-gs-status--<?php echo $status['open'] ? 'open' : 'closed'; ?>">
                        <?php echo $status['open'] ? 'Jetzt geöffnet' : 'Jetzt geschlossen'; ?>
                    </span>
                </div>
                <p class="tgs-gs-tagline"><?php echo esc_html( $d['tagline'] ); ?></p>
                <div class="tgs-gs-hero-cta">
                    <a href="tel:<?php echo esc_attr( $d['tel_link'] ); ?>" class="tgs-gs-btn tgs-gs-btn--primary">📞 Tisch reservieren</a>
                    <?php if ( $d['speisekarte'] ) : ?>
                    <a href="<?php echo esc_url( $d['speisekarte'] ); ?>" class="tgs-gs-btn tgs-gs-btn--ghost" target="_blank" rel="noopener">Zur Speisekarte →</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SCHNELL-FAKTEN -->
        <div class="tgs-gs-facts">
            <div class="tgs-gs-fact">
                <span class="tgs-gs-fact-k">Heute</span>
                <span class="tgs-gs-fact-v"><?php echo esc_html( $status['heute_text'] ); ?></span>
            </div>
            <a class="tgs-gs-fact tgs-gs-fact--link" href="tel:<?php echo esc_attr( $d['tel_link'] ); ?>">
                <span class="tgs-gs-fact-k">Telefon</span>
                <span class="tgs-gs-fact-v"><?php echo esc_html( $d['tel'] ); ?></span>
            </a>
            <a class="tgs-gs-fact tgs-gs-fact--link" href="<?php echo esc_url( $d['maps'] ); ?>" target="_blank" rel="noopener">
                <span class="tgs-gs-fact-k">Adresse</span>
                <span class="tgs-gs-fact-v"><?php echo esc_html( $d['adresse'] . ', ' . $d['plz_ort'] ); ?></span>
            </a>
            <div class="tgs-gs-fact">
                <span class="tgs-gs-fact-k">Küche</span>
                <span class="tgs-gs-fact-v"><?php echo esc_html( $d['kueche'] ); ?></span>
            </div>
        </div>

        <!-- ÖFFNUNGSZEITEN + SPEISEN -->
        <div class="tgs-gs-grid">
            <div class="tgs-gs-block">
                <h2 class="tgs-gs-h2">Öffnungszeiten</h2>
                <table class="tgs-gs-hours">
                    <tbody>
                        <?php foreach ( $d['zeiten'] as $n => $z ) :
                            $is_today = ( $n === $status['today'] );
                            $ruhetag  = empty( $z[2] );
                        ?>
                        <tr class="<?php echo $is_today ? 'is-today' : ''; ?><?php echo $ruhetag ? ' is-closed' : ''; ?>">
                            <th><?php echo esc_html( $z[0] ); ?><?php if ( $is_today ) echo ' <span class="tgs-gs-today-tag">heute</span>'; ?></th>
                            <td><?php echo esc_html( $z[1] ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="tgs-gs-note">Für größere Gruppen und an Wochenenden empfehlen wir eine kurze Reservierung unter <a href="tel:<?php echo esc_attr( $d['tel_link'] ); ?>"><?php echo esc_html( $d['tel'] ); ?></a>.</p>
            </div>

            <div class="tgs-gs-block">
                <h2 class="tgs-gs-h2">Was auf den Tisch kommt</h2>
                <p class="tgs-gs-lead">Von <strong>Pizza und Pasta</strong> bis zu <strong>deutschen Klassikern</strong> – frisch zubereitet zu familienfreundlichen Preisen.</p>
                <div class="tgs-gs-speisen">
                    <?php foreach ( $d['speisen'] as $s ) : ?>
                    <div class="tgs-gs-speise">
                        <span class="tgs-gs-speise-ic"><?php echo esc_html( $s[0] ); ?></span>
                        <span class="tgs-gs-speise-tx"><strong><?php echo esc_html( $s[1] ); ?></strong><span><?php echo esc_html( $s[2] ); ?></span></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ( $d['speisekarte'] ) : ?>
                <a href="<?php echo esc_url( $d['speisekarte'] ); ?>" class="tgs-gs-menu-btn" target="_blank" rel="noopener">Komplette Speisekarte ansehen →</a>
                <?php else : ?>
                <p class="tgs-gs-note">Die vollständige Speisekarte gibt's direkt vor Ort – oder ruf uns kurz an unter <a href="tel:<?php echo esc_attr( $d['tel_link'] ); ?>"><?php echo esc_html( $d['tel'] ); ?></a>.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- BEWERTUNGEN -->
        <div class="tgs-gs-reviews">
            <div class="tgs-gs-reviews-l">
                <div class="tgs-gs-reviews-score"><?php echo esc_html( $d['rating'] ); ?></div>
                <div class="tgs-gs-reviews-stars">★★★★★</div>
                <div class="tgs-gs-reviews-sub">Gäste-Bewertung auf Google</div>
            </div>
            <div class="tgs-gs-reviews-r">
                <p>Unsere Gäste schätzen die herzliche Atmosphäre, die frische Küche und den Platz auf der Terrasse. Überzeug dich selbst – lies die öffentlichen Bewertungen:</p>
                <a href="<?php echo esc_url( $d['rating_link'] ); ?>" class="tgs-gs-btn tgs-gs-btn--primary" target="_blank" rel="noopener">Bewertungen auf Google ansehen →</a>
            </div>
        </div>

        <!-- BESONDERHEITEN -->
        <div class="tgs-gs-block">
            <h2 class="tgs-gs-h2">Gut zu wissen</h2>
            <div class="tgs-gs-features">
                <?php foreach ( $d['features'] as $f ) : ?>
                <div class="tgs-gs-feature">
                    <span class="tgs-gs-feature-ic"><?php echo esc_html( $f[0] ); ?></span>
                    <div class="tgs-gs-feature-tx"><strong><?php echo esc_html( $f[1] ); ?></strong><span><?php echo esc_html( $f[2] ); ?></span></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- KONTAKT / CTA -->
        <div class="tgs-gs-contact">
            <div class="tgs-gs-contact-tx">
                <div class="tgs-gs-contact-h">Reservieren & vorbeikommen</div>
                <div class="tgs-gs-contact-sub"><?php echo esc_html( $d['adresse'] . ', ' . $d['plz_ort'] ); ?> · <?php echo esc_html( $d['wirt'] ); ?></div>
            </div>
            <div class="tgs-gs-contact-btns">
                <a href="tel:<?php echo esc_attr( $d['tel_link'] ); ?>" class="tgs-gs-btn tgs-gs-btn--primary">📞 <?php echo esc_html( $d['tel'] ); ?></a>
                <a href="<?php echo esc_url( $d['maps'] ); ?>" class="tgs-gs-btn tgs-gs-btn--ghost" target="_blank" rel="noopener">📍 Anfahrt</a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_gaststaette', 'tgs_shortcode_gaststaette' );
