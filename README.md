PostType Term Archive
=====================

Allow pretty Archive-URLs with custom post types, taxonomies and terms.  

Usage
-----

Registering a post type taxonomy archive:

```
do_action('register_post_type_taxonomy', 'book', 'publisher' );
```

Doing the same, but show some links in WP Menu editor:

```
do_action('register_post_type_taxonomy', 'book', 'publisher', true );
```

Same as above

```
do_action('register_post_type_taxonomy', 'book', 'publisher', array(
	'show_in_menu'	=> true,
) );
```

Same as above but allow admin to disable the archive in permalink settings

```
do_action('register_post_type_taxonomy', 'book', 'publisher', array(
	'show_in_menu'		=> true,
	'show_in_settings'	=> true,
) );
```

Disable configurability entirely:

```
// filter must be added before or during `plugins_loaded`
// best is to put in mu-plugin, like wp-content/mu-plugins/some-file.php
add_filter( 'posttype_term_archive_settings', '__return_false');
```


Getting a specfic post type term archive URL:

```
$post_type = 'book';
$term_id = 123;
$term_slug = 'rainbow-press';
$taxonomy = 'publisher';
$term_object = get_term( $term_id, $taxonomy );

// All these will return something like http://my-site.tld/book/publisher/rainbow-press
// note that when you pass the term as slug, you'll have to pass the taxonomy also.
// will return WP_Error if $post_type + $taxonomy has not been registered first.

$url = apply_filters( 'post_type_term_link', '', $post_type, $term_object );

$url = apply_filters( 'post_type_term_link', '', $post_type, $term_id );

$url = apply_filters( 'post_type_term_link', '', $post_type, $term_slug, $taxonomy );
```



ToDo
----

 - [ ] Add Archive URLs to WPSEO.
 - [ ] More Testing with different Polylang setups.
 - [ ] Refactor: put frontend nav menu to standalone singleton
