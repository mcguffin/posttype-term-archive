<?php

namespace PosttypeTermArchive\Admin;

use PosttypeTermArchive\Core;
use PosttypeTermArchive\Settings;


class Admin extends Core\Singleton {

	private $core;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->archive = Core\TermArchive::instance();

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded'), 0xffffffff );

		add_filter( 'display_post_states', array( $this, 'post_states'), 10, 2 );

	}

	/**
	 *	@filter display_post_states
	 */
	public function post_states( $states, $post ) {
		if ( $post_type = $this->archive->is_archive_page( $post->ID ) ) {
			$pto = get_post_type_object( $post_type );
			$states[] = sprintf( __('Page for %s', 'posttype-term-archive' ), $pto->labels->name );
		}
		return $states;
	}

	/**
	 *	@action plugins_loaded (late)
	 */
	public function plugins_loaded() {

		if ( ! apply_filters( 'posttype_term_archive_settings', true ) ) {
			return;
		}

		Settings\SettingsPermalink::instance();
		Settings\SettingsReading::instance();
	}


}
