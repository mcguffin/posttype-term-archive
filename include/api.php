<?php


/**
 * Return CPT Term archive link.
 * 
 * @param	string			$post_type	The Post Type
 * @param	int|object		$term		Term ID, term slug or Term object
 * @param	string			$taxonomy	Taxonomy name. Mandatory if $term is a slug
 * @return	string|WP_Error	The CPT term archive Link or WP_Error on failure
 */

function get_post_type_term_link( $post_type , $term , $taxonomy = '' ) {
	
	if ( empty( $taxonomy ) ) {
		$taxonomy = PosttypeTermArchive\Core\Archive::get_term_taxonomy( $term );
	}

	if ( is_wp_error( $taxonomy ) ) {
		return $taxonomy;
	}

	$inst = PosttypeTermArchive\Core\Archive::get( $post_type , $taxonomy );

	return $inst->get_link( $term );
}


/**
 * Return CPT Term archive link.
 * 
 * @param	string			$post_type	The Post Type
 * @param	string			$taxonomy	Taxonomy name. Mandatory if $term is a slug
 */

function register_post_type_taxonomy( $post_type , $taxonomy, $show_in_menus = true ) {
	if ( ! post_type_exists( $post_type ) ) {
		return new WP_Error('post_type_taxonomy', sprintf(__('Invalid Post Type %s','posttype-term-archive'), $post_type ));
	}
	if ( ! taxonomy_exists($taxonomy) ) {
		return new WP_Error('post_type_taxonomy', sprintf(__('Invalid Taxonomy %s','posttype-term-archive'), $taxonomy ));
	}
	
	return PosttypeTermArchive\Core\Archive::get( $post_type , $taxonomy, $show_in_menus );
}


/**
 * Return CPT Term archive link.
 * 
 * @param	string			$post_type	The Post Type
 * @param	string			$taxonomy	Taxonomy name. Mandatory if $term is a slug
 */
function has_post_type_taxonomy( $post_type , $taxonomy ) {
	if ( ! post_type_exists( $post_type ) ) {
		return new WP_Error('post_type_taxonomy', sprintf(__('Invalid Post Type %s','posttype-term-archive'), $post_type ));
	}
	if ( ! taxonomy_exists($taxonomy) ) {
		return new WP_Error('post_type_taxonomy', sprintf(__('Invalid Taxonomy %s','posttype-term-archive'), $taxonomy ));
	}
	
	return PosttypeTermArchive\Core\Archive::has( $post_type , $taxonomy );
}

