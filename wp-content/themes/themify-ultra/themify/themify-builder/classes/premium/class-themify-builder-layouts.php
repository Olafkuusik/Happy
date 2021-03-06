<?php
/**
 * This file defines Builder Layouts and Layout Parts
 *
 * Themify_Builder_Layouts class register post type for Layouts and Layout Parts
 * Custom metabox, shortcode, and load layout / layout part.
 * 
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

/**
 * The Builder Layouts class.
 *
 * This class register post type for Layouts and Layout Parts
 * Custom metabox, shortcode, and load layout / layout part.
 *
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 * @author     Themify
 */
class Themify_Builder_Layouts {

	/**
	 * Post Type Layout Object.
	 * 
	 * @access public
	 * @var object $layout.
	 */
	public $layout;

	/**
	 * Post Type Layout Part Object.
	 * 
	 * @access public
	 * @var object $layout_part.
	 */
	public $layout_part;

	/**
	 * Store registered layout / part post types.
	 * 
	 * @access public
	 * @var array $post_types.
	 */
	public $post_types = array();

	/**
	 * Holds a list of layout provider instances
	 */
	public $provider_instances = array();

	/**
	 * Constructor
	 * 
	 * @access public
	 */
	public function __construct() {
		$this->register_layout();
		$this->register_providers();

		// Builder write panel
		add_filter( 'themify_do_metaboxes', array( $this, 'layout_write_panels' ), 11 );
		add_filter( 'themify_post_types', array( $this, 'extend_post_types' ) );
		add_action( 'add_meta_boxes_tbuilder_layout_part', array( $this, 'custom_meta_boxes' ) );

		add_action( 'wp_ajax_tfb_load_layout', array( $this, 'load_layout_ajaxify' ), 10 );
		add_action( 'wp_ajax_tfb_set_layout', array( $this, 'set_layout_ajaxify' ), 10 );
		add_action( 'wp_ajax_tfb_append_layout', array( $this, 'append_layout_ajaxify' ), 10 );
		add_action( 'wp_ajax_tfb_custom_layout_form', array( $this, 'custom_layout_form_ajaxify' ), 10 );
		add_action( 'wp_ajax_tfb_save_custom_layout', array( $this, 'save_custom_layout_ajaxify' ), 10 );

		add_filter( 'template_include', array( $this, 'template_singular_layout' ) );

		add_shortcode( 'themify_layout_part', array( $this, 'layout_part_shortcode' ) );

		// Quick Edit Links
		add_filter( 'post_row_actions', array( $this, 'row_actions' ) );
		add_filter( 'page_row_actions', array( $this, 'row_actions' ) );
		add_action( 'admin_init', array( $this, 'duplicate_action' ) );

		add_action( 'admin_init', array( $this, 'cleanup_builtin_layouts' ) );
		add_filter( 'themify_builder_post_types_support', array( $this, 'add_builder_support' ) );
	}


	/**
	 * Registers providers for layouts in Builder
	 *
	 * @since 2.0.0
	 */
	public function register_providers() {
		$providers = apply_filters( 'themify_builder_layout_providers', array(
			'Themify_Builder_Layouts_Provider_Pre_Designed',
			'Themify_Builder_Layouts_Provider_Theme',
			'Themify_Builder_Layouts_Provider_Custom',
		) );
		foreach( $providers as $provider ) {
			if( class_exists( $provider ) ) {
				$instance = new $provider();
				$this->provider_instances[ $instance->get_id() ] = $instance;
			}
		}
	}

	/**
	 * Get a single layout provider instance
	 *
	 * @since 2.0.0
	 */
	public function get_provider( $id ) {
		if( isset( $this->provider_instances[ $id ] ) ) {
			return $this->provider_instances[ $id ];
		}

		return false;
	}

	/**
	 * Register Layout and Layout Part Custom Post Type
	 * 
	 * @access public
	 */
	public function register_layout() {
		if ( ! class_exists( 'CPT' ) ) {
			include_once THEMIFY_BUILDER_LIBRARIES_DIR . '/' . 'CPT.php';
		}

		// create a template custom post type
		$this->layout = new CPT( array(
			'post_type_name' => 'tbuilder_layout',
			'singular' => __('Layout', 'themify'),
			'plural' => __('Layouts', 'themify')
		), array(
			'supports' => array('title', 'thumbnail'),
			'exclude_from_search' => true,
			'show_in_nav_menus' => false,
			'show_in_menu' => false,
			'public' => true
		));

		// define the columns to appear on the admin edit screen
		$this->layout->columns(array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Title', 'themify'),
			'thumbnail' => __('Thumbnail', 'themify'),
			'author' => __('Author', 'themify'),
			'date' => __('Date', 'themify')
		));

		// populate the thumbnail column
		$this->layout->populate_column('thumbnail', array( $this, 'populate_column_layout_thumbnail' ) );

		// use "pages" icon for post type
		$this->layout->menu_icon('dashicons-admin-page');

		// create a template custom post type
		$this->layout_part = new CPT( array(
			'post_type_name' => 'tbuilder_layout_part',
			'singular' => __('Layout Part', 'themify'),
			'plural' => __('Layout Parts', 'themify'),
			'slug' => 'tbuilder-layout-part'
		), array(
			'supports' => array('title', 'thumbnail'),
			'exclude_from_search' => true,
			'show_in_nav_menus' => false,
			'show_in_menu' => false,
			'public' => true
		));

		// define the columns to appear on the admin edit screen
		$this->layout_part->columns(array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Title', 'themify'),
			'shortcode' => __('Shortcode', 'themify'),
			'author' => __('Author', 'themify'),
			'date' => __('Date', 'themify')
		));

		// populate the thumbnail column
		$this->layout_part->populate_column('shortcode', array( $this, 'populate_column_layout_part_shortcode' ) );

		// use "pages" icon for post type
		$this->layout_part->menu_icon('dashicons-screenoptions');

		$this->set_post_type_var( $this->layout->post_type_name );
		$this->set_post_type_var( $this->layout_part->post_type_name );

		add_post_type_support( $this->layout->post_type_name, 'revisions' );
		add_post_type_support( $this->layout_part->post_type_name, 'revisions' );
	}

	/**
	 * Set the post type variable.
	 * 
	 * @access public
	 * @param string $name 
	 */
	public function set_post_type_var( $name ) {
                $this->post_types[] = $name;
	}

	/**
	 * Custom column thumbnail.
	 * 
	 * @access public
	 * @param array $column 
	 * @param object $post 
	 */
	public function populate_column_layout_thumbnail( $column, $post ) {
		echo get_the_post_thumbnail( $post->ID, 'thumbnail');
	}

	/**
	 * Custom column for shortcode.
	 * 
	 * @access public
	 * @param array $column 
	 * @param object $post 
	 */
	public function populate_column_layout_part_shortcode( $column, $post ) {
		echo sprintf( '[themify_layout_part id=%d]', $post->ID );
		echo '<br/>';
		echo sprintf( '[themify_layout_part slug=%s]', $post->post_name );
	}

	/**
	 * Metabox Panel
	 *
	 * @access public
	 * @param $meta_boxes
	 * @return array
	 */
	public function layout_write_panels( $meta_boxes ) {
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
			return $meta_boxes;
		}

		$meta_settings = array(
			array(
				'name' 		=> 'post_image',
				'title' 	=> __('Layout Thumbnail', 'themify'),
				'description' => '',
				'type' 		=> 'image',
				'meta'		=> array()
			)
		);
			
		$all_meta_boxes = array();
		$all_meta_boxes[] = apply_filters( 'layout_write_panels_meta_boxes', array(
			'name'		=> __( 'Settings', 'themify' ),
			'id' 		=> 'layout-settings-builder',
			'options'	=> $meta_settings,
			'pages'    	=> $this->layout->post_type_name
		) );
		return array_merge( $meta_boxes, $all_meta_boxes);
	}

	/**
	 * Includes this custom post to array of cpts managed by Themify
	 * 
	 * @access public
	 * @param Array $types
	 * @return Array
	 */
	public function extend_post_types( $types ) {
		$cpts = array( $this->layout->post_type_name, $this->layout_part->post_type_name );
		return array_merge( $types, $cpts );
	}

	/**
	 * Add meta boxes to layout and/or layout part screens.
	 *
	 * @access public
	 * @param object $post
	 */
	public function custom_meta_boxes( $post ) {
		add_meta_box( 'layout-part-info', __( 'Using this Layout Part', 'themify' ), array( $this, 'layout_part_info' ), $this->layout_part->post_type_name, 'side', 'default' );
	}

	/**
	 * Displays information about this layout part.
	 * 
	 * @access public
	 */
	public function layout_part_info() {
		$layout_part = get_post();
		echo '<div>' . __( 'To display this Layout Part, insert this shortcode:', 'themify' ) . '<br/>
		<input type="text" readonly="readonly" class="widefat" onclick="this.select()" value="' . esc_attr( '[themify_layout_part id="' . $layout_part->ID . '"]' ) . '" />';
		if ( ! empty( $layout_part->post_name ) ) {
			echo '<input type="text" readonly="readonly" class="widefat" onclick="this.select()" value="' . esc_attr( '[themify_layout_part slug="' . $layout_part->post_name . '"]' ) . '" />';
		}
		echo '</div>';
	}

	/**
	 * Load list of available Templates
	 * 
	 * @access public
	 */
	public function load_layout_ajaxify() {
		global $post;

		check_ajax_referer( 'tfb_load_nonce', 'nonce' );

		include_once THEMIFY_BUILDER_INCLUDES_DIR . '/themify-builder-layout-lists.php';
		die();
	}

	/**
	 * Custom layout for Template / Template Part Builder Editor.
	 * 
	 * @access public
	 */
	public function template_singular_layout( $original_template ) {
		if ( is_singular( array( $this->layout->post_type_name, $this->layout_part->post_type_name ) ) ) {
			$templatefilename = 'template-builder-editor.php';
			
			$return_template = locate_template(
				array(
					trailingslashit( 'themify-builder/templates' ) . $templatefilename
				)
			);

			// Get default template
			if ( ! $return_template )
				$return_template = THEMIFY_BUILDER_TEMPLATES_DIR . '/' . $templatefilename;

			return $return_template;
		} else {
			return $original_template;
		}
	}

	/**
	 * Set template to current active builder.
	 * 
	 * @access public
	 */
	public function set_layout_ajaxify() {
		global $ThemifyBuilder;
		check_ajax_referer( 'tfb_load_nonce', 'nonce' );
		$template_slug = $_POST['layout_slug'];
		$current_builder_id = (int) $_POST['current_builder_id'];
		$layout_group = $_POST['layout_group'];
		$builder_data = '';
		$response = array();

		if( isset( $this->provider_instances[ $layout_group ] ) ) {
			$builder_data = $this->provider_instances[ $layout_group ]->get_builder_data( $template_slug );
		}

		if ( ! is_wp_error( $builder_data ) && ! empty( $builder_data ) ) {
			$GLOBALS['ThemifyBuilder_Data_Manager']->save_data( $builder_data, $current_builder_id, false );
			$response['status'] = 'success';
			$response['msg'] = '';
			$response['builder_data'] = $builder_data;
		} else {
			$response['status'] = 'failed';
			$response['msg'] = $builder_data->get_error_message();
		}

		do_action( 'themify_builder_layout_loaded', compact( 'template_slug', 'current_builder_id', 'layout_group', 'builder_data' ) );

		wp_send_json( $response );
		die();
	}

	/**
	 * Append template to current active builder.
	 * 
	 * @access public
	 */
	public function append_layout_ajaxify() {
		global $ThemifyBuilder;
		check_ajax_referer( 'tfb_load_nonce', 'nonce' );
		$template_slug = $_POST['layout_slug'];
		$current_builder_id = (int) $_POST['current_builder_id'];
		$layout_group = $_POST['layout_group'];
		$builder_data = '';
		$response = array();

		if( isset( $this->provider_instances[ $layout_group ] ) ) {
			$builder_data = $this->provider_instances[ $layout_group ]->get_builder_data( $template_slug );
		}

		if ( ! is_wp_error( $builder_data ) && ! empty( $builder_data ) ) {
			$oldPostData = $GLOBALS['ThemifyBuilder_Data_Manager']->get_data( $current_builder_id );
			$newBuilderData = $oldPostData;
			$count = count( $newBuilderData );
			foreach ($builder_data as $data ) {
				$data['row_order'] = $count;
				$newBuilderData[] = $data;
				$count++;
			}
			$builder_data = json_encode( $newBuilderData );
			$GLOBALS['ThemifyBuilder_Data_Manager']->save_data( $builder_data, $current_builder_id, false );
			$response['status'] = 'success';
			$response['msg'] = '';
			$response['builder_data'] = $newBuilderData;
		} else {
			$response['status'] = 'failed';
			$response['msg'] = $builder_data->get_error_message();
		}

		do_action( 'themify_builder_layout_appended', compact( 'template_slug', 'current_builder_id', 'layout_group', 'builder_data' ) );

		wp_send_json( $response );
		die();
	}

	/**
	 * Layout Part Shortcode
	 * 
	 * @access public
	 * @param array $atts 
	 * @return string
	 */
	public function layout_part_shortcode( $atts ) {
		global $ThemifyBuilder;
		extract( shortcode_atts( array(
			'id' => '',
			'slug' => ''
		), $atts ));

		$args = array(
			'post_type' => $this->layout_part->post_type_name,
			'post_status' => 'publish',
			'numberposts' => 1
		);
		if ( ! empty( $slug ) ) $args['name'] = $slug;
		if ( ! empty( $id ) ) $args['p'] = $id;
		$template = get_posts( $args );
		$output = '';

		if ( $template ) {
			$builder_data = $ThemifyBuilder->get_builder_data( $template[0]->ID );

			if ( ! empty( $builder_data ) ) {
				$output = $ThemifyBuilder->retrieve_template( 'builder-layout-part-output.php', array( 'builder_output' => $builder_data, 'builder_id' => $template[0]->ID ), '', '', false );
				$output = $ThemifyBuilder->get_builder_stylesheet( $output ) . $output;
			}
		}

		return $output;
	}

	/**
	 * Render Layout Form in lightbox
	 * 
	 * @access public
	 */
	public function custom_layout_form_ajaxify() {
		check_ajax_referer( 'tfb_load_nonce', 'nonce' );
		$postid = (int) $_POST['postid'];

		$fields = array(
			array(
				'id' => 'layout_img_field',
				'type' => 'image',
				'label' => __('Image Preview', 'themify'),
				'class' => 'xlarge'
			),
			array(
				'id' => 'layout_title_field',
				'type' => 'text',
				'label' => __('Title', 'themify')
			)
		);
		
		include_once THEMIFY_BUILDER_INCLUDES_DIR . '/themify-builder-save-layout-form.php';
		die();
	}

	/**
	 * Save as Layout
	 * 
	 * @access public
	 */
	public function save_custom_layout_ajaxify() {
		check_ajax_referer( 'tfb_load_nonce', 'nonce' );
		global $ThemifyBuilder;
		$data = array();
		$response = array(
			'status' => 'failed',
			'msg' => __('Something went wrong', 'themify')
		);

		if ( isset( $_POST['form_data'] ) )
			parse_str( $_POST['form_data'], $data );

		if ( isset( $data['postid'] ) && ! empty( $data['postid'] ) ) {
			$template = get_post( $data['postid'] );
			$title = isset( $data['layout_title_field'] ) && ! empty( $data['layout_title_field'] ) ? sanitize_text_field( $data['layout_title_field'] ) : $template->post_title . ' Layout';
			$builder_data = $ThemifyBuilder->get_builder_data( $template->ID );
			if ( ! empty( $builder_data ) ) {
				$new_id = wp_insert_post(array(
					'post_status' => 'publish',
					'post_type' => $this->layout->post_type_name,
					'post_author' => $template->post_author,
					'post_title' => $title,
				));

				$GLOBALS['ThemifyBuilder_Data_Manager']->save_data( $builder_data, $new_id );

				// Set image as Featured Image
				if ( isset( $data['layout_img_field_attach_id'] ) && ! empty( $data['layout_img_field_attach_id'] ) )
					set_post_thumbnail( $new_id, $data['layout_img_field_attach_id'] );

				$response['status'] = 'success';
				$response['msg'] = '';
			}
		}

		wp_send_json( $response );
	}

	/**
	 * Add custom link actions in post / page rows
	 * 
	 * @access public
	 * @param array $actions 
	 * @return array
	 */
	public function row_actions( $actions ) {
		global $post;

		if ( ( $this->layout->post_type_name == get_post_type() ) || ( $this->layout_part->post_type_name == get_post_type() ) ) {
			$actions['themify-builder-duplicate'] = sprintf( '<a href="%s">%s</a>', wp_nonce_url( admin_url( 'post.php?post=' . $post->ID . '&action=duplicate_tbuilder' ), 'duplicate_themify_builder' ), __('Duplicate', 'themify') );
		}

		$registered_post_types = themify_post_types();
		if ( current_user_can( 'edit_post', get_the_id() ) && in_array( get_post_type(), $registered_post_types ) ) {
			$builder_link = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( get_permalink( $post->ID ) . '#builder_active' ), __('Themify Builder', 'themify' ));
			$actions['themify-builder'] = $builder_link;
		}

		return $actions;
	}

	/**
	 * Duplicate Post in Admin Edit page.
	 * 
	 * @access public
	 */
	public function duplicate_action() {
		if ( isset( $_GET['action'] ) && 'duplicate_tbuilder' == $_GET['action'] && wp_verify_nonce($_GET['_wpnonce'], 'duplicate_themify_builder') ) {
			global $themifyBuilderDuplicate;
			$postid = (int) $_GET['post'];
			$layout = get_post( $postid );

			$new_id = $themifyBuilderDuplicate->duplicate( $layout );
			delete_post_meta( $new_id, '_themify_builder_prebuilt_layout' );

			wp_redirect( admin_url( 'edit.php?post_type=' . get_post_type( $postid ) ) );
			exit;
		}
	}

	/**
	 * Get layouts cache dir.
	 * 
	 * @access public
	 * @return array
	 */
	static public function get_cache_dir() {
		$upload_dir = wp_upload_dir();

		$dir_info = array(
			'path'   => $upload_dir['basedir'] . '/themify-builder/',
			'url'    => $upload_dir['baseurl'] . '/themify-builder/'
		);

		if( ! file_exists( $dir_info['path'] ) ) {
			wp_mkdir_p( $dir_info['path'] );
		}

		return $dir_info;
	}

	/**
	 * Add Builder support to Layout and Layout Part post types.
	 * 
	 * @access public
	 * @since 2.4.8
	 */
	public function add_builder_support( $post_types ) {
		$post_types['tbuilder_layout'] = 'tbuilder_layout';
		$post_types['tbuilder_layout_part'] = 'tbuilder_layout_part';

		return $post_types;
	}

	/**
	 * Runs once and removes the builtin layout posts as no longer needed
	 *
	 * @access public
	 * @since 1.5.1
	 */
	public function cleanup_builtin_layouts() {
		global $post;
		if( get_option( 'themify_builder_cleanup_builtin_layouts' ) == 'yes' )
			return;

		$posts = new WP_Query( array(
			'post_type' => $this->layout->post_type_name,
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'meta_key' => '_themify_builder_prebuilt_layout',
			'meta_value' => 'yes'
		));
		if( $posts->have_posts() ) : while( $posts->have_posts() ) : $posts->the_post();
			wp_delete_post( $post->ID, true );
		endwhile; endif;
		wp_reset_postdata();

		update_option( 'themify_builder_cleanup_builtin_layouts', 'yes' );
	}
}

/**
 * Base class for Builder layout provider
 *
 * Different types of layouts that can be imported in Builder must each extend this base class
 *
 * @since 2.0.0
 */
class Themify_Builder_Layouts_Provider {

	/**
	 * Get the ID of provider
	 *
	 * @return string
	 */
	public function get_id() {}

	/**
	 * Get the label of provider
	 *
	 * @return string
	 */
	public function get_label() {}

	/**
	 * Get a list of available layouts provided by this class
	 *
	 * @return array
	 */
	public function get_layouts() {
		return array();
	}

	/**
	 * Check if the layout provider has any layouts available
	 *
	 * @return bool
	 */
	public function has_layouts() {
		$layouts = $this->get_layouts();
		return ! empty( $layouts );
	}

	/**
	 * Returns Builder data for a given layout $slug, or a WP_Error instance should that fail
	 *
	 * @return array|WP_Error
	 */
	public function get_builder_data( $slug ) {
		return array();
	}

	/**
	 * Create the tab interface in Load Layouts screen
	 *
	 * @return string
	 */
	public function get_list_output() {
		$layouts = $this->get_layouts();
		if( ! empty( $layouts ) ) : ?>
			<div id="themify_builder_tabs_<?php echo $this->get_id(); ?>" class="themify_builder_tab">
				<ul class="themify_builder_layout_lists">

					<?php foreach( $layouts as $layout ) : ?>
					<li class="layout_preview_list">
						<div class="layout_preview" data-slug="<?php echo esc_attr( $layout['slug'] ); ?>" data-group="<?php echo $this->get_id(); ?>">
							<div class="thumbnail"><?php echo $layout['thumbnail']; ?></div><!-- /thumbnail -->
							<div class="layout_action">
								<div class="layout_title"><?php echo $layout['title']; ?></div><!-- /template_title -->
							</div><!-- /template_action -->
						</div><!-- /template_preview -->
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php
		endif;
	}

	/**
	 * Gets a path to a layouts list file, returns the list
	 *
	 * @return array
	 */
	public function get_layouts_from_file( $path ) {
		$layouts = array();
		if( is_file( $path ) ) {
			foreach( include( $path ) as $layout ) {
				$group = isset( $layout['group'] ) ? $layout['group'] : $this->get_id();
				$layouts[] = array(
					'title' => $layout['title'],
					'slug' => $layout['data'],
					'thumbnail' => sprintf( '<img src="%s">', $layout['thumb'] ),
				);
			}
		}

		return $layouts;
	}

	/**
	 * Get the Builder data from an exported file
	 * Automatically unzips the file if it's compressed
	 *
	 * @return array|WP_Error
	 */
	function get_builder_data_from_file( $file ) {
		if( is_file( $file ) ) {
			$cache_dir = themify_get_cache_dir();
			$extract_file = $cache_dir['path'] . basename( $slug );
			WP_Filesystem();
			/* extract the file */
			$extract_action = unzip_file( $file, $extract_file );
			if( is_wp_error( $extract_action ) ) {
				return $extract_action;
			} else {
				$extract_file = $cache_dir['path'] . basename( $slug ) . '/builder_data_export.txt';
				/* use include to read the file, seems safer than wp_filesystem */
				ob_start();
				include $extract_file;
				$builder_data = ob_get_clean();
				$builder_data = json_decode( $builder_data, true );
				return $builder_data;
			}
		} else {
			return new WP_Error( 'fail', __( 'Layout does not exist.', 'themify' ) );
		}
	}
}

/**
 * "Custom" layout provider, adds the posts from "tbuilder_layout" post type as layouts
 *
 * @since 2.0.0
 */
class Themify_Builder_Layouts_Provider_Custom extends Themify_Builder_Layouts_Provider {

	public function get_id() {
		return 'custom';
	}

	public function get_label() {
		return __( 'Custom', 'themify' );
	}

	/**
	 * Get a list of "custom" layouts, each post from the "tbuilder_layout" post type
	 * is a Custom layout, this returns a list of them all
	 *
	 * @return array
	 */
	public function get_layouts() {
		global $post;

		$posts = new WP_Query( array(
			'post_type' => 'tbuilder_layout',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
		));
		$layouts = array();
		if( $posts->have_posts() ) : while( $posts->have_posts() ) : $posts->the_post();
			$layouts[] = array(
				'title' => get_the_title(),
				'slug' => $post->post_name,
				'thumbnail' => has_post_thumbnail() ? get_the_post_thumbnail(null, 'thumbnail', array( 150, 150 ) ) : sprintf( '<img src="%s">', 'http://placehold.it/150x150' ),
			);
		endwhile; endif;
		wp_reset_postdata();

		return $layouts;
	}

	public function get_builder_data( $slug ) {
		global $ThemifyBuilder;
		$args = array(
			'name' => $slug,
			'post_type' => 'tbuilder_layout',
			'post_status' => 'publish',
			'numberposts' => 1
		);
		$template = get_posts( $args );
		if ( $template ) {
			return $ThemifyBuilder->get_builder_data( $template[0]->ID );
		} else {
			return new WP_Error( 'fail', __('Requested layout not found.', 'themify') );
		}
	}
}

/**
 * Pre-designed layouts in Builder
 *
 * To see a list of pre-designed layouts go to https://themify.me/demo/themes/builder-layouts/
 * The list of these layouts is loaded in themify-builder-app.js
 *
 * @since 2.0.0
 */
class Themify_Builder_Layouts_Provider_Pre_Designed extends Themify_Builder_Layouts_Provider {

	public function get_id() {
		return 'pre-designed';
	}

	public function get_label() {
		return __( 'Pre-designed', 'themify' );
	}

	/**
	 * Check if the provider has any layouts
	 *
	 * The pre-designed layouts are always available!
	 *
	 * @return true
	 */
	public function has_layouts() {
		return true;
	}

	public function get_list_output() {
		?>
		<div id="themify_builder_tabs_pre-designed" class="themify_builder_tab">
			<input type="text" placeholder="<?php _e( 'Search', 'themify' ); ?>" id="themify_builder_layout_search" />
			<ul id="themify_builder_pre-designed-filter" style="display: none;">
				<li><a href="#" class="all"><?php _e( 'All', 'themify' ); ?></a></li>
			</ul>
			<div id="themify_builder_load_layout_error" style="display: none;">
				<?php _e( 'There was an error in load layouts, please make sure your internet is connected and check if Themify site is available.', 'themify' ); ?>
			</div>
		</div>
		<script type="text/html" id="tmpl-themify-builder-layout-item">
		<ul class="themify_builder_layout_lists">
			<# jQuery.each( data, function( i, e ) { #>
			<li class="layout_preview_list" data-category="{{{e.category}}}">
				<div class="layout_preview" data-id="{{{e.id}}}" data-slug="{{{e.slug}}}" data-group="pre-designed">
					<div class="thumbnail"><img src="{{{e.thumbnail}}}" /></div>
					<div class="layout_action">
						<div class="layout_title">{{{e.title}}}</div>
						<a class="layout-preview-link themify_lightbox" href="{{{e.url}}}" target="_blank" title="<?php _e( 'Preview', 'themify' ); ?>"><i class="ti-search"></i></a>
					</div><!-- /template_action -->
				</div><!-- /template_preview -->
			</li>
			<# } ) #>
		</ul>
		</script>
		<?php
	}

	/**
	 * Get the Builder data for a particular layout
	 *
	 * The builder data is sent via JavaScript (themify-builder-app.js)
	 *
	 * @return array|WP_Error
	 */
	public function get_builder_data( $slug ) {
		if( isset( $_POST['builder_data'] ) ) {
			return json_decode( stripslashes_deep( $_POST['builder_data'] ), true );
		} else {
			return new WP_Error( 'fail', __( 'Failed to get Builder data.', 'themify' ) );
		}
	}
}

/**
 * Adds Builder layouts bundled with themes
 *
 * These layouts should be placed in /builder-layouts directory inside the theme's root folder
 *
 * @since 2.0.0
 */
class Themify_Builder_Layouts_Provider_Theme extends Themify_Builder_Layouts_Provider {

	public function get_id() {
		return 'theme';
	}

	public function get_label() {
		return __( 'Theme', 'themify' );
	}

	/**
	 * Get a list of layouts from /builder-layouts/layouts.php file inside the theme
	 *
	 * @return array
	 */
	public function get_layouts() {
		return $this->get_layouts_from_file( get_template_directory() . '/builder-layouts/layouts.php' );
	}

	/**
	 * Get the Builder data from a file in /builder-layouts directory in the theme
	 *
	 * @return array|WP_Error
	 */
	public function get_builder_data( $slug ) {
		$file = get_template_directory() . '/builder-layouts/' . $slug;
		return $this->get_builder_data_from_file( $file );
	}
}