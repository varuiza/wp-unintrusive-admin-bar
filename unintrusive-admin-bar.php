<?php
defined( 'ABSPATH' ) || die( 'No direct access to files' );

/**
 * Plugin Name: Unintrusive Admin Bar
 * Description: Replaces the WP Admin Bar with a small toggle icon, so it doesn't stay fixed at the top of every page.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.0
 * Author: Varuiza
 * Author URI: https://profiles.wordpress.org/varuiza/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: unintrusive-admin-bar
 *
 * Forked and hardened from `plasticbrain`'s abandoned "WP Minimize Admin Bar" (GPLv2 or later).
 */

/**
 * Enqueue the CSS and JS
 */
function uab_toggle_admin_bar_assets() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	// Same $suffix convention WP core uses throughout wp-includes/script-loader.php:
	// unminified while debugging, minified otherwise. Generate the .min files
	// with `node scripts/build-assets.mjs` (see cheatsheets/NPM-CHEATSHEET.md).
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	$css_rel = "css/style{$suffix}.css";
	$js_rel  = "js/app{$suffix}.js";

	$css_url  = plugin_dir_url( __FILE__ ) . $css_rel;
	$js_url   = plugin_dir_url( __FILE__ ) . $js_rel;
	$css_path = plugin_dir_path( __FILE__ ) . $css_rel;
	$js_path  = plugin_dir_path( __FILE__ ) . $js_rel;

	// Depending on WP core's own 'admin-bar' handles guarantees our assets
	// always load after admin-bar.css/js: admin-bar.css already pulls in
	// 'dashicons' (which our icons rely on), and admin-bar.js also reads
	// and manipulates #wpadminbar, so running after it avoids two scripts
	// racing over the same DOM. Our own script is plain JS (no jQuery) on
	// purpose, to avoid forcing that whole library onto every frontend
	// pageview just for this toggle.
	wp_enqueue_style( 'uab-unintrusive-admin-bar-css', $css_url, array( 'admin-bar' ), filemtime( $css_path ) );
	// 'wp-a11y' gives us wp.a11y.speak(), core's own screen-reader
	// announcement utility (see wp-includes/js/dist/a11y.js), instead of
	// us hand-rolling another aria-live region.
	wp_enqueue_script( 'uab-unintrusive-admin-bar-js', $js_url, array( 'admin-bar', 'wp-a11y' ), filemtime( $js_path ), true );

	wp_localize_script(
		'uab-unintrusive-admin-bar-js',
		'uabL10n',
		array(
			'shownAnnouncement'  => __( 'WP Admin Bar shown', 'unintrusive-admin-bar' ),
			'hiddenAnnouncement' => __( 'WP Admin Bar hidden', 'unintrusive-admin-bar' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'uab_toggle_admin_bar_assets' );

/**
 * Remove the 32px of padding the WP Admin Bar adds
 */
function uab_remove_padding() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	remove_action( 'wp_head', '_admin_bar_bump_cb' );
}
add_action( 'wp_head', 'uab_remove_padding', 1 );

/**
 * Add the floating "show admin bar" toggle
 */
function uab_add_admin_bar_toggle() {
	$label = __( 'Show WP Admin Bar', 'unintrusive-admin-bar' );
	echo '<a href="#" id="uab-btn-show-admin-bar" title="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '" aria-controls="wpadminbar" aria-expanded="false"></a>';
}
add_action( 'wp_after_admin_bar_render', 'uab_add_admin_bar_toggle' );

/**
 * Add the "hide admin bar" toggle as a native Toolbar API node, so it
 * renders server-side with the rest of the bar (no popping in once JS
 * runs) and reuses WP core's own icon markup: an ".ab-icon" span plus
 * visually-hidden ".screen-reader-text" for the accessible name, exactly
 * like wp_admin_bar_sidebar_toggle()'s own "Menu" toggle in
 * wp-includes/admin-bar.php (also a plain href="#" node whose click is
 * handled entirely in JS). No 'parent' is set, so it lands in core's
 * own 'root-default' group; hooking before wp_admin_bar_wp_menu() runs at
 * its default priority 10 makes it the first icon in the bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
 */
function uab_add_admin_bar_hide_toggle( $wp_admin_bar ) {
	// This plugin only replaces the bar on the logged-in frontend view (see
	// the FAQ in readme.txt); wp-admin keeps WordPress's own admin bar as-is.
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
