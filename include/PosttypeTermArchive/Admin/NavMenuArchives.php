<?php

namespace PosttypeTermArchive\Admin;

use PosttypeTermArchive\Ajax;
use PosttypeTermArchive\Core;
use PosttypeTermArchive\Settings;


class NavMenuArchives extends Core\Singleton {


	private $core;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();


		$this->ajax_handler = new Ajax\AjaxHandler( 'post-type-archive', array(
			'public'		=> false,
			'use_nonce'		=> true,
			'capability'	=> 'edit_theme_options',
			'callback'		=> array( $this, 'ajax_add_post_type'),
		));

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );

		add_filter( 'customize_nav_menu_available_items', array( $this, 'customize_nav_menu_available_items' ), 10, 4 );

		add_filter( 'customize_nav_menu_available_item_types', array( $this, 'customize_nav_menu_available_item_types' ) );

	}

	/**
	 * Admin init
	 * @action admin_init
	 */
	public function admin_init() {

		add_meta_box(
			'posttype-archives',
			__( 'Post Types', 'posttype-term-archive' ),
			array( $this, 'metabox' ),
			'nav-menus',
			'side',
			'low',
			array( )
		);
	}


	/**
	 * MetaBox Content Callback
	 * @return string $html
	 */
	public function metabox( $object, $box ) {

		global $nav_menu_selected_id;


		$html = '<ul id="posttype-archives">';

		foreach ( get_post_types(array('_builtin'=>false,'has_archive'=>true)) as $post_type ) {
			$pto = get_post_type_object($post_type);
			$html .= sprintf(
				'<li><label><input type="checkbox" value="%s" />&nbsp;%s</label></li>',
				$pto->name,
				$pto->labels->singular_name
			);
		}

		$html .= '</ul>';

		// 'Add to Menu' button
		$html .= '<p class="button-controls">';
		$html .= 	'<span class="add-to-menu">';
		$html .= 		sprintf('<input type="submit" %1$s ' .
							'class="button-secondary submit-add-to-menu add-post-type-term-menu-item right" value="%2$s" '.
							'name="add-post-type-archive-menu-item" id="submit-post-type-archive" '.
							'data-action="%3$s" data-nonce="%4$s" '.
							 '/>',

							disabled( $nav_menu_selected_id, 0, false ),
							esc_attr__( 'Add to Menu', 'posttype-term-archive' ),
							$this->ajax_handler->action,
							$this->ajax_handler->nonce
						);
		$html .= 	'<span class="spinner"></span>';
		$html .= 	'</span>';
		$html .= '</p>';

		print $html;
	}

	/**
	 *	@filter customize_nav_menu_available_item_types
	 */
	public function customize_nav_menu_available_item_types( $item_types ) {


		$item_types[] = array(
			//*
			'title'			=> __('Post Types', 'posttype-term-archive'),
			/*/
			'title'			=> $tax_obj->labels->name,
			//*/
			'type_label'	=> __( 'Post Type Archive', 'posttype-term-archive' ),
			'type'			=> 'post_type_archive',
			'object'		=> 'post_type_archive',
		);

		return $item_types;
	}

	/**
	 *	@filter customize_nav_menu_available_items
	 */
	public function customize_nav_menu_available_items( $items, $type, $object, $page ) {



		foreach ( get_post_types(array('has_archive'=> true)) as $post_type ) {
			$pto = get_post_type_object( $post_type );
			$items[] = array(
				'id'			=> $post_type,
				'object'		=> $pto->name . Core\Core::SEPARATOR . $tax,
				'object_id'		=> $term->term_id,
				//*
				'title'			=> __('Post Types', 'posttype-term-archives'),
				/*/
				'title'			=> $term->name,
				//*/
				'type'			=> 'post_type_archive',
				'type_label'	=> __( 'Post Type Archive', 'posttype-term-archive' ),
				'url'			=> get_post_type_archive_link( $post_type->name ),
			);
		}

		return $items;
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
				'nonce'				=> $this->ajax_handler->nonce,
				'action'			=> $this->ajax_handler->action,
			)
		);
	}

	/**
	 * AJAX Callback to create the menu item and add it to menu
	 * @return string $HTML built with walk_nav_menu_tree()
	 * use \Post_Type_Archive_Links::is_allowed() Check request and return choosen post types
	 */
	public function ajax_add_post_type($args) {

		$post_types = array_filter( (array) $args['items'], 'is_string' );

		if ( empty( $post_types ) ) {
			exit();
		}

		// Create menu items and store IDs in array
		$item_ids = array();

		foreach ( $post_types as $post_type ) {

			if ( ! $post_type_obj = get_post_type_object( $post_type )) {
				continue;
			}

			$menu_item_data = array(
				'menu-item-title'		=> sprintf( __('%s Archive', ''), $post_type_obj->labels->singular_name ),
				'menu-item-type'		=> 'post_type_archive',
				'menu-item-object'		=> $post_type,
				'menu-item-url'			=> get_post_type_archive_link( $post_type ),
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
				'walker'      => new \Walker_Nav_Menu_Edit(),
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


}
