<?php

namespace PosttypeTermArchive\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use PosttypeTermArchive\Core;


class WPSEO extends Core\PluginComponent {

	protected function __construct() {

		add_filter( 'wpseo_breadcrumb_links', array( $this, 'breadcrumb_links' ) );
		add_filter( 'wpseo_canonical', array( $this, 'canonical' ) );
		add_filter( 'posttype_term_archive_link', array( $this, 'archive_link' ), 10, 3 );
//		add_filter( 'wpseo_breadcrumb_single_link', 'wpseo_post_type_taxonomy_breadcrumb_single_link', 10, 2 );

	}

	/**
	 *	@filter posttype_term_archive_link
	 */
	public function archive_link( $archive_link, $term, $paged ) {
		if ( is_front_page() ) {
			$archive_link = WPSEO_Sitemaps_Router::get_base_url( '' );
		}
		return $archive_link;
	}


	/**
	 *	@filter wpseo_canonical
	 */
	public function canonical( $canonical ) {

		if ( ( $archive = Core\TermArchive::maybe_get() ) && $archive->canonical ) {

			$paged = get_query_var( 'paged' ) > 1 ? get_query_var( 'paged' ) : false;

			$canonical = $archive->get_link( get_queried_object(), $paged );

			add_filter( 'wpseo_prev_rel_link', array( $this, 'prev_rel_link' ) );
			add_filter( 'wpseo_next_rel_link', array( $this, 'next_rel_link' ) );

		}

		return $canonical;
	}

	/**
	 *	@action wpseo_prev_rel_link
	 */
	public function prev_rel_link( $link ) {
		if ( $archive = Core\TermArchive::maybe_get() ) {
			$paged = get_query_var( 'paged' ) - 1;
			if ( $paged <= 1 ) {
				$paged = false;
			}
			$url = $archive->get_link( get_queried_object(), $paged );
			if ( ! is_wp_error( $url ) ) {
				$link = sprintf( '<link rel="prev" href="%s" />' . "\n", esc_url( $url ) );
			}
		}
		return $link;

	}

	/**
	 *	@action wpseo_next_rel_link
	 */
	public function next_rel_link( $link ) {
		if ( $archive = Core\TermArchive::maybe_get() ) {
			$paged = get_query_var( 'paged' ) + 1;
			$url = $archive->get_link( get_queried_object(), $paged );
			if ( ! is_wp_error( $url ) ) {
				$link = sprintf( '<link rel="next" href="%s" />' . "\n", esc_url( $url ) );
			}
		}
		return $link;
	}

	/**
	 *	@filter wpseo_breadcrumb_links
	 */
	public function breadcrumb_links( $links ) {
		$archive = Core\Archive::instance();
		if ( is_post_type_archive() && is_category() || is_tag() || is_tax() ) { // is pt term archive
			$term = get_queried_object();
			$links[] = array(
				'term'	=> $term,
			);
		} else if ( $page_id = $archive->get_current_post_type_archive_page() ) { // is pt-archive with page

			array_pop($links);

			$links = $this->append_ancestors( $links, $page_id );
		} else if ( is_singular() && ( $post_type = get_post_type() ) && ( $page_id = $archive->get_archive_page_id( $post_type ) ) ) {

			$last = array_pop($links);

			array_pop( $links );

			$links = $this->append_ancestors( $links, $page_id );
			$links[] = $last;

		}
		return $links;
	}

	/**
	 *
	 */
	private function append_ancestors( $links, $page_id ) {
		if ( $ancestors = get_post_ancestors($page_id) ) {
			foreach ( $ancestors as $id ) {
				$links[] = array(
					'id'	=> $id,
				);
			};
		}
		$links[] = array(
			'id'	=> $page_id,
		);
		return $links;
	}


	/**
	 *	@filter wpseo_breadcrumb_single_link
	 */
	public function breadcrumb_single_link( $output, $link ) {
		return $output;
	}

	/**
	 *	@inheritdoc
	 */
	 public function activate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public function deactivate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public static function uninstall() {
		 // remove content and settings
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
