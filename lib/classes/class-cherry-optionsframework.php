<?php
/**
 *
 * @package    Cherry_Framework
 * @subpackage Class
 * @author     Cherry Team <support@cherryframework.com>
 * @copyright  Copyright (c) 2012 - 2014, Cherry Team
 * @link       http://www.cherryframework.com/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

if ( !class_exists( 'Cherry_Options_Framework' ) ) {
	class Cherry_Options_Framework {

		public $current_section_name = '';

		/**
		* Cherry_Options_Framework constructor
		* 
		* @since 1.0.0
		*/
		function __construct() {
			add_action( 'admin_init', array( $this, 'create_themename_option' ) );
		}

		/**
		 * Create themename option
		 *
		 * @since 1.0.0
		 */
		public function create_themename_option() {
			// This gets the theme name from the stylesheet (lowercase and without spaces)
			$themename = get_option( 'stylesheet' );
			$themename = preg_replace("/\W/", "_", strtolower($themename) );
			$cherry_options_settings = get_option('cherry-options');
			$cherry_options_settings['id'] = $themename;
			update_option('cherry-options', $cherry_options_settings);
		}

		/**
		 * 
		 * Save options to DB
		 *
		 * @since 1.0.0
		 */
		public function save_options($options_array) {
			$settings = get_option( 'cherry-options' );
			update_option($settings['id'], $options_array);
		}

		/**
		 * 
		 * Load options from DB
		 *
		 * @since 1.0.0
		 */
		public function load_options() {
			$settings = get_option( 'cherry-options' );
			$result_options = get_option( $settings['id'] );

			return $result_options;
		}

		/**
		 * 
		 *
		 * @since 1.0.0
		 */
		public function is_db_options_exist() {
			$cherry_options_settings = get_option( 'cherry-options' );
			(get_option($cherry_options_settings['id']) == false)? $is_options=false : $is_options = true;
			return $is_options;
		}

		/**
		 * 
		 *
		 * @since 1.0.0
		 */
		public function get_section_name_by_id($section_id) {
			$default_settings = $this->load_settings();
			$result = $default_settings[$section_id]['name'];
			return $result;
		}

		/**
		 * 
		 *
		 * @since 1.0.0
		 */
		public function get_type_by_option_id($option_id) {
			$default_settings = $this->load_settings();
			foreach ($default_settings as $sectionName => $sectionSettings) {
				foreach ($sectionSettings['options-list'] as $optionId => $optionSettings) {
					if($option_id == $optionId){
						$result = $optionSettings['type'];
					}
				}
			}
			return $result;
		}
		
		/**
		 * 
		 * Create 
		 *
		 * @since 1.0.0
		 */
		public function create_options_array() {
			$default_set = $this->load_settings();

			foreach ( $default_set as $key => $value ) {
				$setname = $key;
				$set = array();
					foreach ( $value['options-list'] as $key => $value ) {
						$set[$key] = $value['value'];
					}	
				$options_parsed_array[$setname] = array('options-list'=>$set);
			}

			return $options_parsed_array;
		}

		/**
		 * 
		 * Create and save updated options
		 *
		 * @since 1.0.0
		 */
		public function create_updated_options_array( $post_array ) {
			$options = $this->create_options_array();
			if(isset($options)){				
				foreach ( $options as $section_key => $value ) {
					$section_name = $section_key;
					$option_list = $value['options-list'];
						foreach ($option_list as $key => $value) {
							$type = $this->get_type_by_option_id($key);
							switch ($type) {
								case 'info':
									# code...
									break;
								case 'checkbox':
									if(isset($post_array[$key])){
										$options[$section_name]['options-list'][$key] = 'true';
									}else{
										$options[$section_name]['options-list'][$key] = 'false';
									}
									break;
								case 'multicheckbox':
									foreach ($value as $k => $val) {
										if (isset($post_array[$k])) {
											$value[$k] = true;
										}else{
											$value[$k] = false;
										}
									}
									$options[$section_name]['options-list'][$key] = $value;
									break;
								default:
									if (isset($post_array[$key])) {
										$options[$section_name]['options-list'][$key] = $post_array[$key];
									}
									break;
							}
						}
				}

				$this->save_options($options);
			}
		}
		
		/**
		 * 
		 * Restore section and save options
		 *
		 * @since 1.0.0
		 */
		public function restore_section_settings_array($activeSection) {
			$activeSectionName = $activeSection;
			
			$loaded_settings = $this->load_options();
			$default_settings = $this->create_options_array();

			if(isset($loaded_settings)){
				foreach ( $loaded_settings as $section_key => $value ) {
					$section_name = $section_key;
					$option_list = $value['options-list'];
					if( $section_name == $activeSectionName ){
						foreach ($option_list as $key => $value) {
							$loaded_settings[$section_name]['options-list'][$key] = $default_settings[$section_name]['options-list'][$key];
						}
					}
				}
				$this->save_options($loaded_settings);
			}
		}

		/**
		 * 
		 * Restore and save options
		 *
		 * @since 1.0.0
		 */
		public function restore_default_settings_array() {
			$options = $this->create_options_array();
				if(isset($options)){
					$this->save_options($options);
				}
		}

		/**
		 * Get default set of options
		 *
		 * @since 1.0.0
		 */
		public function load_settings() {
			$result_settings = null;

			if ( !$result_settings ) {
		        // Load options from options.php file (if it exists)
		        $location = apply_filters( 'default_set_file_location', array('cherry-options.php') );
		        if ( $optionsfile = locate_template( $location, true ) ) {
		            if ( function_exists( 'cherry_defaults_settings' ) ) {
						$result_settings = cherry_defaults_settings();
					}
		        }
			}
			
			return $result_settings;
		}
		/**
		 * Merge default set with seved options
		 *
		 * @since 1.0.0
		 */
		public function merged_settings() {
			$result_settings = null;

			$default_settings = $this->load_settings();
			$loaded_settings = $this->load_options();			

			foreach ( $default_settings as $key => $value ) {
				$section_name = $key;
				$option_list = $value['options-list'];
				
					foreach ($option_list as $optname => $value) {
						if(array_key_exists($section_name, $loaded_settings)){
							$default_settings[$section_name]['options-list'][$optname]['value'] = $loaded_settings[$section_name]['options-list'][$optname];
						}
					}
			}

			$result_settings = $default_settings;
			return $result_settings;
		}	

		/**
		 * Check for the existence of an option in the database
		 *
		 * @since 1.0.0
		 */
		public function get_settings() {
			$result_settings = array();

			if($this->is_db_options_exist()){
				//var_dump('merged_settings');
				$result_settings = $this->merged_settings();
			}else{
				//var_dump('default_settings');
				$result_settings = $this->load_settings();
			}

			return $result_settings;
		}

		/**
		 * Get option value
		 *
		 * @since 1.0.0
		 */
		static function get_option_value( $name, $default = false ) {
			$setting = get_option( 'cherry-options' );
			if ( ! isset( $setting['id'] ) ) {
				return $default;
			}
			$options_array = get_option( $setting['id'] );
			if ( isset( $options_array ) ) {
				foreach ( $options_array as $sections_name => $value ) {
					if(array_key_exists($name, $value['options-list'])){
						return $value['options-list'][$name];
					}
				}
			}
			return $default;
		}
	}
}

?>