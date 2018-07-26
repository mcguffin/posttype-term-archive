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
//		add_filter( 'wpseo_breadcrumb_single_link', 'wpseo_post_type_taxonomy_breadcrumb_single_link', 10, 2 );

	}

	/**
	 *	@filter wpseo_canonical
	 */
	public function canonical( $canonical ) {

		if ( $archive = Core\Archive::maybe_get() ) {

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
		if ( $archive = Core\Archive::maybe_get() ) {
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
		if ( $archive = Core\Archive::maybe_get() ) {
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
		if ( is_post_type_archive() && is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			$links[] = array(
				'term'	=> $term,
			);
		}
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
	 public function uninstall() {
		 // remove content and settings
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
