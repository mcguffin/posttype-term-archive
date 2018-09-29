<?php

namespace PosttypeTermArchive\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use PosttypeTermArchive\Core;


class Polylang extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		add_filter('pll_translation_url',array($this,'translation_url'),10,2);
	}


	/**
	 *	@filter pll_translation_url
	 */
	public function translation_url( $url , $language_slug ) {

		if ( ! is_paged() && is_post_type_archive() && (is_category() || is_tag() || is_tax() ) ) {
			$term = get_queried_object();
			$translated_term_id = pll_get_term( $term->term_id , $language_slug );
			if ( $translated_term_id ) {
				$post_type = get_post_type();
				$url = get_post_type_term_link( $post_type , $translated_term_id, $term->taxonomy );
				if ( is_wp_error( $url ) ) {
					return false;
				}
				// if term has posts
				global $wpdb;
				$sql = "SELECT count(id) FROM $wpdb->posts AS p
						LEFT JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id
						WHERE tr.term_taxonomy_id =%d
						AND p.post_type=%s";
				$res = $wpdb->get_var( $wpdb->prepare( $sql, $translated_term_id, $post_type ) );

				if ( ! $res ) {
					return false;
				}
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
	 public static function uninstall() {
		 // remove content and settings
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
