<?php
/**
 * Rechtstexte: Impressum & Datenschutzerklärung
 *
 * [tgs_impressum]     — Impressum
 * [tgs_datenschutz]   — Datenschutzerklärung
 *
 * WICHTIG: Vorlage / Entwurf. Vor Veröffentlichung bitte prüfen (lassen).
 * Stammdaten unten in tgs_rechtstext_daten() zentral pflegbar.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Stammdaten für Impressum & Datenschutz. */
function tgs_rechtstext_daten() {
    return array(
        'verein'          => 'TGS 1886 Langenhain e.V.',
        'strasse'         => 'Sportplatzstraße 13',
        'plz_ort'         => '65719 Hofheim am Taunus',
        'email'           => 'verein@tgs-langenhain.de',
        'telefon'         => '', // optional, z.B. '06192 000000'
        'registergericht' => 'Amtsgericht Frankfurt am Main',
        'vr'              => 'VR 4433',
        'vorstand'        => array( 'Gerald Gräser', 'Thomas Müller', 'Andreas Trapper', 'Tanja Völker' ),
        'hoster'          => 'DomainFactory GmbH, Oskar-Messter-Straße 33, 85737 Ismaning',
        'aufsicht'        => 'Der Hessische Beauftragte für Datenschutz und Informationsfreiheit, Postfach 3163, 65021 Wiesbaden',
        'stand'           => 'Juli 2026',
    );
}

/** Anschrift-Block (wiederverwendet). */
function tgs_rechtstext_anschrift( $d ) {
    $tel = $d['telefon'] ? '<br>Telefon: ' . esc_html( $d['telefon'] ) : '';
    return '<address>' . esc_html( $d['verein'] ) . '<br>'
        . esc_html( $d['strasse'] ) . '<br>'
        . esc_html( $d['plz_ort'] ) . '<br>'
        . 'E-Mail: <a href="mailto:' . esc_attr( $d['email'] ) . '">' . esc_html( $d['email'] ) . '</a>'
        . $tel . '</address>';
}

/**
 * [tgs_impressum]
 */
function tgs_shortcode_impressum() {
    $d = tgs_rechtstext_daten();
    $vorstand = implode( ', ', array_map( 'esc_html', $d['vorstand'] ) );
    ob_start();
    ?>
    <div class="tgs-legal">
        <h2>Angaben gemäß § 5 DDG</h2>
        <?php echo tgs_rechtstext_anschrift( $d ); ?>

        <h2>Vertretungsberechtigter Vorstand</h2>
        <p>Der Verein wird durch den Vorstand gemäß § 26 BGB vertreten:<br><?php echo $vorstand; ?></p>

        <h2>Registereintrag</h2>
        <p>Eintragung im Vereinsregister.<br>
        Registergericht: <?php echo esc_html( $d['registergericht'] ); ?><br>
        Registernummer: <?php echo esc_html( $d['vr'] ); ?></p>

        <h2>Kontakt</h2>
        <p>E-Mail: <a href="mailto:<?php echo esc_attr( $d['email'] ); ?>"><?php echo esc_html( $d['email'] ); ?></a><?php if ( $d['telefon'] ) echo '<br>Telefon: ' . esc_html( $d['telefon'] ); ?><br>
        Über unser <a href="/kontakt">Kontaktformular</a> erreichst du uns ebenfalls.</p>

        <h2>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h2>
        <p>Der Vorstand der <?php echo esc_html( $d['verein'] ); ?>, Anschrift wie oben.</p>

        <h2>Haftung für Inhalte</h2>
        <p>Als Diensteanbieter sind wir für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Wir sind jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen. Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden entsprechender Rechtsverletzungen werden wir diese Inhalte umgehend entfernen.</p>

        <h2>Haftung für Links</h2>
        <p>Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich. Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Links umgehend entfernen.</p>

        <h2>Urheberrecht</h2>
        <p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Beiträge Dritter sind als solche gekennzeichnet. Downloads und Kopien dieser Seite sind nur für den privaten, nicht kommerziellen Gebrauch gestattet.</p>

        <h2>Streitschlichtung</h2>
        <p>Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: <a href="https://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener">https://ec.europa.eu/consumers/odr/</a>. Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>

        <p class="tgs-legal-stand">Stand: <?php echo esc_html( $d['stand'] ); ?></p>
    </div>
    <?php
    return preg_replace( '/>\s+</', '><', ob_get_clean() );
}
add_shortcode( 'tgs_impressum', 'tgs_shortcode_impressum' );

/**
 * [tgs_datenschutz]
 */
function tgs_shortcode_datenschutz() {
    $d = tgs_rechtstext_daten();
    ob_start();
    ?>
    <div class="tgs-legal">
        <h2>1. Verantwortlicher</h2>
        <p>Verantwortlich für die Datenverarbeitung auf dieser Website ist:</p>
        <?php echo tgs_rechtstext_anschrift( $d ); ?>

        <h2>2. Allgemeines</h2>
        <p>Wir nehmen den Schutz deiner persönlichen Daten ernst und behandeln sie vertraulich und entsprechend der gesetzlichen Datenschutzvorschriften (DSGVO, BDSG) sowie dieser Datenschutzerklärung. Personenbezogene Daten werden nur erhoben, wenn du sie uns – etwa über ein Formular – freiwillig mitteilst, oder wenn dies technisch für den Betrieb der Website erforderlich ist.</p>

        <h2>3. Hosting und Server-Logfiles</h2>
        <p>Diese Website wird bei einem externen Dienstleister gehostet (<?php echo esc_html( $d['hoster'] ); ?>). Beim Aufruf der Seiten werden automatisch Informationen in sogenannten Server-Logfiles gespeichert, die dein Browser übermittelt: Browsertyp und -version, verwendetes Betriebssystem, Referrer-URL, Hostname des zugreifenden Rechners, Uhrzeit der Serveranfrage und die IP-Adresse. Diese Daten dienen der technischen Bereitstellung, der Sicherheit und der Stabilität der Website und werden nicht mit anderen Datenquellen zusammengeführt. Rechtsgrundlage ist unser berechtigtes Interesse an einem sicheren und funktionierenden Angebot (Art. 6 Abs. 1 lit. f DSGVO).</p>

        <h2>4. Cookies</h2>
        <p>Unsere Website verwendet für Besucherinnen und Besucher keine Tracking- oder Marketing-Cookies. Technisch notwendige Cookies können durch das zugrunde liegende System (WordPress) gesetzt werden, insbesondere für angemeldete Redakteurinnen und Redakteure. Für die normale Nutzung der Seite ist keine Einwilligung in Cookies erforderlich.</p>

        <h2>5. Kontaktformular</h2>
        <p>Wenn du uns über das Kontaktformular schreibst, verarbeiten wir die von dir angegebenen Daten (Name, E-Mail-Adresse, ggf. Betreff und deine Nachricht), um deine Anfrage zu beantworten. Diese Daten werden per E-Mail an den Verein übermittelt. Rechtsgrundlage ist unser berechtigtes Interesse an der Beantwortung deiner Anfrage bzw. die Anbahnung/Durchführung eines Vertragsverhältnisses (Art. 6 Abs. 1 lit. f bzw. b DSGVO). Die Daten werden gelöscht, sobald die Anfrage erledigt ist und keine gesetzlichen Aufbewahrungspflichten entgegenstehen.</p>

        <h2>6. Kursanmeldung</h2>
        <p>Für die Anmeldung zu einem Kurs verarbeiten wir die im Formular angegebenen Daten: Name, E-Mail-Adresse und ggf. Telefonnummer. Bei Kursen mit Altersvorgabe zusätzlich das Geburtsdatum; bei Kinderkursen den Namen des Kindes sowie die Kontaktdaten der Ansprechpartner (Eltern). Zur Bestätigung nutzen wir ein Double-Opt-In-Verfahren (Bestätigung per E-Mail-Link).</p>
        <p>Diese Daten dienen ausschließlich der Verwaltung der Kursteilnahme – etwa der Platzvergabe, der Warteliste sowie Benachrichtigungen zum Kurs (z. B. Bestätigung, Nachrücken, Ausfälle oder Mitteilungen der Kursleitung). Rechtsgrundlage ist die Durchführung der Teilnahme (Art. 6 Abs. 1 lit. b DSGVO) bzw. deine Einwilligung (Art. 6 Abs. 1 lit. a DSGVO), die du jederzeit über den persönlichen Link in unseren E-Mails widerrufen kannst. Die Daten werden gespeichert, solange die Anmeldung besteht, und anschließend gelöscht, soweit keine gesetzlichen Pflichten entgegenstehen.</p>

        <h2>7. Kursbewertungen</h2>
        <p>Sofern für einen Kurs aktiviert, können angemeldete Teilnehmer freiwillig eine Bewertung abgeben. Die Angabe eines Namens ist optional (eine anonyme Bewertung ist möglich). Bewertungen werden erst nach Prüfung durch die Kursleitung veröffentlicht. Rechtsgrundlage ist deine Einwilligung (Art. 6 Abs. 1 lit. a DSGVO).</p>

        <h2>8. E-Mail-Versand</h2>
        <p>Im Rahmen der Kursverwaltung versenden wir Service-E-Mails (z. B. Anmeldebestätigung, Wartelisten- und Ausfallbenachrichtigungen) an die von dir angegebene Adresse. Diese sind zur Durchführung der Kursteilnahme erforderlich.</p>

        <h2>9. Eingebundene Videos (YouTube)</h2>
        <p>Auf einzelnen Seiten binden wir Videos von YouTube ein (Anbieter: Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland). Die Einbindung erfolgt im erweiterten Datenschutzmodus (youtube-nocookie) und erst nach aktivem Klick: Solange du das Vorschaubild nicht anklickst, werden keine Daten an YouTube übertragen. Erst mit dem Klick lädst du das Video von YouTube; dabei können Daten (u. a. deine IP-Adresse) an Google übermittelt werden. Rechtsgrundlage ist dann deine Einwilligung durch den Klick (Art. 6 Abs. 1 lit. a DSGVO). Näheres in der <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Datenschutzerklärung von Google</a>.</p>

        <h2>10. Schriftarten (Google Fonts)</h2>
        <p>Zur einheitlichen Darstellung von Schriften nutzt diese Website Schriftarten. Werden diese von Google geladen, kann dabei deine IP-Adresse an Google übertragen werden. Anbieter ist Google Ireland Limited. Weitere Informationen findest du in der <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Datenschutzerklärung von Google</a>. <em>(Hinweis für den Verein: Werden die Schriften lokal auf dem eigenen Server ausgeliefert, entfällt diese Übermittlung – dieser Abschnitt kann dann entfernt werden.)</em></p>

        <h2>11. SSL-/TLS-Verschlüsselung</h2>
        <p>Diese Seite nutzt aus Sicherheitsgründen und zum Schutz der Übertragung vertraulicher Inhalte eine SSL-/TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennst du am „https://" in der Adresszeile deines Browsers.</p>

        <h2>12. Deine Rechte</h2>
        <p>Du hast im Rahmen der gesetzlichen Vorgaben jederzeit das Recht auf Auskunft über deine gespeicherten personenbezogenen Daten, deren Herkunft und Empfänger sowie den Zweck der Verarbeitung, auf Berichtigung, Löschung, Einschränkung der Verarbeitung, Widerspruch gegen die Verarbeitung und auf Datenübertragbarkeit. Eine erteilte Einwilligung kannst du jederzeit mit Wirkung für die Zukunft widerrufen. Wende dich hierzu an die oben genannte Adresse.</p>
        <p>Zudem steht dir ein Beschwerderecht bei einer Datenschutz-Aufsichtsbehörde zu, für uns: <?php echo esc_html( $d['aufsicht'] ); ?>.</p>

        <h2>13. Aktualität und Änderungen</h2>
        <p>Wir passen diese Datenschutzerklärung an, sobald Änderungen der Website oder der rechtlichen Rahmenbedingungen dies erfordern.</p>

        <p class="tgs-legal-stand">Stand: <?php echo esc_html( $d['stand'] ); ?></p>
    </div>
    <?php
    return preg_replace( '/>\s+</', '><', ob_get_clean() );
}
add_shortcode( 'tgs_datenschutz', 'tgs_shortcode_datenschutz' );
