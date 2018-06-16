<?php

namespace PosttypeTermArchive\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use PosttypeTermArchive\Core;


class WPSEO extends Core\PluginComponent {

	protected function __construct() {


		add_filter( 'wpseo_breadcrumb_links', 'wpseo_post_type_taxonomy_breadcrumb_links' );

//		add_filter( 'wpseo_breadcrumb_single_link', 'wpseo_post_type_taxonomy_breadcrumb_single_link', 10, 2 );

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
