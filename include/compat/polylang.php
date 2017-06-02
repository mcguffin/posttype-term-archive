<?php


/**
 *	Polylang Filter to get translated ppost type term links
 */
if ( ! function_exists( 'polylang_post_type_term_link' ) ) :
function polylang_post_type_term_link( $url , $language_slug ) {
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
endif;
add_filter('pll_translation_url','polylang_post_type_term_link',10,2);
