<?php
/**
 * Functions for handling JavaScript in the framework. Themes can add support for the
 * 'cherry-scripts' feature to allow the framework to handle loading the scripts into
 * the theme header or footer at an appropriate time.
 *
 * @package    Cherry_Framework
 * @subpackage Functions
 * @author     Cherry Team <support@cherryframework.com>
 * @copyright  Copyright (c) 2012 - 2015, Cherry Team
 * @link       http://www.cherryframework.com/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

// Register Cherry Framework scripts.
add_action( 'wp_enqueue_scripts', 'cherry_register_scripts', 1 );

// Load Cherry Framework scripts.
add_action( 'wp_enqueue_scripts', 'cherry_enqueue_scripts', 5 );

// Add arguments for sticky menu
add_action( 'wp_enqueue_scripts', 'cherry_prepare_sticky_vars' );

/**
 * Get cherry default scripts data
 *
 * @since  4.0.0
 * @return array  cherry default scripts data
 */
function cherry_default_scripts() {

	$default_scripts = array(
		'slick' => array(
			'src'       => esc_url( trailingslashit( CHERRY_URI ) . 'assets/js/jquery.slick.min.js' ),
			'deps'      => array( 'jquery' ),
			'ver'       => CHERRY_VERSION,
			'in_footer' => true
		),
		'magnific-popup' => array(
			'src'       => esc_url( trailingslashit( CHERRY_URI ) . 'assets/js/jquery.magnific-popup.min.js' ),
			'deps'      => array( 'jquery' ),
			'ver'       => CHERRY_VERSION,
			'in_footer' => true
		)
	);

	return $default_scripts;
}
/**
 * Registers JavaScript files for the framework.  This function merely registers scripts with WordPress using
 * the wp_register_script() function.  It does not load any script files on the site.  If a theme wants to register
 * its own custom scripts, it should do so on the 'wp_enqueue_scripts' hook.
 *
 * @author Justin Tadlock <justin@justintadlock.com>
 * @author Cherry Team <support@cherryframework.com>
 * @since  4.0.0
 */
function cherry_register_scripts() {

	// Supported JavaScript.
	$supports = get_theme_support( 'cherry-scripts' );

	// Gets a defaults framework scripts.
	$default_scripts = cherry_default_scripts();

	if ( isset( $supports[0] ) && is_array( $supports[0] ) ) {

		foreach ( $default_scripts as $id => $data ) {
			if ( in_array( $id, $supports[0] ) ) {
				wp_register_script( $id, $data['src'], $data['deps'], $data['ver'], $data['in_footer'] );
			}
		}

	}

	wp_register_script(
		'cherry-script',
		esc_url( trailingslashit( CHERRY_URI ) . 'assets/js/script.js' ),
		array( 'jquery' ),
		CHERRY_VERSION,
		true
	);
}

/**
 * Tells WordPress to load the scripts needed for the framework using the wp_enqueue_script() function.
 *
 * @author Justin Tadlock <justin@justintadlock.com>
 * @author Cherry Team <support@cherryframework.com>
 * @since  4.0.0
 */
function cherry_enqueue_scripts() {

	// Supported JavaScript.
	$supports = get_theme_support( 'cherry-scripts' );

	$comments_supports = ( isset( $supports[0] ) && in_array( 'comment-reply', $supports[0] ) ) ? true : false;

	// Load the comment reply script on singular posts with open comments if threaded comments are supported.
	if ( is_singular()
		&& get_option( 'thread_comments' )
		&& comments_open()
		&& $comments_supports
		) {
		wp_enqueue_script( 'comment-reply' );
	}

	wp_enqueue_script( 'cherry-script' );

	// add custom data to cherry scripts
	$use_lightbox  = cherry_get_option( 'blog-add-ligthbox' );
	$use_lightbox = ( 'true' == $use_lightbox ) ? true : false;
	wp_localize_script( 'cherry-script', 'cherry_data', array( 'use_lightbox' => $use_lightbox ) );
}

/**
 * Prepare JS variables for sticky header script
 *
 * @since 4.0.0
 */
function cherry_prepare_sticky_vars() {
	$is_sticky = cherry_get_option( 'header-sticky', 'false' );

	$defaults = array(
		'correctionSelector' => '#wpadminbar',
		'listenSelector'     => '.listenSelector',
		'pseudo'             => true,
	);

	$options_args = array(
		'active' => ( 'true' == $is_sticky && ! wp_is_mobile() ) ? true : false
	);

	$args            = apply_filters( 'cherry_header_sticky_args', $defaults );
	$sticky_selector = cherry_get_option( 'header-sticky-selector', '.site-header' );

	$args = array_merge( $args, $options_args );

	$data = array(
		'selector' => $sticky_selector,
		'args'     => $args,
	);

	wp_localize_script( 'cherry-script', 'sticky_data', $data );
}