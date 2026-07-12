<?php
/**
 * Kontaktformular
 *
 * [tgs_kontakt]  — Formular, das eine E-Mail an den Verein sendet.
 *
 * Empfänger standardmäßig die WordPress-Admin-E-Mail, überschreibbar per
 * Filter 'tgs_kontakt_empfaenger'.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Empfänger-Adresse für Kontaktanfragen. */
function tgs_kontakt_empfaenger() {
    return apply_filters( 'tgs_kontakt_empfaenger', get_option( 'admin_email' ) );
}

/** Verarbeitet die Formulardaten und verschickt die E-Mail. */
function tgs_process_kontakt() {
    // Honeypot: Bots füllen dieses versteckte Feld aus.
    if ( ! empty( $_POST['tgs_kf_website'] ) ) {
        return '<p class="tgs-anm-success">Vielen Dank!</p>';
    }

    $name      = sanitize_text_field( $_POST['tgs_kf_name'] ?? '' );
    $email     = sanitize_email( $_POST['tgs_kf_email'] ?? '' );
    $betreff   = sanitize_text_field( $_POST['tgs_kf_betreff'] ?? '' );
    $nachricht = sanitize_textarea_field( $_POST['tgs_kf_nachricht'] ?? '' );

    if ( empty( $name ) || empty( $email ) || ! is_email( $email ) || empty( $nachricht ) ) {
        return '<p class="tgs-anm-error">Bitte gib deinen Namen, eine gültige E-Mail-Adresse und deine Nachricht an.</p>';
    }
    if ( empty( $_POST['tgs_kf_dsgvo'] ) ) {
        return '<p class="tgs-anm-error">Bitte stimme der Datenschutzerklärung zu.</p>';
    }

    $to      = tgs_kontakt_empfaenger();
    $subject = 'Kontaktanfrage über die Website' . ( $betreff ? ': ' . $betreff : '' );
    $body    = "Neue Nachricht über das Kontaktformular:\n\n"
             . "Name: {$name}\n"
             . "E-Mail: {$email}\n"
             . ( $betreff ? "Betreff: {$betreff}\n" : '' )
             . "\nNachricht:\n{$nachricht}\n";
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    );

    $sent = wp_mail( $to, $subject, $body, $headers );

    if ( $sent ) {
        return '<p class="tgs-anm-success"><strong>Danke für deine Nachricht!</strong> Wir melden uns so bald wie möglich bei dir.</p>';
    }
    return '<p class="tgs-anm-error">Die Nachricht konnte gerade nicht gesendet werden. Bitte versuche es später noch einmal oder ruf uns an.</p>';
}

/**
 * [tgs_kontakt] — Kontaktformular
 */
function tgs_shortcode_kontakt() {
    $message = '';
    if ( isset( $_POST['tgs_kontakt_submit'] ) && isset( $_POST['tgs_kontakt_nonce'] )
         && wp_verify_nonce( $_POST['tgs_kontakt_nonce'], 'tgs_kontakt' ) ) {
        $message = tgs_process_kontakt();
    }

    ob_start();
    ?>
    <div class="tgs-kontakt-form">
        <?php if ( $message ) : ?><div class="tgs-kontakt-msg"><?php echo wp_kses_post( $message ); ?></div><?php endif; ?>
        <form method="post" action="#tgs-kontakt" id="tgs-kontakt">
            <?php wp_nonce_field( 'tgs_kontakt', 'tgs_kontakt_nonce' ); ?>
            <div class="tgs-kf-hp"><label>Bitte dieses Feld leer lassen <input type="text" name="tgs_kf_website" tabindex="-1" autocomplete="off"></label></div>
            <div class="tgs-kf-row">
                <div class="tgs-anm-field"><label for="tgs_kf_name">Name *</label><input type="text" id="tgs_kf_name" name="tgs_kf_name" required placeholder="Vor- und Nachname"></div>
                <div class="tgs-anm-field"><label for="tgs_kf_email">E-Mail *</label><input type="email" id="tgs_kf_email" name="tgs_kf_email" required placeholder="deine@email.de"></div>
            </div>
            <div class="tgs-anm-field"><label for="tgs_kf_betreff">Betreff</label><input type="text" id="tgs_kf_betreff" name="tgs_kf_betreff" placeholder="Worum geht es?"></div>
            <div class="tgs-anm-field"><label for="tgs_kf_nachricht">Nachricht *</label><textarea id="tgs_kf_nachricht" name="tgs_kf_nachricht" rows="6" required placeholder="Deine Nachricht an uns …"></textarea></div>
            <div class="tgs-anm-field"><label><input type="checkbox" name="tgs_kf_dsgvo" required> Ich stimme der <a href="/datenschutz" target="_blank" rel="noopener">Datenschutzerklärung</a> zu. *</label></div>
            <button type="submit" name="tgs_kontakt_submit" class="tgs-anm-submit">Nachricht senden</button>
        </form>
    </div>
    <?php
    // Whitespace zwischen Tags entfernen, damit wpautop das Formular nicht zerlegt.
    return preg_replace( '/>\s+</', '><', ob_get_clean() );
}
add_shortcode( 'tgs_kontakt', 'tgs_shortcode_kontakt' );
