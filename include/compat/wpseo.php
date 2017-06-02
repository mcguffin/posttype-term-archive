<?php



// yoast breadcrumbs
function wpseo_post_type_taxonomy_breadcrumb_links( $links ) {
	if ( is_post_type_archive() && is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		$links[] = array(
			'term'	=> $term,
		);
	}
	return $links;
}

add_filter( 'wpseo_breadcrumb_links', 'wpseo_post_type_taxonomy_breadcrumb_links' );


function wpseo_post_type_taxonomy_breadcrumb_single_link( $output, $link ) {
	return $output;
}

add_filter( 'wpseo_breadcrumb_single_link', 'wpseo_post_type_taxonomy_breadcrumb_single_link', 10, 2 );


