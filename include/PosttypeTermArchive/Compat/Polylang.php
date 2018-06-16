<?php

namespace PostTypeTermArchive\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use PostTypeTermArchive\Core;


class Polylang extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		add_filter('pll_translation_url','polylang_post_type_term_link',10,2);
	}


	/**
	 *	@filter pll_translation_url
	 */
	public function translation_url( $url , $language_slug ) {
		if ( is_post_type_archive() && (is_category() || is_tag() || is_tax() ) ) {
			$term = get_queried_object();
			$translated_term_id = pll_get_term( $term->term_id , $language_slug );
			if ( $translated_term_id ) {
				$post_type = get_post_type();
				$url = get_post_type_term_link( $post_type , $translated_term_id , $term->taxonomy );
				if ( is_wp_error( $url ) )
					return false;
			} else {
				return false;
			}
		}
		return $url;
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
