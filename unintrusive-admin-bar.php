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

	$css_path = plugin_dir_path( __FILE__ ) . 'css/style.css';
	$js_path  = plugin_dir_path( __FILE__ ) . 'js/app.js';

	wp_enqueue_style( 'uab-unintrusive-admin-bar-css', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), filemtime( $css_path ) );
	wp_enqueue_script( 'uab-unintrusive-admin-bar-js', plugin_dir_url( __FILE__ ) . 'js/app.js', array( 'jquery' ), filemtime( $js_path ), true );

	wp_localize_script(
		'uab-unintrusive-admin-bar-js',
		'uabL10n',
		array(
			'hideLabel' => __( 'Hide WP Admin Bar', 'unintrusive-admin-bar' ),
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
 * Add the WP Admin Bar toggle
 */
function uab_add_admin_bar_toggle() {
	$label = __( 'Show WP Admin Bar', 'unintrusive-admin-bar' );
	echo '<a href="#" id="uab-btn-show-admin-bar" title="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '"></a>';
}
add_action( 'wp_after_admin_bar_render', 'uab_add_admin_bar_toggle' );
