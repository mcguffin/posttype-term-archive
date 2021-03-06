<?php

/*
Plugin Name: PostType Term Archive
Plugin URI: https://github.com/mcguffin/posttype-term-archive
Description: Managing Wordpress PostType and Term Archives
Author: Jörn Lund
Version: 0.3.11
Github Plugin URI: mcguffin/posttype-term-archive
Author URI: https://github.com/mcguffin/
License: GPL3

Text Domain: posttype-term-archive
Domain Path: /languages/
*/

/*  Copyright 2017  Jörn Lund

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin was generated by WP Plugin Scaffold
https://github.com/mcguffin/wp-plugin-scaffold
Command line args were: `"PostType Term Archive" admin+css+js gulp git --force`
*/


namespace PosttypeTermArchive;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/api.php';

Core\Core::instance( __FILE__ );
Core\Archive::instance();

if ( is_admin() || defined( 'DOING_AJAX' ) ) {

	Admin\Admin::instance();
	Admin\NavMenuArchives::instance();
	Admin\NavMenuTermArchives::instance();

}
