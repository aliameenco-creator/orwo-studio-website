<?php
/** ORWO Studios native block theme. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/includes/media.php';
require_once __DIR__ . '/includes/setup.php';
require_once __DIR__ . '/includes/contact-form.php';
require_once __DIR__ . '/includes/form-settings.php';
require_once __DIR__ . '/includes/github-updater.php';

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_editor_style( array( 'assets/site.css', 'assets/editor.css' ) );
} );
add_action( 'wp_enqueue_scripts', function () {
    $v = wp_get_theme()->get( 'Version' );
    wp_enqueue_style( 'orwost-theme', get_theme_file_uri( 'assets/site.css' ), array(), $v );
    wp_enqueue_script( 'orwost-theme', get_theme_file_uri( 'assets/site.js' ), array(), $v, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );
// Hint the browser to start the two variable fonts early; they are used above the fold.
add_action( 'wp_head', function () {
    // Marks JavaScript as available before first paint, so scroll animations never flash content.
    echo "<script>document.documentElement.classList.add('js')</script>";
    foreach ( array( 'playfair-normal-latin', 'manrope-normal-latin' ) as $font ) {
        echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( 'assets/fonts/' . $font . '.woff2' ) ) . '" as="font" type="font/woff2" crossorigin>';
    }
}, 1 );

/** Resolve portable bundle tokens: Media Library attachments when imported, theme copies until then. */
function orwost_resolve( $markup ) {
    $ids = orwost_media_ids();
    $markup = preg_replace_callback( '~<!-- wp:image (\{[^}]*\}) -->(.*?)<!-- /wp:image -->~s', function ( $m ) use ( $ids ) {
        if ( ! preg_match( '~/assets/img/([a-z0-9]+)-\d+\.webp~', $m[2], $k ) || empty( $ids[ $k[1] ] ) ) { return $m[0]; }
        $id = (int) $ids[ $k[1] ];
        return '<!-- wp:image ' . str_replace( '"id":0', '"id":' . $id, $m[1] ) . ' -->' . str_replace( 'wp-image-0', 'wp-image-' . $id, $m[2] ) . '<!-- /wp:image -->';
    }, $markup );
    $markup = preg_replace_callback( '~(?<![a-zA-Z0-9_./:-])/assets/img/([a-z0-9]+)-(\d+)\.webp~', function ( $m ) use ( $ids ) {
        $url = isset( $ids[ $m[1] ] ) ? wp_get_attachment_url( $ids[ $m[1] ] ) : '';
        return $url ?: get_theme_file_uri( 'assets/img/' . $m[1] . '-' . $m[2] . '.webp' );
    }, $markup );
    $markup = preg_replace_callback( '~(?<![a-zA-Z0-9_./:-])/assets/(logo-white|logo-black)\.png~', function ( $m ) {
        return get_theme_file_uri( 'assets/' . $m[1] . '.png' );
    }, $markup );
    return preg_replace_callback( '~(href="|"url":")(/(?!/)[^"<>]*)"~', function ( $m ) {
        return $m[1] . esc_url( home_url( $m[2] ) ) . '"';
    }, $markup );
}
add_filter( 'render_block', function ( $html ) { return orwost_resolve( $html ); }, 20 );

/* ---------------------------------------------------------------- shortcodes */
add_shortcode( 'orwost_form', function ( $attrs ) {
    $atts = shortcode_atts( array( 'id' => 'contact' ), $attrs );
    ob_start(); orwost_form( sanitize_key( $atts['id'] ) ); return ob_get_clean();
} );
add_shortcode( 'orwost_copyright', function () { return '<p class="copyright">ORWO Studios &copy; ' . esc_html( wp_date( 'Y' ) ) . '</p>'; } );

/** Embedded Google map; the address is editable in the shortcode block: [orwost_map address="…"]. */
add_shortcode( 'orwost_map', function ( $attrs ) {
    $atts = shortcode_atts( array( 'address' => 'Highway 68 ORWO studios Jackson, Louisiana 70748 United States' ), $attrs );
    $q = rawurlencode( $atts['address'] );
    return '<div class="orwost-map"><iframe title="' . esc_attr( 'Map: ' . $atts['address'] ) . '" src="' . esc_url( 'https://maps.google.com/maps?q=' . $q . '&z=12&output=embed' ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe><a class="map-link" href="' . esc_url( 'https://www.google.com/maps/search/?api=1&query=' . $q ) . '" target="_blank" rel="noreferrer noopener">Open in Google Maps ↗</a></div>';
} );

function orwost_social_links() {
    static $links = null;
    if ( null === $links ) { $links = (array) json_decode( (string) file_get_contents( get_theme_file_path( 'parts/socials.json' ) ), true ); }
    return $links;
}
function orwost_icon( $name ) {
    $paths = array(
        'Facebook'  => 'M14 8.5V6.6c0-.9.6-1.1 1-1.1h2.6V1.6L14 1.5c-3.9 0-4.8 2.9-4.8 4.8v2.2H6.9v4h2.3V22.5H14v-10h3.2l.4-4z',
        'Instagram' => 'M12 7.3a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0zM21.9 8c-.1-1.5-.4-2.8-1.5-3.9S17.9 2.7 16.4 2.6C14.9 2.5 9.1 2.5 7.6 2.6 6.1 2.7 4.8 3 3.7 4.1S2.3 6.5 2.2 8c-.1 1.5-.1 6.4 0 8 .1 1.5.4 2.8 1.5 3.9s2.4 1.4 3.9 1.5c1.5.1 6.4.1 8 0 1.5-.1 2.8-.4 3.9-1.5s1.4-2.4 1.5-3.9c.1-1.6.1-6.4-.1-8zm-2 9.7a3.2 3.2 0 0 1-1.8 1.8c-1.3.5-4.3.4-5.7.4s-4.4.1-5.7-.4a3.2 3.2 0 0 1-1.8-1.8c-.5-1.3-.4-4.3-.4-5.7s-.1-4.4.4-5.7a3.2 3.2 0 0 1 1.8-1.8c1.3-.5 4.3-.4 5.7-.4s4.4-.1 5.7.4a3.2 3.2 0 0 1 1.8 1.8c.5 1.3.4 4.3.4 5.7s.1 4.4-.4 5.7z',
        'X'         => 'M17.8 2.5h3.1l-6.8 7.8 8 10.6h-6.3l-4.9-6.4-5.6 6.4H2.2l7.3-8.3L1.9 2.5h6.4l4.4 5.9 5.1-5.9zm-1.1 16.5h1.7L7.4 4.3H5.6L16.7 19z',
        'LinkedIn'  => 'M5.4 8.5H1.9v12.4h3.5V8.5zM3.7 2.8a2 2 0 1 0 0 4.1 2 2 0 0 0 0-4.1zM22 13.6c0-3.3-.7-5.4-4.5-5.4-1.8 0-3 .7-3.5 1.6V8.5H10.6v12.4h3.5v-6.1c0-1.6.3-3.2 2.3-3.2s2 1.8 2 3.3v6h3.6v-7.3z',
        'YouTube'   => 'M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12 31 31 0 0 0 1 16.8a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1c.4-1.6.5-4.8.5-4.8s0-3.2-.5-4.8zM9.7 15V9l5.8 3-5.8 3z',
    );
    return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="' . esc_attr( $paths[ $name ] ?? '' ) . '"/></svg>';
}
add_shortcode( 'orwost_social', function ( $attrs ) {
    $atts = shortcode_atts( array( 'labels' => '' ), $attrs );
    $out = '<ul class="orwost-social' . ( $atts['labels'] ? ' with-labels' : '' ) . '">';
    foreach ( orwost_social_links() as $link ) {
        $out .= '<li><a href="' . esc_url( $link['url'] ) . '" target="_blank" rel="noreferrer noopener" aria-label="' . esc_attr( 'ORWO Studios on ' . $link['name'] ) . '">' . orwost_icon( $link['name'] ) . ( $atts['labels'] ? '<span>' . esc_html( $link['name'] ) . '</span>' : '' ) . '</a></li>';
    }
    return $out . '</ul>';
} );

/** Category chips for the blog index (original /blog/categories/<slug> addresses). */
add_shortcode( 'orwost_categories', function () {
    $terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) );
    if ( is_wp_error( $terms ) || ! $terms ) { return ''; }
    $current = is_category() ? get_queried_object_id() : 0;
    $out = '<nav class="orwost-chips" aria-label="Blog categories"><a href="' . esc_url( home_url( '/blog/' ) ) . '"' . ( $current ? '' : ' aria-current="page"' ) . '>All</a>';
    foreach ( $terms as $term ) {
        if ( 'uncategorized' === $term->slug ) { continue; }
        $out .= '<a href="' . esc_url( get_term_link( $term ) ) . '"' . ( $current === $term->term_id ? ' aria-current="page"' : '' ) . '>' . esc_html( $term->name ) . '</a>';
    }
    return $out . '</nav>';
} );

add_shortcode( 'orwost_reading_time', function () {
    $words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', get_the_ID() ) ) );
    return '<p class="orwost-reading-time">' . esc_html( max( 1, (int) ceil( $words / 220 ) ) . ' min read' ) . '</p>';
} );

/** "More from the blog" on an article never repeats the article being read. */
add_filter( 'query_loop_block_query_vars', function ( $query, $block ) {
    if ( is_singular( 'post' ) && 77 === (int) ( $block->context['queryId'] ?? 0 ) ) {
        $query['post__not_in'] = array( get_queried_object_id() );
    }
    return $query;
}, 10, 2 );

/** Editor-created pages without the seeded hero get a native title; seeded layouts carry their own H1. */
add_filter( 'render_block_core/group', function ( $html, $block ) {
    if ( ! empty( $block['attrs']['className'] ) && str_contains( $block['attrs']['className'], 'orwost-auto-hero' ) && str_contains( (string) get_post_field( 'post_content', get_queried_object_id() ), 'orwost-hero' ) ) { return ''; }
    return $html;
}, 10, 2 );

/* ---------------------------------------------------------------- original URL structure */
function orwost_request_path() {
    $path = trim( rawurldecode( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ) ), '/' );
    $base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
    return ( $base && str_starts_with( $path, $base . '/' ) ) ? substr( $path, strlen( $base ) + 1 ) : $path;
}
/**
 * Articles live at /blog/<slug>/ and categories at /blog/categories/<slug>/ (the original Strikingly structure).
 * Resolved from the request path, so it never depends on rewrite rules having been flushed on the host.
 */
add_filter( 'request', function ( $vars ) {
    if ( is_admin() ) { return $vars; }
    $path = orwost_request_path();
    if ( preg_match( '~^blog/categories/([^/]+)(?:/page/(\d+))?$~', $path, $m ) ) {
        $term = get_term_by( 'slug', sanitize_title( $m[1] ), 'category' );
        if ( $term ) { return array( 'category_name' => $term->slug ) + ( empty( $m[2] ) ? array() : array( 'paged' => (int) $m[2] ) ); }
        return $vars;
    }
    if ( ! preg_match( '~^blog/([^/]+)$~', $path, $m ) || 'categories' === $m[1] || get_page_by_path( 'blog/' . $m[1] ) ) { return $vars; }
    $slug = sanitize_title( $m[1] );
    $found = get_posts( array( 'post_type' => 'post', 'name' => $slug, 'post_status' => array( 'publish', 'private', 'future', 'draft', 'pending' ), 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) );
    if ( ! $found ) { return $vars; }
    $keep = array_intersect_key( $vars, array_flip( array( 'preview', 'p', 'preview_id', 'preview_nonce', 'cpage' ) ) );
    return array_merge( $keep, array( 'name' => $slug, 'post_type' => 'post' ) );
} );
add_filter( 'post_link', function ( $link, $post ) {
    if ( '' === (string) get_option( 'permalink_structure' ) || '' === $post->post_name || ! in_array( $post->post_status, array( 'publish', 'private', 'future' ), true ) ) { return $link; }
    return home_url( user_trailingslashit( '/blog/' . $post->post_name ) );
}, 10, 2 );
add_filter( 'term_link', function ( $link, $term, $taxonomy ) {
    if ( 'category' !== $taxonomy || '' === (string) get_option( 'permalink_structure' ) ) { return $link; }
    return home_url( user_trailingslashit( '/blog/categories/' . $term->slug ) );
}, 10, 3 );
add_filter( 'strict_redirect_guess_404_permalink', '__return_true' );
add_action( 'template_redirect', function () {
    if ( is_preview() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? '', array( 'GET', 'HEAD' ), true ) ) { return; }
    $path = orwost_request_path();
    // Platform-only and duplicate addresses of the old site: one address per page.
    $aliases = array( 'home' => '/', 'film-studio' => '/', '_blog' => '/blog/', 'bnb' => '/hotel/', 'blog/categories' => '/blog/', 'pages' => '/pages/cookie-policy/', 'c/apps/google_map' => '/contact/' );
    if ( isset( $aliases[ $path ] ) ) { wp_safe_redirect( home_url( $aliases[ $path ] ), 301 ); exit; }
    if ( preg_match( '~^blog/categories/([^/]+)$~', $path, $m ) && ! term_exists( sanitize_title( $m[1] ), 'category' ) ) { wp_safe_redirect( home_url( '/blog/' ), 301 ); exit; }
    if ( is_singular( 'post' ) && 'publish' === get_post_status() && '' !== (string) get_option( 'permalink_structure' ) && 'blog/' . rawurldecode( (string) get_post_field( 'post_name' ) ) !== $path ) {
        wp_safe_redirect( get_permalink(), 301 ); exit;
    }
    if ( 'search' === $path ) { wp_safe_redirect( add_query_arg( 's', sanitize_text_field( is_string( $_GET['q'] ?? null ) ? wp_unslash( $_GET['q'] ) : '' ), home_url( '/' ) ), 301 ); exit; }
} );

/* ---------------------------------------------------------------- pages before setup */
/** The original page for this address when no published WordPress page exists yet (fresh install, before setup). */
function orwost_fallback_row() {
    static $row = false;
    if ( false !== $row ) { return $row; }
    $row = null;
    $path = orwost_request_path();
    foreach ( orwost_pages_data() as $candidate ) {
        if ( 'post' === ( $candidate['type'] ?? 'page' ) || $candidate['path'] !== $path ) { continue; }
        if ( '' === $path ) {
            if ( 'page' !== get_option( 'show_on_front' ) || ! get_option( 'page_on_front' ) ) { $row = $candidate; }
        } elseif ( ! get_page_by_path( $path ) || 'publish' !== get_post_status( get_page_by_path( $path ) ) ) {
            $row = $candidate;
        }
        break;
    }
    return $row;
}
add_action( 'template_redirect', function () {
    if ( is_admin() || is_preview() || ( ! is_404() && ! is_front_page() && ! is_home() ) || is_paged() ) { return; }
    $row = orwost_fallback_row();
    if ( ! $row ) { return; }
    global $_wp_current_template_content, $wp_query;
    $wp_query->is_404 = false;
    status_header( 200 );
    $markup = (string) file_get_contents( get_theme_file_path( 'parts/pages/' . $row['slug'] . '.html' ) );
    $_wp_current_template_content = '<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /--><!-- wp:group {"tagName":"main","className":"site-main","anchor":"main"} --><main id="main" class="wp-block-group site-main">' . $markup . '</main><!-- /wp:group --><!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
    add_filter( 'pre_get_document_title', function () use ( $row ) { return $row['seo_title'] ?? $row['title'] . ' - ORWO Studios'; }, 30 );
    add_filter( 'body_class', function ( $classes ) { $classes[] = 'orwost-fallback'; return $classes; } );
    include ABSPATH . WPINC . '/template-canvas.php';
    exit;
}, 20 );

/* ---------------------------------------------------------------- SEO */
add_filter( 'pre_get_document_title', function ( $title ) {
    if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || ! is_singular() ) { return $title; }
    return get_post_meta( get_queried_object_id(), '_orwost_seo_title', true ) ?: $title;
} );
add_action( 'wp_head', function () {
    if ( ! has_site_icon() ) { echo '<link rel="icon" href="' . esc_url( get_theme_file_uri( 'assets/favicon.png' ) ) . '"><link rel="apple-touch-icon" href="' . esc_url( get_theme_file_uri( 'assets/favicon.png' ) ) . '">'; }
    echo '<meta name="theme-color" content="#0c0c0d">';
    if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) { return; }
    $row = orwost_fallback_row();
    if ( $row ) {
        if ( ! empty( $row['description'] ) ) { echo '<meta name="description" content="' . esc_attr( $row['description'] ) . '">'; }
        return;
    }
    if ( ! is_singular() ) { return; }
    $id = get_queried_object_id();
    $description = get_post_meta( $id, '_orwost_description', true );
    if ( $description ) { echo '<meta name="description" content="' . esc_attr( $description ) . '">'; }
    echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '"><meta property="og:url" content="' . esc_url( get_permalink( $id ) ) . '"><meta property="og:type" content="' . ( is_single() ? 'article' : 'website' ) . '"><meta property="og:site_name" content="ORWO Studios">';
    if ( $description ) { echo '<meta property="og:description" content="' . esc_attr( $description ) . '">'; }
    if ( has_post_thumbnail( $id ) ) { echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( $id, 'full' ) ) . '">'; }
} );

/** FAQPage structured data from the FAQ page's own (editable) question blocks; runs alongside SEO plugins. */
add_action( 'wp_head', function () {
    if ( ! is_page( 'faq' ) ) { return; }
    if ( ! preg_match_all( '~<details[^>]*>\s*<summary>(.*?)</summary>(.*?)</details>~s', (string) get_post_field( 'post_content', get_queried_object_id() ), $m, PREG_SET_ORDER ) ) { return; }
    $items = array();
    foreach ( $m as $qa ) {
        $answer = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( preg_replace( '~<div class="wp-block-buttons.*?</div>\s*</div>~s', '', $qa[2] ) ) ) );
        $question = trim( wp_strip_all_tags( $qa[1] ) );
        if ( $question && $answer ) { $items[] = array( '@type' => 'Question', 'name' => html_entity_decode( $question ), 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => html_entity_decode( $answer ) ) ); }
    }
    if ( $items ) { echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>'; }
} );
