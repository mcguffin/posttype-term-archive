<?php

namespace PosttypeTermArchive\Core;

class Archive extends Singleton {

	/**
	 *	@var string	The post type
	 */
	private $post_type;


	/**
	 *	@inheritdoc
	 */
	protected function __construct( ) {

		add_action( 'registered_post_type' , array( $this, 'registered_post_type' ), 0, 2 );

		add_filter( 'nav_menu_css_class', array( $this, 'nav_item_css_class' ), 10, 5 );

		add_filter( 'get_the_archive_title', array( $this, 'archive_title' ) );
		add_filter( 'get_the_archive_description', array( $this, 'archive_description' ) );

		// add filter: post type archive url > page url
//		add
		$args = func_get_args();
		parent::__construct( ...$args );

	}

	/**
	 *	@filter get_the_archive_title
	 */
	public function archive_title( $title ) {

		if ( $archive_page_id = $this->get_current_post_type_archive_page() ) {
			$title = get_the_title( $archive_page_id );
		}
		return $title;
	}

	/**
	 *	@filter get_the_archive_description
	 */
	public function archive_description( $description ) {
		if ( $archive_page_id = $this->get_current_post_type_archive_page() ) {
			$description = apply_filters( 'the_content', get_post( $archive_page_id )->post_content );
		}
		return $description;
	}

	/**
	 *	@action registered_post_type
	 */
	public function registered_post_type( $post_type, $post_type_object ) {

		$core = Core::instance();

		if ( $archive_page_id = $this->get_current_post_type_archive_page( $post_type ) ) {

			if ( $post_type_object->rewrite ) {
				global $wp_post_types;
				if ( $post_type_object->rewrite === true ) {
					$post_type_object->rewrite = get_page_uri( $archive_page_id );
				} else if ( is_array( $post_type_object->rewrite ) ) {
					$post_type_object->rewrite['slug'] = get_page_uri( $archive_page_id );
				}

				$wp_post_types[ $post_type ] = $post_type_object;
				$post_type_object->add_rewrite_rules();
			}
		}
	}

	/**
	 *	@return bool|int
	 */
	public function get_current_post_type_archive_page() {
		if ( ! is_post_type_archive() ) {
			return false;
		}
		if (  is_category() || is_tag() || is_tax()  ) {
			return false;
		}
		if ( ! $post_type = get_post_type() ) {
			return false;
		}
		return $this->get_archive_page_id( $post_type );

	}

	/**
	 *	@param int
	 *	@return bool|string	false or post_type
	 */
	public function page_is_archive( $post_id ) {
		if ( ! $archive_settings = get_option('post_type_archive_pages')) {
			return false;
		}
		return array_search( $post_id, $archive_settings );
	}



	/**
	 *	@param $page
	 *	@param $post_type
	 */
	public function get_archive_page_id( $post_type = 'post' ) {
		if ( ! $archive_settings = get_option('post_type_archive_pages')) {
			return false;
		}
		if ( ! isset( $archive_settings[ $post_type ] ) ) {
			return false;
		}
		return $archive_settings[ $post_type ];
	}

	/**
	 *	@filter nav_menu_css_class
	 */
	public function nav_item_css_class( $classes, $item, $args, $depth ) {

		if ( 'page' === $item->object && ( $post_type = $this->page_is_archive( $item->object_id ) ) && is_post_type_archive( $post_type ) ) {
			$classes[] = 'current-menu-item';
		}
		return $classes;
	}

	/**
	 *	Destructor
	 */
	public function __destruct() {
		remove_action( 'registered_post_type' , array( $this, 'registered_post_type' ), 0 );
	}


}
