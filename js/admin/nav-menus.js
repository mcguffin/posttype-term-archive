(function($){

	$( document ).ready(function(){
		$( '.add-post-type-term-menu-item').click( function(e) {

			e.preventDefault();

			var $submit = $(this),
				$list_items = $submit.closest('.inside').find('[type="checkbox"]:checked'),
				$spinner = $submit.next('.spinner').show(),
				terms = [];

			$list_items.each( function() {
				terms.push( $( this ).val() );
			});

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
					$spinner.hide();
					$list_items.prop("checked", false);
					$submit.prop( 'disabled', false );
				}
			);
		});
	});
})(jQuery)
