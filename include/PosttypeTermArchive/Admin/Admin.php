<?php

namespace PosttypeTermArchive\Admin;
use PosttypeTermArchive\Core;


class Admin extends Core\Singleton {

	private $core;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'admin_init', array( $this , 'admin_init' ) );
	}


	/**
	 * Admin init
	 */
	function admin_init() {
	}

	/**
	 * Enqueue options Assets
	 */
	function enqueue_assets() {
		wp_enqueue_style( 'posttype_term_archive-admin' , $this->core->get_asset_url( '/css/admin.css' ) );

		wp_enqueue_script( 'posttype_term_archive-admin' , $this->core->get_asset_url( 'js/admin.js' ) );
		wp_localize_script('posttype_term_archive-admin' , 'posttype_term_archive_admin' , array(
		) );
	}

}

