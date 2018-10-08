<?php

namespace PosttypeTermArchive\Core;

use PosttypeTermArchive\Compat;

class Core extends Plugin {

	const SEPARATOR = '___';

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		add_action( 'plugins_loaded' , array( $this , 'plugins_loaded' ) );
		add_action( 'plugins_loaded' , array( $this , 'init_compat' ), 0 );
		add_action( 'init' , array( $this , 'apply_settings' ), 0xffffffff );
		add_action( 'init' , array( $this , 'init' ) );
		add_action( 'wp_enqueue_scripts' , array( $this , 'wp_enqueue_style' ) );

		add_action( 'register_post_type_taxonomy', 'register_post_type_taxonomy', 10, 3 );
		add_filter( 'post_type_term_link', array( $this, 'get_post_type_term_link'), 10, 4 );


		// posttype archive pages
		add_filter( 'nav_menu_css_class', array( $this, 'nav_item_css_class' ), 10, 5 );
		add_filter( 'page_link', array( $this, 'page_link' ), 10, 2 );

		$args = func_get_args();
		parent::__construct( ...$args );
	}


	/**
	 *	@filter nav_menu_css_class
	 */
	public function nav_item_css_class( $classes, $item, $args, $depth ) {

		if ( 'page' === $item->object ) {

			if ( $this->is_archive_page( $item->object_id ) ) {
				$classes[] = 'current-menu-parent';
			}

		}
		return $classes;
	}


	/**
	 *	@filter page_link
	 */
	public function page_link( $link, $post_id ) {

		if ( $post_type = $this->is_archive_page( $post_id ) ) {
			$link = get_post_type_archive_link( $post_type );
		}

		return $link;
	}

	/**
	 *	Is $page_id a configured post type archive
	 *	@return bool
	 */
	public function is_archive_page( $page_id ) {

		return array_search( $page_id, get_option('post_type_archive_pages') );
	}

	/**
	 *	Load Compatibility classes
	 *
	 *  @action plugins_loaded
	 */
	public function init_compat() {
		if ( class_exists('Polylang') ) {
			Compat\Polylang::instance();
		}
		if ( defined( 'WPSEO_VERSION' ) ) {
			Compat\WPSEO::instance();
		}
	}

	/**
	 *	Load frontend styles and scripts
	 *
	 *	@filter post_type_term_link
	 */
	function get_post_type_term_link( $link, $post_type , $term , $taxonomy = '' ) {
		return get_post_type_term_link( $post_type, $term, $taxonomy );
	}

	/**
	 *	Load frontend styles and scripts
	 *
	 *	@action wp_enqueue_scripts
	 */
	public function wp_enqueue_style() {
	}

	/**
	 *	@action plugins_loaded
	 */
	public function plugins_loaded() {

		// apply settings


		add_filter( 'wp_setup_nav_menu_item',  array( $this, 'setup_archive_item' ) );

		add_filter( 'wp_nav_menu_objects', array( $this, 'maybe_make_current' ) );

	}

	/**
	 *	@action init
	 */
	public function apply_settings() {

		if ( ! apply_filters( 'posttype_term_archive_settings', true ) ) {
			return;
		}

		if ( ! $archive_settings = get_option('posttype_term_archives')) {
			return;
		}

		foreach ( $archive_settings as $post_type => $taxonomies ) {
			foreach ( $taxonomies as $tax => $args ) {
				if ( isset( $args[ 'enabled' ] ) ) {
					register_post_type_taxonomy( $post_type, $tax, $args + array( 'show_in_settings' => true ) );
				}
			}
		}
	}

	/**
	 * Assign menu item the appropriate url
	 * @param  object $menu_item
	 * @return object $menu_item
	 */
	public function setup_archive_item( $menu_item ) {
		if ( $menu_item->type !== 'post_type_term_archive' )
			return $menu_item;

		@list( $post_type, $taxonomy ) = explode( self::SEPARATOR, $menu_item->object );
		$term_id = $menu_item->object_id;

		if ( ! $post_type || ! $term_id ) {
			return $menu_item;
		}

		$term = get_term( $term_id );
		$link = get_post_type_term_link( $post_type, $term );

		if ( is_wp_error( $link ) ) {
			return $menu_item;
		}

		$menu_item->type_label = __( 'Archive', 'posttype-term-archive');
		$menu_item->url = $link;

		return $menu_item;
	}


	/**
	 * Make post type archive link 'current'
	 * @uses   Post_Type_Archive_Links :: get_item_ancestors()
	 * @param  array $items
	 * @return array $items
	 * @filter wp_nav_menu_objects
	 */
	public function maybe_make_current( $items ) {
		foreach ( $items as $item ) {
			if ( 'post_type_term_archive' !== $item->type ) {
				continue;
			}
			@list( $post_type, $taxonomy ) = explode( self::SEPARATOR, $item->object );

			if (
				! is_post_type_archive( $post_type )
				&& ! is_tax( $taxonomy, $item->object_id )
			) {
				continue;
			}
			$term = get_term( $item->object_id );

			// Make item current
			$item->current = true;

			if ( is_post_type_archive( $post_type ) && is_tax( $taxonomy, $item->object_id ) ) {
				$item->classes[] = 'current-menu-item';
			}

			// Loop through ancestors and give them 'parent' or 'ancestor' class
			$active_anc_item_ids = $this->get_item_ancestors( $item );
			foreach ( $items as $key => $parent_item ) {
				$classes = (array) $parent_item->classes;

				// If menu item is the parent
				if ( $parent_item->db_id == $item->menu_item_parent ) {
					$classes[] = 'current-menu-parent';
					$items[ $key ]->current_item_parent = true;
				}

				// If menu item is an ancestor
				if ( in_array( intval( $parent_item->db_id ), $active_anc_item_ids ) ) {
					$classes[] = 'current-menu-ancestor';
					$items[ $key ]->current_item_ancestor = true;
				}

				$items[ $key ]->classes = array_unique( $classes );
			}
		}

		return $items;
	}


	/**
	 * Get menu item's ancestors
	 * @param  object $item
	 * @return array  $active_anc_item_ids
	 */
	public function get_item_ancestors( $item ) {
		$anc_id = absint( $item->db_id );

		$active_anc_item_ids = array();
		while (
			$anc_id = get_post_meta( $anc_id, '_menu_item_menu_item_parent', true )
			AND ! in_array( $anc_id, $active_anc_item_ids )
		)
			$active_anc_item_ids[] = $anc_id;

		return $active_anc_item_ids;
	}


	/**
	 *	Init hook.
	 *
	 *  @action init
	 */
	public function init() {
	}


	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string
	 */
	public function get_asset_url( $asset ) {
		return plugins_url( $asset, $this->get_plugin_file() );
	}




	/**
	 *	Fired on plugin activation
	 */
	public function activate() {
	}

	/**
	 *	Fired on plugin deactivation
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 *	Fired on plugin deinstallation
	 */
	public static function uninstall() {
	}

}
