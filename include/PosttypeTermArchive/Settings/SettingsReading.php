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

class SettingsReading extends Settings {

	private $optionset = 'reading';


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		add_action( "load-options-rading.php" , array( $this, 'enqueue_assets' ) );
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
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {

		$settings_section	= 'posttype_term_archives';

		add_settings_section( $settings_section, __( 'Post Type Archives',  'posttype-term-archive' ), array( $this, 'section_description' ), $this->optionset );

		$option_name	= 'posttype_archives';
		add_option( $option_name, array(), '', true );


		$post_types = get_post_types( array(
			'_builtin'	=> false,
			'has_archive'	=> true,
		));
		$option_name = 'post_type_archive_pages';
		register_setting( $this->optionset , $option_name, 'sanitize_pt_archive_page' );

		foreach ( $post_types as $post_type ) {
			$pto = get_post_type_object( $post_type );
			add_settings_field(
				$option_name . '-',
				sprintf( 'Page for %s posts', $pto->labels->singular_name ),
				array( $this, 'select_posttype_archive_page' ),
				$this->optionset,
				$settings_section,
				array(
					'option_name'			=> $option_name,
					'post_type'				=> $pto,
				)
			);
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
	public function select_posttype_archive_page( $args ) {

		@list( $option_name, $pto ) = array_values( $args );
		$option = get_option( $option_name );

		$dropdown_pages_args = array(
			'selected'	=> isset($option[ $pto->name ]) ? $option[ $pto->name ] : 0,
			'echo'		=> true,
			'name'		=> sprintf( '%s[%s]', $option_name, $pto->name ),
			'show_option_none'	=> __('– Select –','posttype-term-archive'),
			'exclude'	=> array( get_option('page_on_front'), get_option('page_for_posts') ),
		);

		wp_dropdown_pages( $dropdown_pages_args );

	}
	/**
	 *	@filter sanitize_option_post_type_archive_pages
	 */
	public function sanitize_pt_archive_page( $value ) {
		$unique_value = array_unique( $value );
		if ( $unique_value != $value ) {
			add_settings_error(__( 'The page is alread archive for an other post type', 'posttype-term-archive' ));
		}

		return $unique_value;
	}

}
