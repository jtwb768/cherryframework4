<?php
/**
 * Cherry enqueue Google fonts class.
 *
 * @package    Cherry_Framework
 * @subpackage Class
 * @author     Cherry Team <support@cherryframework.com>
 * @copyright  Copyright (c) 2012 - 2015, Cherry Team
 * @link       http://themehybrid.com/plugins/breadcrumb-trail, http://www.cherryframework.com/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

/**
 * Cherry enqueue Google fonts class.
 * @since  4.0.0
 */
class cherry_enqueue_fonts {

	/**
	 * A reference to an instance of this class.
	 */
	private static $instance = null;

	/**
	 * Array of typography options names
	 * @var array
	 */
	public $typography_options_set = array();

	/**
	 * Array of stored google fonts data
	 * @var array
	 */
	public $fonts_data = array();

	/**
	 * JSON string with google fonts data parsed from font file
	 * @var null
	 */
	public static $google_fonts = null;

	/**
	 * Define fonts server URL
	 * @var string
	 */
	public static $fonts_host = '//fonts.googleapis.com/css';

	function __construct() {

		add_action( 'cherry-options-updated', array( $this, 'reset_fonts_cache' ) );
		add_action( 'cherry-section-restored', array( $this, 'reset_fonts_cache' ) );
		add_action( 'cherry-options-restored', array( $this, 'reset_fonts_cache' ) );

		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'prepare_fonts' ) );

	}

	/**
	 * Get fonts data and enqueue URL
	 *
	 * @since 4.0.0
	 */
	function prepare_fonts() {

		$font_url = get_transient( 'cherry_google_fonts_url' );

		if ( ! $font_url ) {

			// Get typography options list
			$this->get_options_set();

			// build Google fonts data array
			foreach ( $this->typography_options_set  as $option ) {
				$this->add_font( $option );
			}

			$font_url = $this->build_fonts_url();

			set_transient( 'cherry_google_fonts_url', $font_url, WEEK_IN_SECONDS );
		}

		wp_enqueue_style( 'cherry-google-fonts', $font_url );
	}

	/**
	 * Build Google fonts stylesheet URL from stored data
	 *
	 * @since  4.0.0
	 */
	function build_fonts_url() {

		$font_families = array();
		$subsets       = array();

		foreach ( $this->fonts_data as $family => $data ) {
			$styles = implode( ',', array_unique( $data['style'] ) );
			$font_families[] = $family . ':' . $styles;
			$subsets = array_merge( $subsets, $data['character'] );
		}

		$subsets = array_unique( $subsets );

		$query_args = array(
			'family' => urlencode( implode( '|', $font_families ) ),
			'subset' => urlencode( implode( ',', $subsets ) ),
		);

		$fonts_url = add_query_arg( $query_args, self::$fonts_host );

		return $fonts_url;
	}

	/**
	 * Get single typography option value from database and store it in object property
	 *
	 * @since  4.0.0
	 *
	 * @param  string  $option  option name to get from database
	 */
	function add_font( $option ) {

		$option_val = cherry_get_option( $option, false );

		if ( ! $option_val || ! is_array( $option_val ) ) {
			return;
		}

		if ( ! self::is_google_font( $option_val['family'] ) ) {
			return;
		}

		$font = $option_val['family'];

		if ( ! isset( $this->fonts_data[$font] ) ) {
			$this->fonts_data[$font] = array(
				'style'     => array( $option_val['style'] ),
				'character' => array( $option_val['character'] )
			);
		} else {
			$this->fonts_data[$font] = array(
				'style'     => array_merge( $this->fonts_data[$font]['style'], array( $option_val['style'] ) ),
				'character' => array_merge( $this->fonts_data[$font]['character'], array( $option_val['character'] ) )
			);
		}
	}

	/**
	 * Check if selected font is google font
	 *
	 * @since  4.0.0
	 *
	 * @param  string  $family  font family name to chack
	 * @return boolean
	 */
	public static function is_google_font( $family ) {

		if ( null == self::$google_fonts ) {
			$fonts_path = trailingslashit( CHERRY_ADMIN ) . 'assets/fonts/google-fonts.json';
			self::$google_fonts = file_get_contents( $fonts_path );
		}

		if ( false === strpos( self::$google_fonts, $family ) ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Get options set from full options array
	 *
	 * @since  4.0.0
	 */
	function get_options_set() {

		if ( ! class_exists( 'Cherry_Options_Framework' ) ) {
			return;
		}

		$default_options_data = Cherry_Options_Framework::load_settings();

		array_walk( $default_options_data, array( $this, 'walk_sections' ) );
	}

	/**
	 * Walk through sections array
	 *
	 * @since  4.0.0
	 *
	 * @param  array  $item section data
	 * @param  string $key  section key
	 */
	function walk_sections( $item, $key ) {

		if ( is_array( $item ) && ! empty( $item['options-list'] ) ) {
			array_walk( $item['options-list'], array( $this, 'catch_option' ) );
		}

	}

	/**
	 * Catcn single typography while walking through options array
	 *
	 * @since  4.0.0
	 */
	function catch_option( $item, $key ) {

		if ( ! is_array( $item ) || ! array_key_exists( 'type', $item ) ) {
			return;
		}

		if ( 'typography' == $item['type'] ) {
			$this->typography_options_set[] = $key;
		}

	}

	/**
	 * Get single font URL by font data
	 *
	 * @since  4.0.0
	 */
	public static function get_single_font_url( $font_data ) {

		$font_data = wp_parse_args( $font_data, array(
			'family'    => '',
			'style'     => '',
			'character' => ''
		) );

		if ( ! self::is_google_font( $font_data['family'] ) ) {
			return;
		}

		$font_family = $font_data['family'] . ':' . $font_data['style'];
		$subsets     = $font_data['character'];

		$query_args = array(
			'family' => urlencode( $font_family ),
			'subset' => urlencode( $subsets )
		);

		$fonts_url = add_query_arg( $query_args, self::$fonts_host );

		return $fonts_url;

	}

	/**
	 * Reset fonts cache
	 *
	 * @since 4.0.0
	 */
	function reset_fonts_cache() {
		delete_transient( 'cherry_google_fonts_url' );
	}

	/**
	 * Returns the instance.
	 *
	 * @since  4.0.0
	 * @return object
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

}

add_action( 'after_setup_theme', array( 'cherry_enqueue_fonts', 'get_instance' ), 40 );