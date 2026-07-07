<?php
defined( 'ABSPATH' ) || die( 'No direct access to files' );

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
 */

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
