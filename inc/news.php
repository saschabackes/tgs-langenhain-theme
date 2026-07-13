<?php
/**
 * News / Aktuelles — Artikel-Detail, Übersicht und Startseiten-Teaser.
 *
 * News sind normale WordPress-Beiträge (Gutenberg-Editor für den Inhalt).
 * Diese Shortcodes liefern den einheitlichen Rahmen:
 *   [tgs_news_detail]   — Artikelseite (Titelbild-Hero, Kategorie, Datum, Inhalt, verwandte Beiträge)
 *   [tgs_news_liste]    — Übersicht als Karten  (Attribut: anzahl)
 *   [tgs_news_teaser]   — Startseiten-Teaser    (Attribut: anzahl, default 3)
 *
 * Empfehlung je Beitrag: Beitragsbild setzen, Kategorie wählen, Auszug pflegen.
 *
 * @package TGS_Langenhain
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Eine News-Karte (für Übersicht + Teaser). */
function tgs_news_card_html( $post_id ) {
    $img   = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'medium_large' ) : '';
    $cats  = get_the_category( $post_id );
    $cat   = $cats ? $cats[0]->name : '';
    $url   = get_permalink( $post_id );
    $date  = get_the_date( 'j. F Y', $post_id );
    $title = get_the_title( $post_id );
    $exc   = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 22, '…' );

    ob_start();
    ?>
    <a href="<?php echo esc_url( $url ); ?>" class="tgs-news-card">
        <?php if ( $img ) : ?><span class="tgs-news-card-img" style="background-image:url(<?php echo esc_url( $img ); ?>);"></span><?php endif; ?>
        <span class="tgs-news-card-body">
            <span class="tgs-news-card-meta">
                <?php if ( $cat ) : ?><span class="tgs-news-card-cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
                <span class="tgs-news-card-date"><?php echo esc_html( $date ); ?></span>
            </span>
            <span class="tgs-news-card-title"><?php echo esc_html( $title ); ?></span>
            <?php if ( $exc ) : ?><span class="tgs-news-card-exc"><?php echo esc_html( $exc ); ?></span><?php endif; ?>
            <span class="tgs-news-card-link">Weiterlesen →</span>
        </span>
    </a>
    <?php
    return trim( preg_replace( '/>\s+</', '><', ob_get_clean() ) );
}

/** [tgs_news_teaser anzahl="3"] — Startseiten-Teaser. */
function tgs_shortcode_news_teaser( $atts ) {
    $atts  = shortcode_atts( array( 'anzahl' => 3 ), $atts );
    $posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => intval( $atts['anzahl'] ), 'post_status' => 'publish' ) );
    if ( empty( $posts ) ) return '<p class="tgs-news-empty">Aktuell keine Beiträge.</p>';
    $html = '<div class="tgs-news-grid">';
    foreach ( $posts as $p ) $html .= tgs_news_card_html( $p->ID );
    return $html . '</div>';
}
add_shortcode( 'tgs_news_teaser', 'tgs_shortcode_news_teaser' );

/** [tgs_news_liste anzahl="12"] — Übersicht. */
function tgs_shortcode_news_liste( $atts ) {
    $atts  = shortcode_atts( array( 'anzahl' => 12 ), $atts );
    $posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => intval( $atts['anzahl'] ), 'post_status' => 'publish' ) );
    if ( empty( $posts ) ) return '<p class="tgs-news-empty">Aktuell keine Beiträge.</p>';
    $html = '<div class="tgs-news-grid tgs-news-grid--liste">';
    foreach ( $posts as $p ) $html .= tgs_news_card_html( $p->ID );
    return $html . '</div>';
}
add_shortcode( 'tgs_news_liste', 'tgs_shortcode_news_liste' );

/** [tgs_news_detail] — Artikelseite (nutzt den aktuellen Beitrag). */
function tgs_shortcode_news_detail() {
    $post_id = get_the_ID();
    if ( get_post_type( $post_id ) !== 'post' ) return '';

    $img   = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'large' ) : '';
    $cats  = get_the_category( $post_id );
    $cat   = $cats ? $cats[0]->name : '';
    $date  = get_the_date( 'j. F Y', $post_id );
    $author= get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );

    ob_start();
    ?>
    <div class="tgs-news">
        <?php if ( $img ) : ?>
        <div class="tgs-news-hero tgs-news-hero--photo" style="background-image:linear-gradient(rgba(20,32,22,.15),rgba(20,32,22,.72)),url(<?php echo esc_url( $img ); ?>);">
            <div class="tgs-news-hero-inner">
                <?php if ( $cat ) : ?><span class="tgs-news-cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
                <h1 class="tgs-news-h1"><?php echo esc_html( get_the_title() ); ?></h1>
                <div class="tgs-news-meta"><?php echo esc_html( $date ); ?><?php if ( $author ) echo ' · ' . esc_html( $author ); ?></div>
            </div>
        </div>
        <?php else : ?>
        <div class="tgs-news-header">
            <?php if ( $cat ) : ?><span class="tgs-news-cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
            <h1 class="tgs-news-h1"><?php echo esc_html( get_the_title() ); ?></h1>
            <div class="tgs-news-meta"><?php echo esc_html( $date ); ?><?php if ( $author ) echo ' · ' . esc_html( $author ); ?></div>
        </div>
        <?php endif; ?>

        <div class="tgs-news-body">
            <div class="tgs-news-content"><?php the_content(); ?></div>

            <div class="tgs-news-foot">
                <a href="<?php echo esc_url( home_url( '/aktuelles' ) ); ?>" class="tgs-news-back">← Zu allen Beiträgen</a>
            </div>

            <?php
            // Verwandte Beiträge (gleiche Kategorie, sonst neueste)
            $args = array( 'post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => array( $post_id ), 'post_status' => 'publish' );
            if ( $cats ) {
                $args['category__in'] = array( $cats[0]->term_id );
            }
            $related = get_posts( $args );
            if ( count( $related ) < 3 ) {
                $fill = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => array_merge( array( $post_id ), wp_list_pluck( $related, 'ID' ) ), 'post_status' => 'publish' ) );
                $related = array_merge( $related, $fill );
                $related = array_slice( $related, 0, 3 );
            }
            if ( $related ) :
            ?>
            <div class="tgs-news-related">
                <div class="tgs-news-related-title">Weitere Beiträge</div>
                <?php
                $rel_html = '';
                foreach ( $related as $rel ) $rel_html .= tgs_news_card_html( $rel->ID );
                echo '<div class="tgs-news-grid">' . $rel_html . '</div>';
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tgs_news_detail', 'tgs_shortcode_news_detail' );
