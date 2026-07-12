<?php
/**
 * Mitglied werden — Kostenübersicht + Beitrittsprozess
 *
 * [tgs_mitglied_werden]  — komplette Seite (Intro, Kosten, Schritte, CTA)
 * [tgs_beitraege]        — nur die Beitragstabelle
 *
 * Beiträge gültig ab 2026 (Hauptversammlung 14.03.2025).
 * Zum Aktualisieren einfach die Arrays unten anpassen.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * URL des offiziellen Online-Mitgliedsantrags (SPG-Vereinsportal).
 * Über Filter 'tgs_mitglied_portal_url' überschreibbar.
 */
function tgs_mitglied_portal_url() {
    return apply_filters( 'tgs_mitglied_portal_url', 'https://online.spg-direkt.de/start/B1B13D3C-767D-42CC-A108-8E2D20FCB610' );
}

/**
 * Grundbeiträge (Jahresbeitrag). 'monat' = grober Monatswert nur zur Orientierung.
 */
function tgs_beitraege_daten() {
    return array(
        'grund' => array(
            array( 'name' => 'Erwachsene (aktiv)',          'jahr' => '90',  'monat' => '7,50' ),
            array( 'name' => 'Jugendliche bis 18 Jahre',    'jahr' => '57',  'monat' => '4,75' ),
            array( 'name' => 'Familienbeitrag',             'jahr' => '196', 'monat' => '16,33', 'hint' => 'für die ganze Familie' ),
            array( 'name' => 'Radsport',                    'jahr' => '45',  'monat' => '3,75' ),
            array( 'name' => 'Erwachsene (passiv/fördernd)','jahr' => '36',  'monat' => '3,00' ),
        ),
        'handball' => array(
            array( 'name' => 'Erwachsene (aktiv)',        'jahr' => '18' ),
            array( 'name' => 'Jugendliche bis 18 (aktiv)','jahr' => '9' ),
        ),
        'beispiele' => array(
            'Familie mit 2 Kindern, Vater + ein Kind im Handball' => '196 + 18 + 9 = <strong>223 €</strong>',
            'Familie, Vater im Handball'                          => '196 + 18 = <strong>214 €</strong>',
            'Erwachsener im Handball'                             => '90 + 18 = <strong>108 €</strong>',
            'Jugendlicher im Handball'                            => '57 + 9 = <strong>66 €</strong>',
        ),
    );
}

/**
 * [tgs_beitraege] — Beitragstabelle als Karten
 */
function tgs_shortcode_beitraege() {
    $d = tgs_beitraege_daten();
    ob_start();
    ?>
    <div class="tgs-beitrag">
        <div class="tgs-beitrag-grid">
            <?php foreach ( $d['grund'] as $b ) : ?>
            <div class="tgs-beitrag-card">
                <div class="tgs-beitrag-name"><?php echo esc_html( $b['name'] ); ?></div>
                <div class="tgs-beitrag-jahr"><?php echo esc_html( $b['jahr'] ); ?> €<span>/Jahr</span></div>
                <div class="tgs-beitrag-monat">≈ <?php echo esc_html( $b['monat'] ); ?> € pro Monat<?php if ( ! empty( $b['hint'] ) ) echo ' · ' . esc_html( $b['hint'] ); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="tgs-beitrag-addon">
            <div class="tgs-beitrag-addon-hd">Zusatzbeitrag Handball <span>zusätzlich zum Grundbeitrag</span></div>
            <div class="tgs-beitrag-addon-row">
                <?php foreach ( $d['handball'] as $h ) : ?>
                <span class="tgs-beitrag-pill"><?php echo esc_html( $h['name'] ); ?>: <strong>+<?php echo esc_html( $h['jahr'] ); ?> €/Jahr</strong></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tgs-beitrag-note">
            <p><strong>Familienbeitrag:</strong> kann beantragt werden, wenn die Einzelbeiträge einer Familie zusammen mehr als 190 € ergeben. Kinder in Ausbildung werden nur mit Nachweis angerechnet.</p>
            <p><strong>Rechenbeispiele:</strong></p>
            <ul>
                <?php foreach ( $d['beispiele'] as $fall => $rechnung ) : ?>
                <li><?php echo esc_html( $fall ); ?>: <?php echo wp_kses_post( $rechnung ); ?> pro Jahr</li>
                <?php endforeach; ?>
            </ul>
            <p class="tgs-beitrag-stand">Beiträge gültig ab 2026 · beschlossen auf der Hauptversammlung am 14. März 2025.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_beitraege', 'tgs_shortcode_beitraege' );

/**
 * [tgs_mitglied_werden] — komplette Seite
 */
function tgs_shortcode_mitglied_werden() {
    $portal = tgs_mitglied_portal_url();
    ob_start();
    ?>
    <div class="tgs-mw">
        <div class="tgs-mw-intro">
            <p class="tgs-mw-kicker">Mitglied werden</p>
            <h1 class="tgs-mw-h1">Werde Teil der TGS 1886 Langenhain</h1>
            <p class="tgs-mw-lead">Mit deiner Mitgliedschaft trainierst du in allen Kursen und Abteilungen mit – vom Turnen über Fitness bis Handball und Radsport. Ein Beitrag, das ganze Angebot. Hier findest du die aktuellen Kosten und wie du in wenigen Minuten dabei bist.</p>
        </div>

        <div class="tgs-section">
            <div class="tgs-section-hd"><p class="tgs-section-title"><strong>Was kostet die Mitgliedschaft?</strong></p></div>
            <?php echo do_shortcode( '[tgs_beitraege]' ); ?>
        </div>

        <div class="tgs-section tgs-mw-steps-wrap">
            <div class="tgs-section-hd"><p class="tgs-section-title"><strong>So wirst du Mitglied</strong></p></div>
            <div class="tgs-mw-steps">
                <div class="tgs-mw-step">
                    <div class="tgs-mw-step-no">1</div>
                    <div class="tgs-mw-step-tx"><strong>Beitrag prüfen</strong><span>Such dir oben den passenden Beitrag heraus – bei Fragen helfen wir gern weiter.</span></div>
                </div>
                <div class="tgs-mw-step">
                    <div class="tgs-mw-step-no">2</div>
                    <div class="tgs-mw-step-tx"><strong>Online-Antrag ausfüllen</strong><span>Deine Daten und das SEPA-Lastschriftmandat trägst du im offiziellen Mitgliedsformular ein – dauert nur ein paar Minuten.</span></div>
                </div>
                <div class="tgs-mw-step">
                    <div class="tgs-mw-step-no">3</div>
                    <div class="tgs-mw-step-tx"><strong>Willkommen im Verein</strong><span>Wir bestätigen deine Aufnahme – und du kannst direkt mit dem Training loslegen.</span></div>
                </div>
            </div>
        </div>

        <div class="tgs-section">
            <div class="tgs-mw-cta">
                <div class="tgs-mw-cta-tx">
                    <div class="tgs-mw-cta-h">Bereit? Hier geht's zum Mitgliedsantrag</div>
                    <div class="tgs-mw-cta-sub">Der Antrag läuft über unser offizielles Vereinsportal (inkl. SEPA-Mandat) und öffnet sich in einem neuen Tab – diese Seite bleibt für dich offen.</div>
                </div>
                <a href="<?php echo esc_url( $portal ); ?>" class="tgs-mw-cta-btn" target="_blank" rel="noopener">Mitgliedsantrag ausfüllen →</a>
            </div>
        </div>

        <div class="tgs-section tgs-mw-faq">
            <div class="tgs-section-hd"><p class="tgs-section-title"><strong>Gut zu wissen</strong></p></div>
            <div class="tgs-mw-faq-grid">
                <div class="tgs-mw-faq-item"><strong>Alle Kurse inklusive</strong><span>Kursangebote der TGS sind über die Mitgliedschaft abgedeckt – es fällt keine gesonderte Kursgebühr an.</span></div>
                <div class="tgs-mw-faq-item"><strong>Handball extra</strong><span>Für die aktive Handball-Teilnahme kommt ein kleiner Zusatzbeitrag zum Grundbeitrag hinzu.</span></div>
                <div class="tgs-mw-faq-item"><strong>Kinder & Familie</strong><span>Für Kinder gibt es einen ermäßigten Beitrag; ab einer gewissen Höhe lohnt sich der Familienbeitrag.</span></div>
                <div class="tgs-mw-faq-item"><strong>Fragen?</strong><span>Melde dich einfach über unsere <a href="/kontakt">Kontaktseite</a> – wir beraten dich gern vor dem Beitritt.</span></div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_mitglied_werden', 'tgs_shortcode_mitglied_werden' );
