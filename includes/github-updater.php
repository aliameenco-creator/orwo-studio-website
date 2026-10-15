<?php
/**
 * Numbered GitHub release updates through WordPress's theme upgrader.
 * Inactive until a repository is assigned to this site (orwost_update_repo); never guesses one.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** "owner/name" of this site's theme repository; empty until the site owner assigns one. */
function orwost_update_repo() { return 'aliameenco-creator/orwo-studio-website'; }
function orwost_update_api() { return 'https://api.github.com/repos/' . orwost_update_repo(); }
function orwost_update_headers() {
    $headers = array( 'Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => '2022-11-28', 'User-Agent' => 'ORWO-Studios-WordPress-Updater' );
    if ( defined( 'ORWOST_GITHUB_TOKEN' ) && is_string( ORWOST_GITHUB_TOKEN ) && ORWOST_GITHUB_TOKEN !== '' ) { $headers['Authorization'] = 'Bearer ' . ORWOST_GITHUB_TOKEN; }
    return $headers;
}
function orwost_release_validate( $data ) {
    if ( ! is_array( $data ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) || ! isset( $data['tag_name'], $data['assets'] ) || ! preg_match( '/^v(\d+\.\d+\.\d+)$/D', $data['tag_name'], $match ) ) { return new WP_Error( 'release', 'No valid stable numbered release is available.' ); }
    foreach ( $data['assets'] as $asset ) {
        if ( 'orwo-studio.zip' !== ( $asset['name'] ?? '' ) ) { continue; }
        if ( empty( $asset['id'] ) || ! is_int( $asset['id'] ) || ! preg_match( '/^sha256:([a-f0-9]{64})$/D', $asset['digest'] ?? '', $digest ) || empty( $asset['size'] ) || $asset['size'] > 90000000 ) { return new WP_Error( 'asset', 'The release package is missing a valid SHA-256 digest or exceeds the size limit.' ); }
        return array( 'version' => $match[1], 'package' => orwost_update_api() . '/releases/assets/' . $asset['id'], 'sha256' => $digest[1], 'size' => (int) $asset['size'], 'notes' => is_string( $data['body'] ?? null ) ? $data['body'] : '' );
    }
    return new WP_Error( 'package', 'The release must include an uploaded orwo-studio.zip asset.' );
}
function orwost_release_check( $force = false ) {
    if ( '' === orwost_update_repo() ) { return new WP_Error( 'repo', 'No GitHub repository has been assigned to this theme yet, so it cannot check for updates. Install new versions by uploading the theme ZIP.' ); }
    if ( ! $force ) { $cached = get_site_transient( 'orwost_github_release' ); if ( is_array( $cached ) || is_wp_error( $cached ) ) { return $cached; } }
    $response = wp_safe_remote_get( orwost_update_api() . '/releases/latest', array( 'headers' => orwost_update_headers(), 'timeout' => 20, 'redirection' => 0, 'limit_response_size' => 1000000 ) );
    if ( is_wp_error( $response ) ) { $error = new WP_Error( 'network', 'GitHub could not be reached. Try again later.' ); set_site_transient( 'orwost_github_release', $error, 5 * MINUTE_IN_SECONDS ); return $error; }
    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) { $error = new WP_Error( 'github', 'GitHub did not return a release. Check that a release exists; GitHub rate limits can also require waiting before checking again.' ); set_site_transient( 'orwost_github_release', $error, 5 * MINUTE_IN_SECONDS ); return $error; }
    $release = orwost_release_validate( json_decode( wp_remote_retrieve_body( $response ), true ) );
    set_site_transient( 'orwost_github_release', $release, is_wp_error( $release ) ? 5 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS );
    return $release;
}
function orwost_release_updates( $transient ) {
    if ( ! is_object( $transient ) || '' === orwost_update_repo() ) { return $transient; }
    // Only contact GitHub from wp-admin, cron or the upgrader, never on front-end page loads.
    if ( ! is_admin() && ! wp_doing_cron() && ! is_array( get_site_transient( 'orwost_github_release' ) ) ) { return $transient; }
    $theme = wp_get_theme( 'orwo-studio' );
    $release = orwost_release_check();
    if ( is_wp_error( $release ) ) { unset( $transient->response['orwo-studio'] ); return $transient; }
    $item = array( 'theme' => 'orwo-studio', 'new_version' => $release['version'], 'url' => 'https://github.com/' . orwost_update_repo() . '/releases', 'package' => $release['package'] );
    if ( version_compare( $release['version'], $theme->get( 'Version' ), '>' ) ) { $transient->response['orwo-studio'] = $item; unset( $transient->no_update['orwo-studio'] ); }
    else { unset( $transient->response['orwo-studio'] ); $transient->no_update['orwo-studio'] = $item; }
    return $transient;
}
add_filter( 'site_transient_update_themes', 'orwost_release_updates' );
add_filter( 'auto_update_theme', function ( $update, $item ) { return 'orwo-studio' === ( $item->theme ?? '' ) ? false : $update; }, 10, 2 );

function orwost_release_zip_validate( $file, $version ) {
    if ( ! class_exists( 'ZipArchive' ) ) { return new WP_Error( 'zip', 'The server needs the PHP ZIP extension to verify this update.' ); }
    $zip = new ZipArchive();
    if ( true !== $zip->open( $file ) ) { return new WP_Error( 'zip', 'The downloaded package is not a valid ZIP.' ); }
    $error = null; $total = 0;
    if ( $zip->numFiles > 3000 ) { $error = 'Too many files in the theme ZIP.'; }
    for ( $i = 0; ! $error && $i < $zip->numFiles; ++$i ) {
        $entry = $zip->statIndex( $i ); $name = $entry['name']; $total += $entry['size'];
        if ( ! str_starts_with( $name, 'orwo-studio/' ) || str_contains( $name, '\\' ) || preg_match( '~(^|/)(\.{1,2}|\.[^/]+)(/|$)~', $name ) || $total > 250000000 ) { $error = 'The theme ZIP contains an unsafe path or exceeds the expanded size limit.'; break; }
        $opsys = 0; $attributes = 0;
        if ( $zip->getExternalAttributesIndex( $i, $opsys, $attributes ) && 0120000 === ( ( $attributes >> 16 ) & 0170000 ) ) { $error = 'Symbolic links are not allowed in update packages.'; }
    }
    $style = $zip->getFromName( 'orwo-studio/style.css' );
    if ( ! $error && ( ! is_string( $style ) || ! preg_match( '/^Version:\s*' . preg_quote( $version, '/' ) . '\s*$/m', $style ) || false === $zip->locateName( 'orwo-studio/functions.php' ) || false === $zip->locateName( 'orwo-studio/theme.json' ) ) ) { $error = 'The package version or required theme files do not match the release.'; }
    $zip->close(); return $error ? new WP_Error( 'package', $error ) : true;
}
/** Downloads the verified release asset (GitHub serves it as application/octet-stream). */
function orwost_release_download( $reply, $package, $upgrader, $hook_extra = array() ) {
    if ( '' === orwost_update_repo() || ! str_starts_with( (string) $package, orwost_update_api() . '/releases/assets/' ) ) { return $reply; }
    $release = orwost_release_check();
    if ( is_wp_error( $release ) || $package !== $release['package'] ) { return new WP_Error( 'package', 'The requested update does not match the checked release.' ); }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $file = wp_tempnam( 'orwo-studio.zip' );
    if ( ! $file ) { return new WP_Error( 'file', 'Cannot create the update temporary file.' ); }
    $headers = orwost_update_headers(); $headers['Accept'] = 'application/octet-stream';
    $args = array( 'headers' => $headers, 'timeout' => 180, 'redirection' => 0, 'stream' => true, 'filename' => $file, 'limit_response_size' => 90000001 );
    $response = wp_safe_remote_get( $package, $args );
    if ( ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), array( 301, 302, 303, 307, 308 ), true ) ) {
        $url = wp_remote_retrieve_header( $response, 'location' );
        $host = wp_parse_url( $url, PHP_URL_HOST );
        if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) || ! in_array( $host, array( 'release-assets.githubusercontent.com', 'objects.githubusercontent.com' ), true ) ) { wp_delete_file( $file ); return new WP_Error( 'redirect', 'GitHub returned an unexpected download host.' ); }
        $args['headers'] = array( 'User-Agent' => 'ORWO-Studios-WordPress-Updater' ); $args['redirection'] = 3;
        $response = wp_safe_remote_get( $url, $args );
    }
    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) || filesize( $file ) !== $release['size'] || ! hash_equals( $release['sha256'], hash_file( 'sha256', $file ) ) ) { wp_delete_file( $file ); return new WP_Error( 'download', 'The update download failed size or SHA-256 verification. No theme files were installed.' ); }
    $valid = orwost_release_zip_validate( $file, $release['version'] );
    if ( is_wp_error( $valid ) ) { wp_delete_file( $file ); return $valid; }
    return $file;
}
add_filter( 'upgrader_pre_download', 'orwost_release_download', 10, 4 );
add_action( 'upgrader_process_complete', function ( $upgrader, $options ) { if ( 'theme' === ( $options['type'] ?? '' ) ) { delete_site_transient( 'orwost_github_release' ); } }, 10, 2 );

add_action( 'admin_menu', function () { add_theme_page( 'ORWO Studios Updates', 'ORWO Studios Updates', 'update_themes', 'orwost-updates', 'orwost_updates_screen' ); } );
function orwost_updates_screen() {
    if ( ! current_user_can( 'update_themes' ) ) { return; }
    $force = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' );
    if ( $force ) { check_admin_referer( 'orwost-check-updates' ); delete_site_transient( 'orwost_github_release' ); }
    $release = orwost_release_check( $force ); $theme = wp_get_theme( 'orwo-studio' );
    echo '<div class="wrap"><h1>ORWO Studios Updates</h1><p>Installed version: <strong>' . esc_html( $theme->get( 'Version' ) ) . '</strong></p><form method="post">';
    wp_nonce_field( 'orwost-check-updates' ); echo '<button class="button" type="submit">Check for updates</button></form>';
    if ( is_wp_error( $release ) ) { echo '<div class="notice notice-warning inline"><p>' . esc_html( $release->get_error_message() ) . '</p></div>'; }
    else {
        echo '<p>Latest release: <strong>' . esc_html( $release['version'] ) . '</strong></p>';
        if ( version_compare( $release['version'], $theme->get( 'Version' ), '>' ) ) {
            $url = wp_nonce_url( admin_url( 'update.php?action=upgrade-theme&theme=orwo-studio' ), 'upgrade-theme_orwo-studio' );
            echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Update theme</a></p>';
        } else { echo '<p>Your theme is up to date.</p>'; }
        echo '<h2>Release notes</h2><div style="white-space:pre-wrap">' . esc_html( $release['notes'] ) . '</div>';
    }
    echo '<p>Back up first. Updates replace theme files only; your pages, articles, Media Library and Site Editor changes stay as they are.</p></div>';
}
