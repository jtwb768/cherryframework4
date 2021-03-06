<?php
/**
 * Functions and filters for handling the output of post formats.
 *
 * This file is only loaded if themes declare support for 'post-formats'. If a theme declares support for
 * 'post-formats', the content filters will not run for the individual formats that the theme
 * supports.
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

// Add support for structured post formats.
add_action( 'wp_loaded', 'cherry_structured_post_formats', 1 );

/**
 * Theme compatibility for post formats. This function adds appropriate filters for
 * the various post formats that a theme supports.
 *
 * @author Justin Tadlock <justin@justintadlock.com>
 * @author Cherry Team <support@cherryframework.com>
 * @since  4.0.0
 */
function cherry_structured_post_formats() {
	// Add infinity symbol to aside posts.
	if ( current_theme_supports( 'post-formats', 'aside' ) ) {
		add_filter( 'the_content', 'cherry_aside_infinity', 9 );
	}

	// Filter the titles of link posts.
	if ( current_theme_supports( 'post-formats', 'link' ) ) {
		add_filter( 'the_title', 'cherry_get_the_link_title', 10, 2 );
	}

	// Filter the entry-header of link posts.
	if ( current_theme_supports( 'post-formats', 'link' ) ) {
		add_filter( 'cherry_get_the_post_title_defaults', 'cherry_get_the_link_url', 10, 3 );
	}

	// Wraps <blockquote> around quote posts.
	if ( current_theme_supports( 'post-formats', 'quote' ) ) {
		add_filter( 'the_content', 'cherry_quote_content' );
	}

	// Filter the attachment markup to be prepended to the post content.
	add_filter( 'prepend_attachment', 'cherry_attachment_content', 9 );
}

/**
 * Adds an infinity character "&#8734;" to the end of the post content on 'aside' posts.
 *
 * @author Justin Tadlock <justin@justintadlock.com>
 * @author Cherry Team <support@cherryframework.com>
 * @since  4.0.0
 * @param  string $content The post content.
 * @return string $content
 */
function cherry_aside_infinity( $content ) {

	if ( has_post_format( 'aside' ) && !is_singular() && !post_password_required() ) {
		$infinity = '<a class="entry-permalink" href="' . get_permalink() . '" title="' . the_title_attribute( array( 'echo' => false ) ) . '">&#8734;</a>';
		$content .= ' ' . apply_filters( 'cherry_aside_infinity', $infinity );
	}

	return $content;
}

/**
 * This function filters the post title when viewing a post with the `link` post format.
 *
 * @since  4.0.0
 *
 * @param  string $title   The post title.
 * @param  int    $post_id
 * @return array
 */
function cherry_get_the_link_title( $title, $post_id ) {

	if ( is_admin() ) {
		return $title;
	}

	if ( ! has_post_format( 'link', $post_id ) ) {
		return $title;
	}

	return $title . '<span class="format-link-marker">&rarr;</span>';
}

/**
 * This function filters the post link when viewing a post with the `link` post format.
 *
 * @since  4.0.0
 * @param  array  $args The defaults arguments used to display a post title.
 * @param  int    $post_id
 * @param  string $post_type
 * @return array
 */
function cherry_get_the_link_url( $args, $post_id, $post_type ) {

	if ( !has_post_format( 'link' ) ) {
		return $args;
	}

	$args['url'] = apply_filters( 'cherry_link_title_url', cherry_get_post_format_url() );

	return $args;
}

/**
 * Checks if the quote post has a <blockquote> tag within the content.
 * If not, wraps the entire post content with one.
 *
 * @author Justin Tadlock <justin@justintadlock.com>
 * @author Cherry Team <support@cherryframework.com>
 * @since  4.0.0
 * @param  string $content The post content.
 * @return string $content
 */
function cherry_quote_content( $content ) {

	if ( post_password_required() ) {
		return $content;
	}

	if ( !has_post_format( 'quote' ) ) {
		return $content;
	}

	if ( !preg_match( '/<blockquote.*?>/', $content, $matches ) ) {
		$content = "<blockquote>{$content}</blockquote>";
	}

	return $content;
}

/**
 * This function filters the attachment markup to be prepended to the post content.
 *
 * @author Justin Tadlock <justin@justintadlock.com>
 * @author Cherry Team <support@cherryframework.com>
 * @since  4.0.0
 * @param  string $p The attachment HTML output.
 */
function cherry_attachment_content( $p ) {

	if ( is_attachment() ) :

		$attr    = array( 'align' => 'aligncenter', 'width' => '', 'caption' => '' );
		$post_id = get_the_ID();

		if ( wp_attachment_is_image( $post_id ) ) {

			$src = wp_get_attachment_image_src( get_the_ID(), 'full' );

			if ( is_array( $src ) && !empty( $src ) ) :

				$attr['width'] = esc_attr( $src[1] );
				$content       = wp_get_attachment_image( get_the_ID(), 'full', false, array( 'class' => 'aligncenter' ) );

			endif;

		} elseif ( cherry_attachment_is_audio( $post_id  ) || cherry_attachment_is_video( $post_id  ) ) {

			$attr['width'] = cherry_get_content_width();
			$content       = $p;

		} else {
			return $p;
		}

		if ( !has_excerpt() ) {
			return $content;
		}

		$attr['caption'] = get_the_excerpt();
		$output          = img_caption_shortcode( $attr, $content );

		return $output;

	endif;

	return $p;
}