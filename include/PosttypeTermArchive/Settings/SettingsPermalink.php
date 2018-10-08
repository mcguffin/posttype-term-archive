<?php
/**
 *	@package PosttypeTermArchive\Settings
 *	@version 1.0.0
 *	2018-09-22
 */

namespace PosttypeTermArchive\Settings;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use PosttypeTermArchive\Core;

class SettingsPermalink extends Settings {

	private $optionset = 'permalink';


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ), 11 );
		add_action( "load-options-permalink.php" , array( $this, 'enqueue_assets' ) );
		parent::__construct();
	}
	/**
	 *	Enqueue settings Assets
	 *
	 *	@action "settings_page_{$this->optionset}
	 */
	public function enqueue_assets() {
		$core = Core\Core::instance();
		wp_enqueue_style( "posttype-term-archives-settings-permalink", $core->get_asset_url( "css/admin/settings/permalink.css" ) );
	}


	/**
	 *	@action admin_init
	 */
	public function maybe_save_settings() {
		global $pagenow;

		if ( ! isset( $_POST[ 'posttype_term_archives' ] ) || $pagenow !== 'options-permalink.php' ) {
			return;
		}

		$value = $_POST[ 'posttype_term_archives' ];
		if ( ! is_array( $value ) ) {
			$value = trim( $value );
		}
		$value = wp_unslash( $value );

		update_option( 'posttype_term_archives', $value );
		$core = Core\Core::instance();


		//var_dump( $pagenow, current_action(), $options);exit();
	}

	/**
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {

		$settings_section	= 'posttype_term_archives';

		add_settings_section( $settings_section, __( 'Post Type Term Archives',  'posttype-term-archive' ), array( $this, 'section_description' ), $this->optionset );

		$option_name	= 'posttype_term_archives';
		add_option( $option_name, array(), '', true );

		register_setting( $this->optionset , $option_name, array( $this , 'sanitize_pt_term_archives' ) );

		foreach ( get_taxonomies() as $tax ) {
			$taxo = get_taxonomy($tax);
			if ( ! $taxo->public || $taxo->_builtin ) {
				continue;
			}
			foreach ( $taxo->object_type as $post_type ) {
				$archive = Core\Archive::maybe_get( $post_type, $tax );
				if ( $archive && ! $archive->show_in_settings ) {
					continue;
				}
				$defaults		= array(
					'enabled'		=> false,
					'show_in_menus'	=> true,
				);


				$pto = get_post_type_object($post_type);

				add_settings_field(
					rand(),
					sprintf( '%s %s Archive', $pto->labels->singular_name, $taxo->labels->name ),
					array( $this, 'select_posttype_term_archives' ),
					$this->optionset,
					$settings_section,
					array(
						'option_name'			=> $option_name,
						'option_label'			=> __( 'Setting #1',  'posttype-term-archive' ),
						'option_description'	=> __( 'Setting #1 description',  'posttype-term-archive' ),
						'post_type'				=> $pto,
						'taxonomy'				=> $taxo,
						'archive'				=> $archive,
						'class'					=> 'posttype-term-archive-setting',
					)
				);


			}
		}

		// more settings go here ...

	}

	/**
	 * Print some documentation for the optionset
	 */
	public function section_description( $args ) {
		?>
		<div class="inside">
			<p>
				<?php _e( 'Enable permalinks for post-type keyword combinations like Book Genres.' , 'posttype-term-archive' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Output Theme selectbox
	 */
	public function select_posttype_term_archives( $args ) {

		@list( $option_name, $label, $description, $pto, $taxo, $archive ) = array_values( $args );

		$option_values	= get_option( $option_name );
		$option_value	= isset( $option_values[$pto->name][$taxo->name] ) ? $option_values[$pto->name][$taxo->name] : array();

		$id 	= sprintf( '%s-%s-%s', $option_name, $pto->name, $taxo->name );
		$name	= sprintf( '%s[%s][%s]', $option_name, $pto->name, $taxo->name );
		if ( ! $archive ) {
			$archive = Core\Archive::get( $pto->name, $taxo->name );
		}
		?>
		<p>
			<input id="<?php echo $id ?>-enabled" type="checkbox" name="<?php echo $name ?>[enabled]" value="1" <?php checked( $option_value['enabled'], true, true ); ?> />
			<label for="<?php echo $id ?>-enabled">
				<?php _e('Enable','posttype-term-archives'); ?>
			</label>
			<input id="<?php echo $id ?>-show_in_menus" type="checkbox" name="<?php echo $name ?>[show_in_menus]" value="1" <?php checked( $option_value['show_in_menus'], true, true ); ?> />
			<label for="<?php echo $id ?>-show_in_menus">
				<?php _e('Show in Menus','posttype-term-archives'); ?>
			</label>
			
			<code>
				<?php
				// print example URL
				echo $archive->get_link( new \WP_Term( (object) array(
					'term_id'	=> 123,
					'name'		=> _x('Example Term','Term Name', 'posttype-term-archive'),
					'slug'		=> _x('example-term','Term Slug', 'posttype-term-archive'),
				)) );

				?>
			</code>
		</p>
		<?php

	}

	/**
	 * Sanitize value of setting_1
	 *
	 * @return string sanitized value
	 */
	public function sanitize_pt_term_archives( $value ) {
		foreach ( $value as $post_type => $taxonomies ) {
			foreach ( $taxonomies as $taxonomy => $settings ) {
				$value[ $post_type ][ $taxonomy ] = array_map( 'boolval', $settings ) + array(
					'enabled' 		=> false,
					'show_in_menus'	=> false,
				);
			}
		}
		// do sanitation here!
		return $value;
	}

}
