<?php

namespace PosttypeTermArchive\Admin;

use PosttypeTermArchive\Ajax;
use PosttypeTermArchive\Core;
use PosttypeTermArchive\Settings;


class NavMenuTermArchives extends Core\Singleton {


	/**
	 *	@var Core\Core
	 */
	private $core;

	/**
	 *	@var Ajax\AjaxHandler
	 */
	private $ajax_handler;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		$this->ajax_handler = new Ajax\AjaxHandler( 'post-type-terms', array(
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

		$this->archives = Core\TermArchive::get_archives();

		foreach ( $this->archives as $archive ) {
			$pto = $archive['post_type'];
			foreach ( $archive['taxonomies'] as $tax => $txo ) {
				$box_id = sprintf( 'posttype-archive-%s-%s', $pto->name, $txo->name );

				add_meta_box(
					$box_id,
					sprintf( '%s %s', $pto->labels->singular_name, $txo->labels->name ),
					array( $this, 'metabox' ),
					'nav-menus',
					'side',
					'low',
					array( 'post_type' => $pto, 'taxonomy' => $txo )
				);

			}
		}
	}


	/**
	 * MetaBox Content Callback
	 * @return string $html
	 */
	public function metabox( $object, $box ) {

		global $nav_menu_selected_id;

		$post_type = $box['args']['post_type'];
		$taxonomy = $box['args']['taxonomy'];

		$terms = get_terms( array(
			'hide_empty'	=> false,
			'taxonomy'		=> $taxonomy->name,
		) );

		// Inform user no CPTs available to be shown.
		if ( empty( $terms ) ) {
			echo '<p>' . __( 'No items.' ) . '</p>';
			return;
		}

		$box_id = sprintf( 'posttype-archive-%s-%s', $post_type->name, $taxonomy->name );

		$html = sprintf( '<ul id="%s">', $box_id );

		foreach ( $terms as $term ) {
			$html .= sprintf(
				'<li><label><input type="checkbox" value="%s%s%s" />&nbsp;%s</label></li>',
				$post_type->name,
				Core\Core::SEPARATOR,
				esc_attr( $term->term_id ),
				$term->name
			);

		}

		$html .= '</ul>';

		// 'Add to Menu' button
		$html .= '<p class="button-controls">';
		$html .= 	'<span class="add-to-menu">';
		$html .= 		sprintf('<input type="submit" %1$s ' .
							'class="button-secondary submit-add-to-menu add-post-type-term-menu-item right" value="%2$s" '.
							'name="add-post-type-term-menu-item-%3$s-%4$s" id="submit-post-type-term-archives-%3$s-%4$s" '.
							'data-action="%5$s" data-nonce="%6$s" '.
							 '/>',

							disabled( $nav_menu_selected_id, 0, false ),
							esc_attr__( 'Add to Menu', 'posttype-term-archive' ),
							$post_type->name,
							$taxonomy->name,
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
		if ( empty( $this->archives ) ) {
			return $item_types;
		}
		foreach ( $this->archives as $archive ) {
			$post_type = $archive['post_type'];

			foreach ( $archive['taxonomies'] as $tax => $tax_obj ) {
				$item_types[] = array(
					//*
					'title'			=> sprintf( '%s – %s',
											$post_type->labels->singular_name,
											$tax_obj->labels->name
										),
					/*/
					'title'			=> $tax_obj->labels->name,
					//*/
					'type_label'	=> __( 'Post Type Term Archive', 'posttype-term-archive' ),
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
						//*
						'title'			=> sprintf( '%s – %s',
												$post_type->labels->name,
												$term->name
											),
						/*/
						'title'			=> $term->name,
						//*/
						'type'			=> 'post_type_term_archive',
						'type_label'	=> __( 'Post Type Term Archive', 'posttype-term-archive' ),
						'url'			=> get_post_type_term_link( $post_type->name, $term ),
					);
				}

			}
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
	public function ajax_add_post_type( $args ) {

		$post_type_terms = array_filter( (array) $args['items'], 'is_string' );

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
				//*
				'menu-item-title'		=> sprintf( '%s %s',
											$post_type_obj->labels->singluar_name,
											$term->name
										),
				/*/
				'menu-item-title'		=> $term->name,
				//*/
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
	public function get_value() {

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
