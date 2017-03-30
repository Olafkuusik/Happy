<?php
/*
Plugin Name:  Builder FitText
Plugin URI:   http://themify.me/addons/fittext
Version:      1.0.9
Author:       Themify
Description:  Auto fit text in the container. It requires to use with a Themify theme (framework 2.0.6+) or the Builder plugin (v 1.2.5).
Text Domain:  builder-fittext
Domain Path:  /languages
*/

defined( 'ABSPATH' ) or die( '-1' );

class Builder_Fittext {

	private static $instance = null;
	var $url;
	var $dir;
	var $version;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return	A single instance of this class.
	 */
	public static function get_instance() {
		return null == self::$instance ? self::$instance = new self : self::$instance;
	}

	private function __construct() {
		$this->constants();
		add_action( 'plugins_loaded', array( $this, 'i18n' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 15 );
		add_action( 'themify_builder_setup_modules', array( $this, 'register_module' ) );
		add_action( 'themify_builder_admin_enqueue', array( $this, 'admin_enqueue' ), 15 );
		add_action( 'init', array( $this, 'updater' ) );
	}

	public function constants() {
		$data = get_file_data( __FILE__, array( 'Version' ) );
		$this->version = $data[0];
		$this->url = trailingslashit( plugin_dir_url( __FILE__ ) );
		$this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public function i18n() {
		load_plugin_textdomain( 'builder-fittext', false, '/languages' );
	}

	public function enqueue() {
		wp_enqueue_script( 'webfontsloader', '//ajax.googleapis.com/ajax/libs/webfont/1.4.7/webfont.js', null, '1.4.7' );
		wp_enqueue_script( 'builder-fittext', $this->url . 'assets/fittext.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_style( 'builder-fittext', $this->url . 'assets/style.css', null, $this->version );
	}

	public function admin_enqueue() {
		wp_enqueue_style( 'builder-fittext-admin', $this->url . 'assets/admin.css' );
	}

	public function register_module( $ThemifyBuilder ) {
		$ThemifyBuilder->register_directory( 'templates', $this->dir . 'templates' );
		$ThemifyBuilder->register_directory( 'modules', $this->dir . 'modules' );
	}

	public function updater() {
		if( class_exists( 'Themify_Builder_Updater' ) ) {
			if ( ! function_exists( 'get_plugin_data') ) 
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			
			$plugin_basename = plugin_basename( __FILE__ );
			$plugin_data = get_plugin_data( trailingslashit( plugin_dir_path( __FILE__ ) ) . basename( $plugin_basename ) );
			new Themify_Builder_Updater( array(
				'name' => trim( dirname( $plugin_basename ), '/' ),
				'nicename' => $plugin_data['Name'],
				'update_type' => 'addon',
			), $this->version, trim( $plugin_basename, '/' ) );
		}
	}
}
Builder_Fittext::get_instance();