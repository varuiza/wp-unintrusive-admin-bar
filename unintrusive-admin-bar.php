<?php
/**
 * Plugin Name: Unintrusive Admin Bar
 * Description: Replaces the WP Admin Bar with a small toggle icon, so it doesn't stay fixed at the top of every page.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Varuiza
 * Author URI: https://profiles.wordpress.org/varuiza/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: unintrusive-admin-bar
 *
 * Forked and hardened from `plasticbrain`'s abandoned "WP Minimize Admin Bar" (GPLv2 or later).
 *
 * @package vr_uab
 */

	defined( 'ABSPATH' ) || die( 'No direct access to files' );

/**
 * Falls back to no explicit version (WordPress then uses its own version
 * string) if the file is missing, instead of letting filemtime() raise a
 * warning.
 *
 * @param string $path Absolute path to the asset file.
 * @return int|false
 */
function uab_asset_version( $path ) {
	return file_exists( $path ) ? filemtime( $path ) : false;
}

/**
 * Resolves the background/icon colors the toggle button should use, based
 * on the current user's admin color scheme (Settings > Profile), so the
 * button blends in with wp-admin instead of using a hardcoded dark theme.
 *
 * Reuses core's own registered scheme data (the same array core shows as
 * the scheme preview swatches on the profile screen) rather than
 * duplicating each scheme's palette here.
 *
 * @return array{background: string, icon: string, icon_focus: string}
 */
function uab_get_admin_bar_colors() {
	global $_wp_admin_css_colors;

	// register_admin_color_schemes() normally only runs on admin_init, which
	// never fires on the frontend; it has no admin-only dependencies though,
	// so it's safe to call directly here to populate the same global.
	if ( empty( $_wp_admin_css_colors ) && function_exists( 'register_admin_color_schemes' ) ) {
		register_admin_color_schemes();
	}

	$fallback = array(
		'background' => '#333',
		'icon'       => '#ccc',
		'icon_focus' => '#00b9eb',
	);

	$scheme_key = get_user_option( 'admin_color' ) ?: 'fresh';
	$scheme     = $_wp_admin_css_colors[ $scheme_key ] ?? $_wp_admin_css_colors['fresh'] ?? null;

	if ( ! $scheme ) {
		return $fallback;
	}

	return array(
		'background' => sanitize_hex_color( $scheme->colors[0] ?? '' ) ?: $fallback['background'],
		'icon'       => sanitize_hex_color( $scheme->icon_colors['base'] ?? '' ) ?: $fallback['icon'],
		'icon_focus' => sanitize_hex_color( $scheme->icon_colors['focus'] ?? '' ) ?: $fallback['icon_focus'],
	);
}

/**
 * Enqueues the toggle's CSS/JS, honoring SCRIPT_DEBUG the same way core does
 * (see wp-includes/script-loader.php's $suffix pattern) so the unminified
 * source loads while debugging and the minified build ships otherwise.
 */
function uab_toggle_admin_bar_assets() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	// Same convention as wp-includes/script-loader.php: unminified while
	// debugging. Regenerate the .min files with `node scripts/build-assets.mjs`.
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	$css_rel = "css/style{$suffix}.css";
	$js_rel  = "js/app{$suffix}.js";

	$css_url  = plugin_dir_url( __FILE__ ) . $css_rel;
	$js_url   = plugin_dir_url( __FILE__ ) . $js_rel;
	$css_path = plugin_dir_path( __FILE__ ) . $css_rel;
	$js_path  = plugin_dir_path( __FILE__ ) . $js_rel;

	// Depending on core's 'admin-bar' handle guarantees these load after
	// admin-bar.css/js: it already pulls in 'dashicons' (our icons need it),
	// and admin-bar.js also manipulates #wpadminbar, so running after it
	// avoids two scripts racing over the same DOM.
	wp_enqueue_style( 'unintrusive-admin-bar-css', $css_url, array( 'admin-bar' ), uab_asset_version( $css_path ) );

	// Colors ship as CSS custom properties (with the previous hardcoded
	// values as their var() fallback) so style.css stays the single source
	// of truth for the button's layout; only the color values come from PHP.
	$colors = uab_get_admin_bar_colors();
	wp_add_inline_style(
		'unintrusive-admin-bar-css',
		sprintf(
			':root{--uab-bg-color:%1$s;--uab-icon-color:%2$s;--uab-icon-focus-color:%3$s;}',
			$colors['background'],
			$colors['icon'],
			$colors['icon_focus']
		)
	);

	// 'wp-a11y' gives us wp.a11y.speak(), core's screen-reader announcement
	// utility, instead of hand-rolling another aria-live region.
	wp_enqueue_script( 'unintrusive-admin-bar-js', $js_url, array( 'admin-bar', 'wp-a11y' ), uab_asset_version( $js_path ), true );

	wp_localize_script(
		'unintrusive-admin-bar-js',
		'uabL10n',
		array(
			'shownAnnouncement'  => __( 'WP Admin Bar shown', 'unintrusive-admin-bar' ),
			'hiddenAnnouncement' => __( 'WP Admin Bar hidden', 'unintrusive-admin-bar' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'uab_toggle_admin_bar_assets' );

/**
 * Undoes core's own top padding for the admin bar (_admin_bar_bump_cb,
 * hooked on wp_head), since this plugin's toggle replaces the fixed bar
 * and no longer needs that reserved space.
 */
function uab_remove_padding() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	// Leading underscore = WordPress core-private, not public API: if core
	// ever renames or removes it, this becomes a silent no-op and the
	// padding this plugin removes would reappear.
	remove_action( 'wp_head', '_admin_bar_bump_cb' );
}
add_action( 'wp_head', 'uab_remove_padding', 1 );

/**
 * Renders the frontend-only button that reveals the hidden admin bar.
 * Hooked on 'wp_after_admin_bar_render' so it lands right after core
 * prints the bar's own markup, keeping it adjacent in the DOM.
 */
function uab_add_admin_bar_toggle() {
	// Frontend-only by design (see the FAQ in readme.txt). Without this
	// guard the button would also render in wp-admin, since
	// 'wp_after_admin_bar_render' fires there too via 'in_admin_header',
	// even though this plugin's CSS/JS are only enqueued on the frontend.
	if ( is_admin() ) {
		return;
	}

	$label = __( 'Show WP Admin Bar', 'unintrusive-admin-bar' );
	echo '<a href="#" id="uab-btn-show-admin-bar" title="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '" aria-controls="wpadminbar" aria-expanded="false"></a>';
}
add_action( 'wp_after_admin_bar_render', 'uab_add_admin_bar_toggle' );

/**
 * Added as a native Toolbar API node (rather than JS-injected) so it
 * renders server-side with the rest of the bar, reusing core's own
 * ".ab-icon"/".screen-reader-text" markup. No 'parent' is set, so it
 * lands in core's 'root-default' group; priority 5 runs before
 * wp_admin_bar_wp_menu()'s default priority 10, making it the first icon.
 *
 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
 */
function uab_add_admin_bar_hide_toggle( $wp_admin_bar ) {
	// Same guard as uab_add_admin_bar_toggle(): 'admin_bar_menu' also fires in wp-admin.
	if ( is_admin() ) {
		return;
	}

	$label = __( 'Hide WP Admin Bar', 'unintrusive-admin-bar' );

	$wp_admin_bar->add_node(
		array(
			'id'    => 'uab-hide-admin-bar',
			'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html( $label ) . '</span>',
			'href'  => '#',
			'meta'  => array(
				'title' => $label,
			),
		)
	);
}
add_action( 'admin_bar_menu', 'uab_add_admin_bar_hide_toggle', 5 );
