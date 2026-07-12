<?php
/**
 * Speisekarte „Zu den Eichen – Da Luca" — als eigene, gesetzte Seite.
 *
 * [tgs_speisekarte]  — komplette Karte (Speisen + Getränke)
 *
 * Daten (Gerichte, Preise) zentral unten in tgs_speisekarte_daten() pflegbar.
 * Quelle: offizielle PDF-Karte (Stand 2025-01).
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Speisekarten-Daten. Jede Kategorie:
 *   'title'   => Überschrift
 *   'note'    => optionaler Untertext
 *   'headers' => optionale Preisspalten-Überschriften (z.B. ['Klein','Groß'])
 *   'items'   => [ nr, Name, Beschreibung, Preis1 (, Preis2) ]  — nr/desc/preis2 optional (leer möglich)
 *   'extra'   => optionale Zusatzliste [ Label, Preis ]
 *   'foot'    => optionaler Fußtext
 */
function tgs_speisekarte_daten() {
    return array(
        'speisen' => array(
            array(
                'title' => 'Antipasti & Vorspeisen',
                'items' => array(
                    array( '30', 'Carpaccio di Manzo', 'Marinierte Rinderfiletstreifen', '14,90' ),
                    array( '32', 'Bruschetta', 'Frisch geröstetes Weißbrot mit gehackten Tomaten in Olivenöl', '5,90' ),
                    array( '33', 'Insalata Caprese', 'Mozzarella, Tomaten, Basilikum', '9,90' ),
                ),
            ),
            array(
                'title' => 'Suppe',
                'items' => array(
                    array( '40', 'Crema di Pomodoro', 'Tomatencremesuppe', '6,90' ),
                ),
            ),
            array(
                'title'   => 'Insalata – Salate',
                'note'    => 'Wahlweise mit Haus- oder Joghurtdressing',
                'headers' => array( 'Klein', 'Groß' ),
                'items'   => array(
                    array( '50', 'Gemischter Italienischer Salat', 'Grüner Salat, Tomaten, Gurken, Ei, Hinterschinken, Zwiebeln, Oliven', '8,50', '10,50' ),
                    array( '51', 'Rucola Salat', 'Rucola, Parmesan, Tomaten', '9,50', '11,50' ),
                    array( '52', 'Bauernsalat', 'Grüner Salat, Gurken, Tomaten, Zwiebeln, Schafskäse, Oliven, Peperoni', '10,50', '12,50' ),
                    array( '53', 'Salat Enna', 'Grüner Salat, Artischocken, Gurken, Tomaten, Ei, Hinterschinken, Käse, Thunfisch, Paprika, Zwiebeln', '10,90', '13,90' ),
                    array( '54', 'Gemischter Salat', 'Grüner Salat, Gurken, Tomaten, Karotten, Zwiebeln', '7,50' ),
                ),
                'extra'   => array(
                    array( 'Dazu: Thunfisch', '2,00' ),
                    array( 'Dazu: Gegrillte Putenbruststreifen', '5,00' ),
                ),
            ),
            array(
                'title'   => 'Pizza',
                'note'    => 'Ø 30 cm',
                'items'   => array(
                    array( '1', 'Margherita', 'Tomaten, Käse', '7,90' ),
                    array( '2', 'Salami', 'Tomaten, Käse, Salami', '9,90' ),
                    array( '3', 'Funghi', 'Tomaten, Käse, Champignons', '9,90' ),
                    array( '4', 'Prosciutto', 'Tomaten, Käse, Hinterschinken', '9,90' ),
                    array( '5', 'Peperoniwurst', 'Tomaten, Käse, Peperoniwurst', '9,90' ),
                    array( '6', 'Quattro Stagioni', 'Tomaten, Käse, Hinterschinken, Artischocken, Paprika, Champignons', '11,90' ),
                    array( '7', 'Hawaii', 'Tomaten, Käse, Hinterschinken, Ananas', '10,90' ),
                    array( '8', 'Capricciosa', 'Tomaten, Käse, Hinterschinken, Artischocken, Oliven, Zwiebeln, milde Peperoni', '12,90' ),
                    array( '9', 'Portofino', 'Tomaten, Käse, Meeresfrüchte', '14,90' ),
                    array( '10', 'Tonno', 'Tomaten, Käse, Thunfisch, Zwiebel', '11,90' ),
                    array( '11', 'Rucola', 'Tomaten, Käse, Rucola, Parmesan', '12,90' ),
                    array( '12', 'Vegetaria', 'Tomaten, Käse, mediterranes Gemüse', '12,90' ),
                    array( '13', 'Parma', 'Tomaten, Mozzarella, Rucola, Parmesan, Parmaschinken', '14,90' ),
                    array( '14', 'Luca', 'Tomaten, Mozzarella, Peperoniwurst, Hinterschinken, Champignons, scharfe Peperoni, Oliven', '13,90' ),
                    array( '15', 'Vulcano', 'Tomaten, Käse, Peperoniwurst, scharfe Peperoni, Zwiebeln, Knoblauch', '12,90' ),
                    array( '16', 'Panna', 'Sahne, Hinterschinken', '9,90' ),
                    array( '18', 'Caprese', 'Tomaten, Käse, Mozzarella, Basilikum, frische Tomaten', '10,90' ),
                    array( '19', 'Pizzabrot', 'Tomaten, Knoblauch', '5,90' ),
                ),
                'foot'    => 'Wunschpizza: Zutaten je nach Auswahl 1,00 € / 1,50 € / 2,00 € (z. B. Champignons, Ananas, Ei; frische Tomaten, Salami, Sardellen; Mozzarella, Gorgonzola, Spinat, Parmaschinken, Thunfisch).',
            ),
            array(
                'title'   => 'Pasta – Nudelgerichte',
                'items'   => array(
                    array( '60', 'Pasta Bolognese', 'Mit hausgemachter Rinderhackfleischsauce', '10,90' ),
                    array( '61', 'Pasta Napoli', 'Hausgemachte Tomatensauce', '9,90' ),
                    array( '62', 'Pasta Carbonara', 'Sahne, Hinterschinken, Ei', '11,90' ),
                    array( '63', 'Pasta aglio e Olio', 'Olivenöl, Knoblauch, Peperoni', '10,50' ),
                    array( '64', 'Pasta di Mare', 'Mit Meeresfrüchten und Scampi', '18,50' ),
                    array( '65', 'Pasta Vegetaria', 'Hausgemachte Tomatensauce, frisches Gemüse', '12,90' ),
                    array( '66', 'Pasta Gorgonzola', 'Gorgonzola, Sahnesauce', '11,90' ),
                    array( '67', 'Pasta Broccoli', 'Broccoli, Hinterschinken, Rosésauce', '11,90' ),
                    array( '68', "Pasta all'Arrabbiata", 'Hausgemachte Tomatensauce, Zwiebeln, Peperoni pikant', '10,90' ),
                    array( '69', 'Pasta alla Sicilia', 'Hinterschinken, Erbsen, Pilze, Rosésauce', '12,90' ),
                    array( '70', 'Pasta al Salmone', 'Lachs, hausgemachte Tomatensahnesauce', '16,90' ),
                ),
                'foot'    => 'Bei jedem Pasta-Gericht wählbar: Tagliatelle, Spaghetti oder Rigatoni.',
            ),
            array(
                'title'   => 'Pasta Special',
                'items'   => array(
                    array( '71', 'Tortelloni Panna', 'Hinterschinken, Sahne', '11,90' ),
                    array( '72', 'Gnocchi Sorrentina', 'Hausgemachte Tomatensauce, Mozzarella, Basilikum', '11,90' ),
                    array( '73', 'Gnocchi Gorgonzola', 'Gorgonzola, Sahnesauce', '12,90' ),
                    array( '74', 'Lasagne', 'Hausgemachte Bolognese, mit Käse überbacken', '11,90' ),
                    array( '75', 'Pasta al Forno', 'Rigatoni, hausgemachte Tomatensahnesauce, Hinterschinken, mit Käse überbacken', '11,90' ),
                ),
            ),
            array(
                'title'   => 'Fisch',
                'items'   => array(
                    array( '110', 'Calamari fritti', 'In Öl gebackene Tintenfischringe mit großem Beilagensalat', '19,90' ),
                    array( '111', 'Scampi alla griglia', '5 Stück Riesengarnelen gegrillt mit großem Beilagensalat', '26,90' ),
                ),
            ),
            array(
                'title'   => 'Rumpsteak vom Grill',
                'items'   => array(
                    array( '100', 'Argentinisches Rumpsteak mit Kräuterbutter', 'Vom Angus-Rind (ca. 220 g)', '29,90' ),
                    array( '101', 'Argentinisches Rumpsteak mit Zwiebeln', 'Vom Angus-Rind (ca. 220 g)', '29,90' ),
                ),
            ),
            array(
                'title'   => 'Schnitzelgerichte',
                'note'    => 'Frisch zubereitet, dazu Pommes oder Bratkartoffeln (große Portion zusätzlich mit Beilagensalat)',
                'headers' => array( 'Klein', 'Groß' ),
                'items'   => array(
                    array( '90', 'Schnitzel Wiener Art', '', '10,90', '15,90' ),
                    array( '91', 'Jägerschnitzel', 'Paniert', '13,90', '18,90' ),
                    array( '92', 'Rahmschnitzel', 'Paniert', '13,90', '18,90' ),
                    array( '93', 'Zwiebelschnitzel', 'Paniert', '13,90', '18,90' ),
                    array( '94', 'Gorgonzolaschnitzel', 'Paniert', '13,90', '18,90' ),
                ),
                'extra'   => array(
                    array( 'Extra: Pommes frites', '4,50' ),
                    array( 'Extra: Bratkartoffeln', '4,50' ),
                    array( 'Extra: Mediterranes Gemüse', '5,00' ),
                ),
            ),
            array(
                'title'   => 'Für unsere kleinen Gäste',
                'items'   => array(
                    array( '125', 'Rigatoni Bolognese', 'Mit hausgemachter Fleischsauce', '8,50' ),
                    array( '126', 'Rigatoni Napoli', 'Mit hausgemachter Tomatensauce', '7,50' ),
                    array( '127', 'Pizza', 'Ø 24 cm – Belag nach Wahl', 'je Auswahl' ),
                    array( '128', 'Schnitzel Wiener Art', 'Mit Pommes', '10,90' ),
                ),
            ),
            array(
                'title'   => 'Dessert',
                'items'   => array(
                    array( '130', 'Tiramisú', 'Löffelbiskuits, Mascarpone-Creme, Kaffee', '8,00' ),
                    array( '131', 'Panna Cotta', '', '6,50' ),
                    array( '132', 'Tartufo Nero', 'Dunkle Schokolade-Mascarpone-Trüffel', '8,00' ),
                    array( '133', 'Tartufo Bianco', 'Weißer Zabaione-Trüffel', '8,00' ),
                ),
            ),
        ),
        'getraenke' => array(
            array(
                'title' => 'Heiße Getränke',
                'items' => array(
                    array( 'Espresso', '', '2,80' ),
                    array( 'Doppio Espresso', '', '4,50' ),
                    array( 'Kaffee Crema', '', '2,80' ),
                    array( 'Cappuccino', '', '3,50' ),
                    array( 'Latte Macchiato', '', '4,00' ),
                    array( 'Tee (versch. Sorten)', '', '2,50' ),
                ),
            ),
            array(
                'title' => 'Alkoholfreie Getränke',
                'items' => array(
                    array( 'Coca-Cola', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Coca-Cola light', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Fanta', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Spezi', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Zitronenlimo', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Orangensaft', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Apfelsaft', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Apfelsaftschorle', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Johannisbeersaft', '0,2 l / 0,4 l', '2,80 / 4,60' ),
                    array( 'Bad Camberger Taunusquelle', '0,25 l / 0,75 l', '2,80 / 6,90' ),
                    array( 'San Pellegrino', '0,75 l', '6,90' ),
                    array( 'Bitter Lemon', '0,2 l', '2,80' ),
                ),
            ),
            array(
                'title' => 'Bier',
                'items' => array(
                    array( 'Würzburger Hofbräu Pils', '0,3 l / 0,5 l', '3,30 / 5,00' ),
                    array( 'Krombacher Pils', '0,3 l / 0,5 l', '3,30 / 5,00' ),
                    array( 'Radler', '0,3 l / 0,5 l', '3,30 / 5,00' ),
                    array( 'Diesel', '0,3 l / 0,5 l', '3,30 / 5,00' ),
                    array( 'Mönchshof Kellerbier', '0,3 l / 0,5 l', '3,30 / 5,00' ),
                    array( 'Julius Echter Hefeweißbier Hell (vom Fass)', '0,5 l', '5,00' ),
                    array( 'Julius Echter Hefeweißbier Dunkel', '0,5 l', '4,50' ),
                    array( 'Julius Echter Hefeweißbier Alkoholfrei', '0,5 l', '4,50' ),
                    array( 'Würzburger Hofbräu Pils Alkoholfrei', '0,33 l', '4,00' ),
                ),
            ),
            array(
                'title' => 'Ebbelwoi',
                'items' => array(
                    array( 'Heil Apfelwein', '0,25 l / 0,5 l', '2,50 / 4,00' ),
                    array( 'Heil Apfelwein Sauer', '0,25 l / 0,5 l', '2,50 / 4,00' ),
                    array( 'Heil Apfelwein Süß', '0,25 l / 0,5 l', '2,50 / 4,00' ),
                ),
            ),
            array(
                'title' => 'Offene Weine',
                'items' => array(
                    array( 'Pinot Grigio', 'Weiß, trocken · 0,2 l', '6,50' ),
                    array( 'Soave', 'Weiß, trocken · 0,2 l', '6,00' ),
                    array( 'Weißweinschorle', '0,2 l', '5,50' ),
                    array( 'Rosé Belvedere', 'Halbtrocken · 0,2 l', '6,00' ),
                    array( 'Chianti', 'Rot, trocken · 0,2 l', '6,50' ),
                    array( 'Montepulciano', 'Rot, trocken · 0,2 l', '6,00' ),
                    array( 'Lambrusco', 'Rot, lieblich · 0,2 l', '5,50' ),
                ),
            ),
            array(
                'title' => 'Flaschenweine',
                'items' => array(
                    array( 'Tareni del Duca Inzolia', 'Weiß · 0,2 l / 0,75 l', '7,50 / 24,00' ),
                    array( 'Il Sacrato Sauvignon Blanc', 'Weiß · 0,2 l / 0,75 l', '7,50 / 24,00' ),
                    array( "Vezzani Nero d'Avola, Sicilia", 'Rot, trocken · 0,2 l / 0,75 l', '7,50 / 24,00' ),
                ),
            ),
            array(
                'title' => 'Aperitif',
                'items' => array(
                    array( 'Prosecco', '0,1 l / 0,75 l', '5,00 / 26,00' ),
                    array( 'Aperol Spritz', '0,2 l', '8,50' ),
                    array( 'Campari Soda', '0,2 l', '7,50' ),
                    array( 'Campari Orange', '0,2 l', '8,50' ),
                ),
            ),
            array(
                'title' => 'Digestif',
                'items' => array(
                    array( 'Grappa di Prosecco', '2 cl', '4,50' ),
                    array( 'Grappa 18 Lune', '2 cl', '5,50' ),
                    array( 'Averna', '2 cl', '4,00' ),
                    array( 'Ramazzotti', '2 cl', '4,00' ),
                    array( 'Fernet Branca', '2 cl', '4,00' ),
                    array( 'Sambuca', '2 cl', '4,00' ),
                    array( 'Vecchia Romagna', '2 cl', '4,50' ),
                    array( 'Amaretto', '2 cl', '3,50' ),
                    array( 'Williams', '2 cl', '3,50' ),
                    array( 'Obstler', '2 cl', '3,00' ),
                ),
            ),
        ),
    );
}

/** Rendert eine Speisen-Kategorie (Nummer, Name, Beschreibung, 1–2 Preise). */
function tgs_speise_kategorie_html( $cat ) {
    $has2 = ! empty( $cat['headers'] );
    ob_start();
    ?>
    <section class="tgs-sk-cat">
        <h2 class="tgs-sk-cat-title"><?php echo esc_html( $cat['title'] ); ?></h2>
        <?php if ( ! empty( $cat['note'] ) ) : ?><p class="tgs-sk-cat-note"><?php echo esc_html( $cat['note'] ); ?></p><?php endif; ?>
        <?php if ( $has2 ) : ?>
        <div class="tgs-sk-head"><span></span><span><?php echo esc_html( $cat['headers'][0] ); ?></span><span><?php echo esc_html( $cat['headers'][1] ); ?></span></div>
        <?php endif; ?>
        <ul class="tgs-sk-list<?php echo $has2 ? ' tgs-sk-list--2' : ''; ?>">
            <?php foreach ( $cat['items'] as $it ) : ?>
            <li class="tgs-sk-item">
                <span class="tgs-sk-nr"><?php echo esc_html( $it[0] ); ?></span>
                <span class="tgs-sk-main">
                    <span class="tgs-sk-name"><?php echo esc_html( $it[1] ); ?></span>
                    <?php if ( ! empty( $it[2] ) ) : ?><span class="tgs-sk-desc"><?php echo esc_html( $it[2] ); ?></span><?php endif; ?>
                </span>
                <?php if ( $has2 ) : ?>
                    <span class="tgs-sk-preis"><?php echo isset( $it[3] ) && $it[3] !== '' ? esc_html( $it[3] ) . ' €' : ''; ?></span>
                    <span class="tgs-sk-preis"><?php echo isset( $it[4] ) && $it[4] !== '' ? esc_html( $it[4] ) . ' €' : ''; ?></span>
                <?php else : ?>
                    <span class="tgs-sk-preis"><?php echo esc_html( $it[3] ); ?><?php echo is_numeric( str_replace( ',', '.', $it[3] ) ) ? ' €' : ''; ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ( ! empty( $cat['extra'] ) ) : ?>
        <ul class="tgs-sk-extra">
            <?php foreach ( $cat['extra'] as $ex ) : ?>
            <li><span><?php echo esc_html( $ex[0] ); ?></span><span><?php echo esc_html( $ex[1] ); ?> €</span></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if ( ! empty( $cat['foot'] ) ) : ?><p class="tgs-sk-cat-foot"><?php echo esc_html( $cat['foot'] ); ?></p><?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

/** Rendert eine Getränke-Kategorie (Name, Menge, Preis). */
function tgs_getraenk_kategorie_html( $cat ) {
    ob_start();
    ?>
    <section class="tgs-sk-cat tgs-sk-cat--drink">
        <h2 class="tgs-sk-cat-title"><?php echo esc_html( $cat['title'] ); ?></h2>
        <ul class="tgs-sk-drinks">
            <?php foreach ( $cat['items'] as $it ) : ?>
            <li>
                <span class="tgs-sk-dname"><?php echo esc_html( $it[0] ); ?><?php if ( ! empty( $it[1] ) ) : ?> <small><?php echo esc_html( $it[1] ); ?></small><?php endif; ?></span>
                <span class="tgs-sk-dprice"><?php echo esc_html( $it[2] ); ?> €</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * [tgs_speisekarte] — komplette Karte
 */
function tgs_shortcode_speisekarte() {
    $d = tgs_speisekarte_daten();
    ob_start();
    ?>
    <div class="tgs-sk">
        <a href="<?php echo esc_url( tgs_gaststaette_url() ); ?>" class="tgs-sk-back">← Zurück zur Gaststätte</a>
        <div class="tgs-sk-intro">
            <p class="tgs-sk-kicker">Zu den Eichen – Da Luca</p>
            <h1 class="tgs-sk-h1">Unsere Speisekarte</h1>
            <p class="tgs-sk-lead">Frische italienische &amp; deutsche Küche zu familienfreundlichen Preisen. Guten Appetit – Buon Appetito!</p>
        </div>

        <div class="tgs-sk-speisen">
            <?php foreach ( $d['speisen'] as $cat ) echo tgs_speise_kategorie_html( $cat ); ?>
        </div>

        <h2 class="tgs-sk-section">Getränke</h2>
        <div class="tgs-sk-getraenke">
            <?php foreach ( $d['getraenke'] as $cat ) echo tgs_getraenk_kategorie_html( $cat ); ?>
        </div>

        <p class="tgs-sk-legend">Preise in Euro, inkl. MwSt. Angaben ohne Gewähr – es gilt die Karte vor Ort. Kennzeichnung von Zusatzstoffen und Allergenen erhalten Sie gern in der Gaststätte.</p>

        <div class="tgs-sk-backbar">
            <a href="<?php echo esc_url( tgs_gaststaette_url() ); ?>" class="tgs-sk-back">← Zurück zur Gaststätte</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_speisekarte', 'tgs_shortcode_speisekarte' );
