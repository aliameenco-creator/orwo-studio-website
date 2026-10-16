<?php
/** Explicit, repeatable setup. No page content is changed merely by theme activation. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function orwost_pages_data() {
    static $rows = null;
    if ( null === $rows ) { $rows = (array) json_decode( (string) file_get_contents( get_theme_file_path( 'parts/pages.json' ) ), true ); }
    return $rows;
}

/** Original Strikingly blog categories at their original /blog/categories/<slug> addresses. */
function orwost_seed_categories() {
    $map = array();
    foreach ( (array) json_decode( (string) file_get_contents( get_theme_file_path( 'parts/categories.json' ) ), true ) as $cat ) {
        $term = get_term_by( 'slug', $cat['slug'], 'category' );
        if ( ! $term ) {
            $made = wp_insert_term( $cat['name'], 'category', array( 'slug' => $cat['slug'] ) );
            if ( is_wp_error( $made ) ) { continue; }
            $term = get_term( $made['term_id'], 'category' );
        }
        $map[ $cat['name'] ] = (int) $term->term_id;
    }
    return $map;
}

/** WordPress's untouched starter content, which must never stand in for a real page. */
function orwost_is_sample( $post, $title, $phrase ) {
    return $post && $title === $post->post_title && str_contains( $post->post_content, $phrase );
}

function orwost_pages_seed() {
    if ( ! current_user_can( 'manage_options' ) ) { return array(); }
    $report = array();
    $hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
    if ( orwost_is_sample( $hello, 'Hello world!', 'Welcome to WordPress. This is your first post.' ) && 'trash' !== $hello->post_status ) { wp_trash_post( $hello->ID ); $report[] = 'WordPress sample post "Hello world!" moved to the trash (untouched).'; }
    $sample = get_page_by_path( 'sample-page' );
    if ( orwost_is_sample( $sample, 'Sample Page', 'This is an example page.' ) && 'trash' !== $sample->post_status ) { wp_trash_post( $sample->ID ); $report[] = 'WordPress "Sample Page" moved to the trash (untouched).'; }
    $categories = orwost_seed_categories();
    $version = wp_get_theme()->get( 'Version' );
    foreach ( orwost_pages_data() as $row ) {
        $key = $row['slug'];
        $type = $row['type'] ?? 'page';
        if ( get_option( 'orwost_page_' . $key ) ) { $report[] = $row['title'] . ': already created earlier; edits preserved.'; continue; }
        $existing = get_page_by_path( '' === $row['path'] ? 'home' : ( 'post' === $type ? $key : $row['path'] ), OBJECT, array( $type ) );
        if ( ! $existing ) {
            $matches = get_posts( array( 'post_type' => $type, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ), 'name' => $key, 'numberposts' => 1 ) );
            $existing = $matches[0] ?? null;
        }
        $takeover = 0;
        if ( $existing && ! empty( $row['privacy'] ) && (int) get_option( 'wp_page_for_privacy_policy' ) === (int) $existing->ID && 'draft' === $existing->post_status && str_contains( $existing->post_content, 'Suggested text:' ) ) {
            $takeover = (int) $existing->ID; // WordPress's sample privacy draft becomes the site's own page (same ID).
        } elseif ( $existing ) {
            $report[] = $row['title'] . ': SKIPPED — an existing record already uses this address.'; continue;
        }
        $parent = 0;
        if ( 'pages/cookie-policy' === $row['path'] ) {
            $container = get_page_by_path( 'pages' );
            $parent = $container ? $container->ID : wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Legal', 'post_name' => 'pages', 'post_status' => 'publish' ), true );
            if ( is_wp_error( $parent ) ) { $report[] = 'Cookie Policy: FAILED to create the /pages/ parent.'; continue; }
        }
        $markup = orwost_resolve( (string) file_get_contents( get_theme_file_path( 'parts/pages/' . $key . '.html' ) ) );
        $args = array( 'post_type' => $type, 'post_status' => 'publish', 'post_name' => $key, 'post_title' => $row['title'], 'post_content' => $markup, 'post_parent' => $parent, 'comment_status' => 'closed', 'ping_status' => 'closed' );
        if ( ! empty( $row['date'] ) ) { $args['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( $row['date'] ) ); $args['post_date_gmt'] = get_gmt_from_date( $args['post_date'] ); }
        if ( ! empty( $row['excerpt'] ) ) { $args['post_excerpt'] = wp_trim_words( $row['excerpt'], 40 ); }
        if ( $takeover ) { $args['ID'] = $takeover; }
        $id = $takeover ? wp_update_post( wp_slash( $args ), true ) : wp_insert_post( wp_slash( $args ), true );
        if ( is_wp_error( $id ) ) { $report[] = $row['title'] . ': FAILED — ' . $id->get_error_message(); continue; }
        update_option( 'orwost_page_' . $key, $id, false );
        update_post_meta( $id, '_orwost_original_path', '/' . $row['path'] );
        // Design version and fingerprint let a later theme release update only pages nobody has edited.
        update_post_meta( $id, '_orwost_design_version', $version );
        update_post_meta( $id, '_orwost_page_revision', (int) ( $row['revision'] ?? 1 ) );
        update_post_meta( $id, '_orwost_fingerprint', md5( (string) get_post_field( 'post_content', $id ) ) );
        $title = $row['seo_title'] ?? $row['title'] . ' - ORWO Studios';
        update_post_meta( $id, '_orwost_seo_title', $title );
        update_post_meta( $id, '_yoast_wpseo_title', $title );
        if ( ! empty( $row['description'] ) ) { update_post_meta( $id, '_orwost_description', $row['description'] ); update_post_meta( $id, '_yoast_wpseo_metadesc', $row['description'] ); }
        if ( 'post' === $type && ! empty( $row['categories'] ) ) {
            wp_set_post_categories( $id, array_values( array_filter( array_map( function ( $name ) use ( $categories ) { return $categories[ $name ] ?? 0; }, $row['categories'] ) ) ) );
        }
        if ( ! empty( $row['featured'] ) && preg_match( '~/assets/img/([a-f0-9]+)-~', $row['featured'], $m ) ) {
            $ids = orwost_media_ids(); if ( ! empty( $ids[ $m[1] ] ) ) { set_post_thumbnail( $id, $ids[ $m[1] ] ); }
        }
        if ( ! empty( $row['front'] ) ) { update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $id ); }
        if ( ! empty( $row['privacy'] ) ) { update_option( 'wp_page_for_privacy_policy', $id ); }
        $report[] = $row['title'] . ( $takeover ? ': created from the WordPress privacy draft.' : ': created.' );
    }
    $report[] = orwost_create_menu();
    // Native Site Editor parts, saved once with real Media Library URLs. Theme updates never overwrite them.
    foreach ( array( 'header', 'footer' ) as $slug ) {
        if ( get_option( 'orwost_part_' . $slug ) ) { continue; }
        $existing = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' );
        if ( $existing && 'custom' === $existing->source ) { continue; }
        $id = wp_insert_post( wp_slash( array( 'post_type' => 'wp_template_part', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => ucfirst( $slug ), 'post_content' => orwost_resolve( (string) file_get_contents( get_theme_file_path( 'parts/' . $slug . '.html' ) ) ), 'tax_input' => array( 'wp_theme' => get_stylesheet(), 'wp_template_part_area' => $slug ) ) ), true );
        if ( ! is_wp_error( $id ) ) { update_option( 'orwost_part_' . $slug, $id, false ); }
    }
    update_option( 'orwost_setup_report', $report, false );
    update_option( 'orwost_setup_done', 1, false );
    flush_rewrite_rules();
    return $report;
}

/** True when no WordPress menu is assigned to the header yet. */
function orwost_menu_pending() { return ! has_nav_menu( 'primary' ); }

/**
 * Creates the "Main menu" (Appearance → Menus) from parts/menu.json and assigns it to the header.
 * Never touches a menu that is already assigned; an existing menu named "Main menu" is assigned as it is.
 */
function orwost_create_menu() {
    if ( ! orwost_menu_pending() ) { return 'Main menu: a menu is already assigned to the header; it was kept as it is.'; }
    $menu = wp_get_nav_menu_object( 'Main menu' );
    $id = $menu ? (int) $menu->term_id : wp_create_nav_menu( 'Main menu' );
    if ( is_wp_error( $id ) ) { return 'Main menu: FAILED — ' . $id->get_error_message(); }
    if ( ! $menu ) {
        $add = function ( $item, $parent ) use ( $id ) {
            $external = str_starts_with( $item['url'], 'http' );
            $args = array( 'menu-item-title' => $item['title'], 'menu-item-url' => $external ? $item['url'] : home_url( $item['url'] ), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent );
            if ( $external ) { $args['menu-item-target'] = '_blank'; }
            if ( '/contact/' === $item['url'] && ! $parent ) { $args['menu-item-classes'] = 'menu-contact'; }
            return wp_update_nav_menu_item( $id, 0, $args );
        };
        foreach ( (array) json_decode( (string) file_get_contents( get_theme_file_path( 'parts/menu.json' ) ), true ) as $item ) {
            $top = $add( $item, 0 );
            if ( is_wp_error( $top ) ) { continue; }
            foreach ( $item['children'] as $child ) { $add( $child, (int) $top ); }
        }
    }
    set_theme_mod( 'nav_menu_locations', array_merge( (array) get_theme_mod( 'nav_menu_locations', array() ), array( 'primary' => $id ) ) );
    return 'Main menu: created and shown in the header. Edit it under Appearance → Menus.';
}

/**
 * Pages whose design a newer theme release changed (pages.json "revision" above the stored one).
 * "edited" means someone changed the page since setup created it (content no longer matches its fingerprint).
 */
function orwost_page_updates() {
    $out = array();
    foreach ( orwost_pages_data() as $row ) {
        $revision = (int) ( $row['revision'] ?? 1 );
        $id = (int) get_option( 'orwost_page_' . $row['slug'] );
        $post = $id ? get_post( $id ) : null;
        if ( $revision < 2 || ! $post || 'trash' === $post->post_status ) { continue; }
        if ( (int) ( get_post_meta( $id, '_orwost_page_revision', true ) ?: 1 ) >= $revision ) { continue; }
        $out[ $row['slug'] ] = array( 'row' => $row, 'post' => $post, 'edited' => md5( $post->post_content ) !== get_post_meta( $id, '_orwost_fingerprint', true ) );
    }
    return $out;
}
function orwost_apply_page_updates( $slugs ) {
    $report = array();
    $version = wp_get_theme()->get( 'Version' );
    foreach ( orwost_page_updates() as $slug => $item ) {
        $row = $item['row']; $id = $item['post']->ID;
        if ( ! in_array( $slug, $slugs, true ) ) { $report[] = $row['title'] . ': left unchanged (not selected).'; continue; }
        wp_save_post_revision( $id );
        $done = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => orwost_resolve( (string) file_get_contents( get_theme_file_path( 'parts/pages/' . $slug . '.html' ) ) ) ) ), true );
        if ( is_wp_error( $done ) ) { $report[] = $row['title'] . ': FAILED — ' . $done->get_error_message(); continue; }
        update_post_meta( $id, '_orwost_page_revision', (int) $row['revision'] );
        update_post_meta( $id, '_orwost_design_version', $version );
        update_post_meta( $id, '_orwost_fingerprint', md5( (string) get_post_field( 'post_content', $id ) ) );
        // A description the owner set in Yoast or elsewhere is never replaced.
        if ( ! empty( $row['description'] ) && ! get_post_meta( $id, '_yoast_wpseo_metadesc', true ) ) {
            update_post_meta( $id, '_orwost_description', $row['description'] );
            update_post_meta( $id, '_yoast_wpseo_metadesc', $row['description'] );
        }
        $report[] = $row['title'] . ': updated to the new design (previous version kept under Revisions).';
    }
    update_option( 'orwost_setup_report', $report ?: array( 'No page updates were waiting.' ), false );
}
add_action( 'admin_post_orwost_update_pages', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'orwost_update_pages' );
    $slugs = isset( $_POST['orwost_pages'] ) && is_array( $_POST['orwost_pages'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['orwost_pages'] ) ) : array();
    orwost_apply_page_updates( $slugs );
    if ( ! empty( $_POST['orwost_menu'] ) ) { update_option( 'orwost_setup_report', array_merge( array( orwost_create_menu() ), (array) get_option( 'orwost_setup_report', array() ) ), false ); }
    wp_safe_redirect( admin_url( 'themes.php?page=orwost-setup' ) ); exit;
} );
add_action( 'admin_notices', function () {
    if ( current_user_can( 'manage_options' ) && get_option( 'orwost_setup_done' ) && ( orwost_page_updates() || orwost_menu_pending() ) && 'appearance_page_orwost-setup' !== ( get_current_screen()->id ?? '' ) ) {
        echo '<div class="notice notice-info"><p><strong>ORWO Studios:</strong> this theme version has updates for your pages or menu. <a href="' . esc_url( admin_url( 'themes.php?page=orwost-setup' ) ) . '">Review and apply theme updates</a>.</p></div>';
    }
} );

add_action( 'admin_menu', function () {
    add_theme_page( 'ORWO Studios Setup', 'ORWO Studios Setup', 'manage_options', 'orwost-setup', function () {
        $updates = get_option( 'orwost_setup_done' ) ? orwost_page_updates() : array();
        $menu_pending = get_option( 'orwost_setup_done' ) && orwost_menu_pending();
        if ( $updates || $menu_pending ) {
            echo '<div class="wrap"><h1>ORWO Studios theme updates</h1><p>This theme version brings the updates below. Pages nobody has edited are ticked. A page that was edited is not ticked: updating it replaces those edits (they stay available under the page\'s Revisions).</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="orwost_update_pages">';
            wp_nonce_field( 'orwost_update_pages' );
            echo '<ul>';
            if ( $menu_pending ) { echo '<li><label><input type="checkbox" name="orwost_menu" value="1" checked> <strong>Main menu</strong> — create the header menu under Appearance → Menus (without Lamplighter Suites), so it can be edited there</label></li>'; }
            foreach ( $updates as $slug => $item ) {
                echo '<li><label><input type="checkbox" name="orwost_pages[]" value="' . esc_attr( $slug ) . '"' . checked( ! $item['edited'], true, false ) . '> <strong>' . esc_html( $item['row']['title'] ) . '</strong> — ' . esc_html( $item['edited'] ? 'edited since setup' : 'unchanged since setup' ) . ' (<a href="' . esc_url( get_permalink( $item['post'] ) ) . '" target="_blank">view</a>)</label></li>';
            }
            echo '</ul><p><button class="button button-primary" type="submit">Apply selected updates</button></p></form></div>';
        }
        $pages = count( array_filter( orwost_pages_data(), function ( $r ) { return 'post' !== ( $r['type'] ?? 'page' ); } ) );
        $posts = count( orwost_pages_data() ) - $pages;
        echo '<div class="wrap"><h1>ORWO Studios</h1><p>Creates the original ' . (int) $pages . ' editable pages at their original addresses, ' . (int) $posts . ' blog articles with their categories, ' . count( orwost_media_manifest() ) . ' Media Library images, the menu, header and footer. Home becomes the front page. Existing pages, articles and edits are never overwritten. Back up an existing site first.</p>';
        echo '<p><a class="button button-primary button-hero" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=orwost_setup' ), 'orwost_setup' ) ) . '">Complete ORWO Studios setup</a></p>';
        echo '<p>Afterwards: edit pages under <strong>Pages</strong>, articles under <strong>Posts</strong>, and the header, footer and menu under <strong>Appearance → Editor</strong>. Form messages are under <strong>Enquiries</strong>; the notification address is under <strong>Settings → General</strong>.</p>';
        echo '<h2>Latest setup report</h2><ul style="list-style:disc;padding-left:20px">';
        foreach ( (array) get_option( 'orwost_setup_report', array() ) as $line ) { echo '<li>' . esc_html( $line ) . '</li>'; }
        echo '</ul></div>';
    } );
} );
add_action( 'admin_notices', function () {
    if ( current_user_can( 'manage_options' ) && ! get_option( 'orwost_setup_done' ) ) { echo '<div class="notice notice-info"><p><strong>ORWO Studios:</strong> <a href="' . esc_url( admin_url( 'themes.php?page=orwost-setup' ) ) . '">Complete ORWO Studios setup</a> to create your editable website.</p></div>'; }
} );
add_action( 'admin_post_orwost_setup', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'orwost_setup' );
    if ( '' === (string) get_option( 'permalink_structure' ) ) { update_option( 'permalink_structure', '/%postname%/' ); }
    if ( orwost_media_pending() ) { wp_safe_redirect( orwost_media_import_url() ); exit; }
    if ( ! get_option( 'orwost_setup_done' ) ) { update_option( 'blogname', 'ORWO Studios' ); update_option( 'blogdescription', 'Film and TV studio in Baton Rouge, Louisiana' ); }
    orwost_pages_seed();
    wp_safe_redirect( admin_url( 'themes.php?page=orwost-setup' ) ); exit;
} );
