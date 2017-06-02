<?php

namespace PosttypeTermArchive\Admin;
use PosttypeTermArchive\Core;


class Admin extends Core\Singleton {

	/**
	 * Nonce Value
	 * @const \Post_Type_Archive_Links::NONCE
	 */
	const NONCE = 'post-type-terms-nonce';

	/**
	 * ID of the custom metabox
	 * @const \Post_Type_Archive_Links::METABOXID
	 */
	const METABOXID = 'post-type-terms-metabox';

	/**
	 * ID of the custom post type list items
	 * @const \Post_Type_Archive_Links::METABOXLISTID
	 */
	const METABOXLISTID = 'post-type-terms-checklist';


	private $core;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'plugins_loaded', array( $this , 'plugins_loaded' ) );
	}

	public function plugins_loaded() {
		
		$this->enable();
		
	}

	private function enable() {
		
		add_action( 'admin_init', array( $this, 'get_cpts' ) );
		
		add_action( 'admin_init', array( $this, 'add_meta_box' ), 20 );

		add_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );
		
		add_action( "wp_ajax_" . self::NONCE, array( $this, 'ajax_add_post_type' ) );
		
		add_filter( 'customize_nav_menu_available_items', array( $this, 'customize_nav_menu_available_items' ), 10, 4 );

		add_filter( 'customize_nav_menu_available_item_types', array( $this, 'customize_nav_menu_available_item_types' ) );

	}

	private function disable() {
		
		remove_action( 'admin_init', array( $this, 'get_cpts' ) );
		
		remove_action( 'admin_init', array( $this, 'add_meta_box' ), 20 );

		remove_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );
		
		remove_action( "wp_ajax_" . self::NONCE, array( $this, 'ajax_add_post_type' ) );

		remove_filter( 'customize_nav_menu_available_items', array( $this, 'customize_nav_menu_available_items' ), 10 );
	}

	/**
	 * Admin init
	 */
	public function admin_init() {
	}


	/**
	 * Get CPTs that plugin should handle: having true
	 * 'has_archive', 'publicly_queryable' and 'show_in_nav_menu'
	 * @return void
	 * @action admin_init
	 */
	public function get_cpts() {

		$this->archives = Core\Archive::get_archives();
	}

	/**
	 * Adds the meta box to the menu page
	 * @return void
	 * @action admin_init
	 */
	public function add_meta_box() {
		
		add_meta_box(
			self::METABOXID,
			__( 'Post Type Term Archives', 'posttype-term-archive'),
			array( $this, 'metabox' ),
			'nav-menus',
			'side',
			'low'
		);
	}
	
	/**
	 *	@filter customize_nav_menu_available_item_types
	 */
	public function customize_nav_menu_available_item_types( $item_types ) {
		if ( empty( $this->archives ) ) {
			return $item_types;
		}
		foreach ( $this->archives as $archive ) {
			$post_type = $archive['post_type'];

			foreach ( $archive['taxonomies'] as $tax => $tax_obj ) {
				$item_types[] = array(
					'title'			=> sprintf( '%s – %s', 
											$post_type->labels->singular_name,
											$tax_obj->labels->name 
										),
					'type_label'	=> __( 'Post Type Term Archive', 'posttype-term-archive'),
					'type'			=> 'post_type_term_archive',
					'object'		=> $post_type->name . Core\Core::SEPARATOR . $tax,
				);
			}
		}
		return $item_types;
	}

	/**
	 *	@filter customize_nav_menu_available_items
	 */
	public function customize_nav_menu_available_items( $items, $type, $object, $page ) {

		@list( $post_type_name, $taxonomy ) = explode( Core\Core::SEPARATOR, $object );

		if ( $type !== 'post_type_term_archive' && empty( $this->archives ) ) {
			return $items;
		}
		foreach ( $this->archives as $archive ) {
			$post_type = $archive['post_type'];

			if ( $post_type_name !== $post_type->name ) {
				continue;
			}

			foreach ( $archive['taxonomies'] as $tax => $tax_obj ) {
				if ( $tax !== $taxonomy ) {
					continue;
				}
				$key = 'post_type_term_archive:'.$post_type->name . Core\Core::SEPARATOR . $tax;
				$terms = get_terms( array(
					'hide_empty'	=> false,
					'taxonomy'		=> $tax,
				) );
/*
				'menu-item-title'  => esc_attr( $post_type_obj->labels->name ) .': ' . $term->name,
				'menu-item-type'   => 'post_type_term_archive',
				'menu-item-object' => $post_type_term,
				'menu-item-url'    => get_post_type_term_link( $post_type, $term ),
*/

				foreach ( $terms as $term ) {
					$items[] = array(
						'id'			=> $term->term_id,
						'object'		=> $post_type->name . Core\Core::SEPARATOR . $tax,
						'object_id'		=> $term->term_id,
						'title'			=> sprintf( '%s – %s', 
												$post_type->labels->name,
												$term->name 
											),
						'type'			=> 'post_type_term_archive',
						'type_label'	=> __( 'Post Type Term Archive', 'posttype-term-archive'),
						'url'			=> get_post_type_term_link( $post_type->name, $term ),
					);
				}

			}
		}
		return $items;
	}

	/**
	 * MetaBox Content Callback
	 * @return string $html
	 */
	public function metabox() {
		
		// Inform user no CPTs available to be shown.
		if ( empty( $this->archives ) ) {
			echo '<p>' . __( 'No items.' ) . '</p>';
			return;
		}
		
		global $nav_menu_selected_id;

		$html = '<ul id="'. self::METABOXLISTID .'">';
		foreach ( $this->archives as $archive ) {
			$post_type = $archive['post_type'];
			foreach ( $archive['taxonomies'] as $tax => $tax_obj ) {
				$html .= sprintf( '<li><strong>%s - %s</strong><ul>',
					$post_type->labels->name,
					$tax_obj->labels->name 
				);
				$terms = get_terms( array(
					'hide_empty'	=> false,
					'taxonomy'		=> $tax,
				) );

				foreach ( $terms as $term ) {
					$html .= sprintf(
						'<li><label><input type="checkbox" value="%s%s%s" />&nbsp;%s</label></li>',
						$post_type->name,
						Core\Core::SEPARATOR,
						esc_attr( $term->term_id ),
						$term->name
					);
					
				}
				$html .= '</ul></li>';
			}
		}
		$html .= '</ul>';

		// 'Add to Menu' button
		$html .= '<p class="button-controls"><span class="add-to-menu">';
		$html .= '<input type="submit"'. disabled( $nav_menu_selected_id, 0, false ) .' class="button-secondary
			  submit-add-to-menu right" value="'. esc_attr__( 'Add to Menu', 'posttype-term-archive') .'" 
			  name="add-post-type-menu-item" id="submit-post-type-term-archives" />';
		$html .= '<span class="spinner"></span>';
		$html .= '</span></p>';
		
		print $html;
	}

	/**
	 * Scripts for AJAX call
	 * Only loads on nav-menus.php
	 * @param  string $hook Page Name
	 * @return void
	 */
	public function metabox_script( $hook ) {

		if ( 'nav-menus.php' !== $hook ) {
			return;
		}
		
		if ( empty( $this->archives ) ) {
			return;
		}

		wp_register_script(
			'pt-term-archive-menus',
			$this->core->get_asset_url( '/js/admin/nav-menus.js' ),
			array( 'jquery' )
		);
		wp_enqueue_script( 'pt-term-archive-menus' );

		// Add nonce variable
		wp_localize_script(
			'pt-term-archive-menus',
			'pt_term_archives',
			array(
				'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
				'nonce'				=> wp_create_nonce( self::NONCE ),
				'metabox_id'		=> self::METABOXID,
				'metabox_list_id'	=> self::METABOXLISTID,
				'action'			=> self::NONCE
			)
		);
	}

	/**
	 * AJAX Callback to create the menu item and add it to menu
	 * @return string $HTML built with walk_nav_menu_tree()
	 * use \Post_Type_Archive_Links::is_allowed() Check request and return choosen post types
	 */
	public function ajax_add_post_type() {
		$post_type_terms = $this->is_allowed();

		// Create menu items and store IDs in array
		$item_ids = array();
		foreach ( $post_type_terms as $post_type_term ) {
			@list( $post_type, $term_id ) = explode( Core\Core::SEPARATOR, $post_type_term );
			if ( ! $post_type || ! $term_id ) {
				continue;
			}
			
			$post_type_obj = get_post_type_object( $post_type );
			$term = get_term( $term_id );
			

			if( ! $post_type_obj )
				continue;

			$menu_item_data = array(
				'menu-item-title'		=> sprintf( '%s – %s', 
											$post_type_obj->labels->name,
											$term->name 
										),
				'menu-item-type'		=> 'post_type_term_archive',
				'menu-item-object'		=> $post_type . Core\Core::SEPARATOR . $term->taxonomy,
				'menu-item-object-id'	=> $term->term_id,
				'menu-item-url'			=> get_post_type_term_link( $post_type, $term ),
			);

			// Collect the items' IDs.
			$item_ids[] = wp_update_nav_menu_item( 0, 0, $menu_item_data );
		}

		// If there was an error die here
		is_wp_error( $item_ids ) AND die( '-1' );

		// Set up menu items
		foreach ( (array) $item_ids as $menu_item_id ) {
			$menu_obj = get_post( $menu_item_id );
			if ( ! empty( $menu_obj->ID ) ) {
				$menu_obj = wp_setup_nav_menu_item( $menu_obj );
				// don't show "(pending)" in ajax-added items
				$menu_obj->label = $menu_obj->title;

				$menu_items[] = $menu_obj;
			}
		}

		// Needed to get the Walker up and running
		require_once ABSPATH.'wp-admin/includes/nav-menu.php';

		// This gets the HTML to returns it to the menu
		if ( ! empty( $menu_items ) ) {
			$args = array(
				'after'       => '',
				'before'      => '',
				'link_after'  => '',
				'link_before' => '',
				'walker'      => new \Walker_Nav_Menu_Edit
			);

			echo walk_nav_menu_tree(
				$menu_items,
				0,
				(object) $args
			);
		}

		// Finally don't forget to exit
		exit;

	}


	/**
	 * Is the AJAX request allowed and should be processed?
	 * @return void
	 */
	public function is_allowed() {
		// Capability Check
		! current_user_can( 'edit_theme_options' ) AND die( '-1' );

		// Nonce check
		check_ajax_referer( self::NONCE, 'nonce' );

		// Is a post type chosen?
		$post_type_terms = filter_input_array(
			INPUT_POST,
			array(
				'post_type_terms' => array(
					'name'	=> 'post_type_terms',
					'filter' => FILTER_SANITIZE_STRING,
					'flags' => FILTER_REQUIRE_ARRAY
				)
			)
		);
		
		empty( $post_type_terms['post_type_terms'] ) AND exit;
		// return post types if chosen
		return array_values( $post_type_terms['post_type_terms'] );
	}



}

