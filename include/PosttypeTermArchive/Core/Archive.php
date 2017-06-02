<?php

namespace PosttypeTermArchive\Core;

class Archive {
	private $post_type;
	private $taxonomy;
	private $show_in_menus;
	
	private static $_instances = array();

	public static function get_archives( ) {
		$archives = array();
		foreach ( self::$_instances as $post_type => $pts ) {
			$taxonomies = array();
			foreach ( $pts as $taxonomy => $instance ) {
				if ( $instance->show_in_menus ) {
					$taxonomies += get_taxonomies( array( 'name' => $taxonomy ), 'object' );
				}
			}
			if ( ! empty( $taxonomies ) ) {
				$archives[] = array(
					'post_type'		=> get_post_type_object( $post_type ),
					'taxonomies'	=> $taxonomies,
				);
			}
		}
		return $archives;
	}


	public static function has( $post_type , $taxonomy ) {
		return isset( self::$_instances[$post_type] ) && isset( self::$_instances[$post_type][$taxonomy] );
	}
	
	public static function get( $post_type, $taxonomy, $show_in_menus = true ) {
		if ( ! isset( self::$_instances[$post_type] ) ) {
			self::$_instances[$post_type] = array();
		}

		if ( ! isset( self::$_instances[$post_type][$taxonomy] ) ) {
			self::$_instances[$post_type][$taxonomy] = new self( $post_type , $taxonomy, $show_in_menus );
		}
		return self::$_instances[$post_type][$taxonomy];
	}
	
	private function __construct( $post_type, $taxonomy, $show_in_menus ) {
				
		$this->post_type = $post_type;
		$this->taxonomy	= $taxonomy;
		$this->show_in_menus = $show_in_menus;

		add_filter( 'rewrite_rules_array', array( &$this , 'rewrite_rules' ) , 11 );
	}
	
	/**
	 * Return CPT Term archive link.
	 * 
	 * @param	int|string|object	$term		Term ID or Term object
	 * @return	string|WP_Error		The terms taxonomy
	 */
	public static function get_term_taxonomy( $term ) {
		global $wpdb;
		
		if ( is_object( $term ) ) {
			if ( isset( $term->taxonomy ) ) {
				return $term->taxonomy;
			}
		} else if ( is_int( $term ) ) {
			$sql = $wpdb->prepare( "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id=%d" , $term );
			if ( $taxonomy = $wpdb->get_var( $sql ) ) {
				return $taxonomy;
			}
		}
		
		return new \WP_Error('invalid_term', __('Empty Term','posttype-term-archive'));
	}

	
	/**
	 * Return CPT Term archive link.
	 * 
	 * @param	int|string|object	$term		Term ID, term slug or Term object
	 * @return	string|WP_Error	The CPT term archive Link or WP_Error on failure
	 */
	function get_link( $term ) {
		global $wp_rewrite;
		
		// chack and sanitize params
		if ( ! is_object($term) ) {
			if ( is_int($term) ) {
				$term = get_term($term, $this->taxonomy);
			} else {
				$term = get_term_by('slug', $term, $this->taxonomy);
			}
		}

		if ( ! is_object($term) )
			$term = new \WP_Error('invalid_term', __('Empty Term','posttype-term-archive'));

		if ( is_wp_error( $term ) )
			return $term;

		$post_type_obj = get_post_type_object( $this->post_type );

		if ( is_null( $post_type_obj ) )
			return new \WP_Error( 'invalid_post_type' , __( 'Invalid post type' , 'posttype-term-archive') );
		
		$archive_link = get_post_type_archive_link( $this->post_type );

		$termlink = $wp_rewrite->get_extra_permastruct($this->taxonomy);

		$slug = $term->slug;
		$t = get_taxonomy($this->taxonomy);

		if ( empty($termlink) ) {
			if ( 'category' == $this->taxonomy )
				$archive_link = add_query_arg( 'cat' , $term->term_id , $archive_link );
			elseif ( $t->query_var )
				$archive_link = add_query_arg( $t->query_var , $slug , $archive_link );
			else
				$archive_link = add_query_arg( array( 'taxonomy' => $this->taxonomy , 'term' => $slug ) , $t->query_var , $archive_link );
		} else {
			if ( $t->rewrite['hierarchical'] ) {
				$hierarchical_slugs = array();
				$ancestors = get_ancestors( $term->term_id, $this->taxonomy, 'taxonomy' );
				foreach ( (array)$ancestors as $ancestor ) {
					$ancestor_term = get_term($ancestor, $this->taxonomy);
					$hierarchical_slugs[] = $ancestor_term->slug;
				}
				$hierarchical_slugs = array_reverse($hierarchical_slugs);
				$hierarchical_slugs[] = $slug;
				$termlink = str_replace("%$this->taxonomy%", implode('/', $hierarchical_slugs), $termlink);
			} else {
				$termlink =  str_replace("%$this->taxonomy%", $slug, $termlink);
			}
			$termlink = preg_replace('/^\/?/','',$termlink);
			$archive_link = trailingslashit( $archive_link ) . $termlink;
		}
		/**
		 * Filter the Post type term link.
		 *
		 * @param string $archive_link	Term Archive link URL.
		 * @param object $post_type		Post Type.
		 * @param object $term     		Term object.
		 * @param string $taxonomy 		Taxonomy slug.
		 */
		return apply_filters( 'post_type_term_link', $archive_link, $this->post_type, $term, $this->taxonomy );
	}

	/**
	 * @filter rewrite_rules_array
	 */
	function rewrite_rules( $rules ) {
		$post_type = $this->post_type;
		
		$pto = get_post_type_object( $this->post_type );
		$taxo_obj = get_taxonomy($this->taxonomy);

		$newrules = array();
		if ( ( in_array( $this->taxonomy , $pto->taxonomies ) 
			|| in_array( $this->post_type , $taxo_obj->object_type ) )
			&& $taxo_obj->public && $taxo_obj->rewrite ) {

			$tax_rewrite_slug = $taxo_obj->rewrite['slug'];

			foreach ( $rules as $regex => $rule ) {

				parse_str( parse_url( $rule, PHP_URL_QUERY ), $q );

				if ( $this->post_type === 'post' && isset( $q[$this->taxonomy] ) ) {
					$match_index = preg_match_all('/\([^\)]+\)/',$regex) + 1;
					$new_regex = $this->post_type.'/'.$regex;
					$new_rule = sprintf('%s&post_type=$matches[%d]' , $rule , $match_index );

					$newrules[$new_regex] = $new_rule;

				} else if ( isset( $q['post_type'] ) && $q['post_type'] === $this->post_type ) {

					$pt_rewrite = isset($pto->rewrite['slug']) ? $pto->rewrite['slug'] : $this->post_type;
					
					// split regex at post type
					@list($regex_before_pt,$regex_after_pt) = explode( "{$pt_rewrite}/" , $regex );
					// get match_index by counting braces in part before post type
					$match_index = preg_match_all('/\([^\)]+\)/',$regex_before_pt) + 1;
					// assemble new regex with post type and taxonomy name
					$new_regex = $regex_before_pt . "{$pt_rewrite}/{$tax_rewrite_slug}/([^/]+?)/" . $regex_after_pt;

					// split rewrite rule at post type
					@list( $rule_before_pt , $rule_after_pt ) = explode( "post_type={$this->post_type}" , $rule );
					// increment all $matches indices behind post type QV
					$rule_after_pt = preg_replace_callback(  '/\$matches\[(\d+)\]$/' , array( $this , '_increment_matches' ) , $rule_after_pt  );

					// assemble new rule
					$newrules[$new_regex] = sprintf( '%spost_type=%s&%s=$matches[%d]%s' , 
											$rule_before_pt , 
											$this->post_type , 
											$taxo_obj->query_var , 
											$match_index , 
											$rule_after_pt 
										);
				}
				$newrules[ $regex ] = $rule;
			}
		} else {
			$newrules = $rules;
		}

//var_dump($ret);
		return $newrules;
	}
	private function _paged_to_top( $a, $b ) {
		$a_page = strpos( $a, 'page/([0-9]{1,})/?$' ) !== false;
		$b_page = strpos( $b, 'page/([0-9]{1,})/?$' ) !== false;
		if ( $a_page === $b_page ) {
			return 0;
		}
		return $a_page ? -1 : 1;
	}
	/**
	 * @private
	 */
	private function _increment_matches( $match ) {
		return sprintf( '$matches[%d]' , $match[1]+1 );
	}
}