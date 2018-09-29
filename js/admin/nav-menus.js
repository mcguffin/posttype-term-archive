(function($){
	$( document ).ready( function() {
		 $( '#submit-post-type-term-archives' ).click( function( event ) {

			event.preventDefault();

			var $list_items = $( '#' + pt_term_archives.metabox_list_id + ' li :checked' ),
				$submit = $( this );

			// Get checked boxes
			var terms = [];
			$list_items.each( function() {
				terms.push( $( this ).val() );
			} );

			// Show spinner
			$( '#' + pt_term_archives.metabox_id ).find('.spinner').show();

			// Disable button
			$submit.prop( 'disabled', true );

			// Send checked post types with our action, and nonce
			$.post( pt_term_archives.ajaxurl, {
					action: pt_term_archives.action,
					posttypearchive_nonce: pt_term_archives.nonce,
					post_type_terms: terms,
					nonce: pt_term_archives.nonce
				},

				// AJAX returns html to add to the menu, hide spinner, remove checks
				function( response ) {
					$( '#menu-to-edit' ).append( response );
					$( '#' + pt_term_archives.metabox_id ).find('.spinner').hide();
					$list_items.prop("checked", false);
					$submit.prop( 'disabled', false );
				}
			);
		});
	});
})(jQuery)
