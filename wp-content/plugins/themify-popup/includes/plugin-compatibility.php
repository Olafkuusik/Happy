<?php
/**
 * Houses codes that provide compatibility with other plugins
 */

class Themify_Popup_Plugin_Compatibility {

	function __construct() {
		add_action( 'after_setup_theme', array( $this, 'themify_builder_compat' ), 20 );
		add_filter( 'themify_builder_layout_providers', array( $this, 'add_sample_popup_layouts' ) );
	}

	/**
	 * Disable Builder frontend editor for the popup posts loaded on frontend
	 *
	 * @since 1.0.0
	 */
	function themify_builder_compat() {
		global $ThemifyBuilder;

		if( isset( $ThemifyBuilder ) ) {
			add_action( 'themify_popup_before_render', array( $this, 'themify_builder_disable_editor' ) );
			add_action( 'themify_popup_after_render', array( $this, 'themify_builder_undisable_editor' ) );
		}
	}

	function themify_builder_disable_editor() {
		$GLOBALS['ThemifyBuilder']->in_the_loop = true;
	}

	function themify_builder_undisable_editor() {
		$GLOBALS['ThemifyBuilder']->in_the_loop = false;
	}

	/**
	 * Add sample layouts bundled with Popup plugin to Themify Builder
	 *
	 * @since 1.0.0
	 */
	function add_sample_popup_layouts( $providers ) {
		include THEMIFY_POPUP_DIR . 'includes/themify-builder-popup-layout-provider.php';
		$providers[] = 'Themify_Builder_Layouts_Provider_Themify_Popup';
		return $providers;
	}
}
new Themify_Popup_Plugin_Compatibility;