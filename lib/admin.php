<?php
/**
 * REFR Shortlinks - Admin Module
 *
 * Contains admin related functions
 *
 * @package REFR Shortlinks
 */
/*  Copyright 2015 Reaktiv Studios

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'REFRCreator_Admin' ) ) {

// Start up the engine
class REFRCreator_Admin
{

	/**
	 * This is our constructor
	 *
	 * @return REFRCreator_Admin
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts',        array( $this, 'scripts_styles'      ),  10      );
		add_action( 'add_meta_boxes',               array( $this, 'refr_metabox'      ),  11      );
		add_action( 'save_post',                    array( $this, 'refr_keyword'      )           );
		add_action( 'save_post',                    array( $this, 'refr_on_save'      )           );
		add_action( 'manage_posts_custom_column',   array( $this, 'display_columns'     ),  10, 2   );
		add_filter( 'manage_posts_columns',         array( $this, 'register_columns'    )           );
		add_filter( 'post_row_actions',             array( $this, 'refr_row_action'   ),  10, 2   );
		add_filter( 'page_row_actions',             array( $this, 'refr_row_action'   ),  10, 2   );
	}

	/**
	 * scripts and stylesheets
	 *
	 * @param  [type] $hook [description]
	 * @return [type]       [description]
	 */
	public function scripts_styles( $hook ) {

		// bail if not on the right part
		if ( ! in_array( $hook, array( 'settings_page_refr-settings', 'edit.php', 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// set our JS and CSS prefixes
		$css_sx = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.css' : '.min.css';
		$js_sx  = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.js' : '.min.js';

		// load the password stuff on just the settings page
		if ( $hook == 'settings_page_refr-settings' ) {
			wp_enqueue_script( 'hideshow', plugins_url( '/js/hideShowPassword' . $js_sx, __FILE__ ) , array( 'jquery' ), '2.0.3', true );
		}

		// load our files
		wp_enqueue_style( 'refr-admin', plugins_url( '/css/refr-admin' . $css_sx, __FILE__ ), array(), YOURS_VER, 'all' );
		wp_enqueue_script( 'refr-admin', plugins_url( '/js/refr-admin' . $js_sx, __FILE__ ) , array( 'jquery' ), YOURS_VER, true );
		wp_localize_script( 'refr-admin', 'refrAdmin', array(
			'shortSubmit'   => '<a onclick="prompt(\'URL:\', jQuery(\'#shortlink\').val()); return false;" class="button button-small" href="#">' . __( 'Get Shortlink' ) . '</a>',
			'defaultError'  => __( 'There was an error with your request.' )
		));
	}

	/**
	 * call the metabox if on an appropriate
	 * post type and post status
	 *
	 * @return [type] [description]
	 */
	public function refr_metabox() {

		// fetch the global post object
		global $post;

		// make sure we're working with an approved post type
		if ( ! in_array( $post->post_type, REFRCreator_Helper::get_refr_types() ) ) {
			return;
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			return;
		}

		// only fire if user has the option
		if(	false === $check = REFRCreator_Helper::check_refr_cap() ) {
			return;
		}

		// now add the meta box
		add_meta_box( 'refr-post-display', __( 'REFR Shortlink', 'wprefr' ), array( __class__, 'refr_post_display' ), $post->post_type, 'side', 'high' );
	}

	/**
	 * Display REFR shortlink if present
	 *
	 * @param  [type] $post [description]
	 * @return [type]       [description]
	 */
	public static function refr_post_display( $post ) {

		// cast our post ID
		$post_id    = absint( $post->ID );

		// check for a link and click counts
		$link   = REFRCreator_Helper::get_refr_meta( $post_id, '_refr_url' );

		// if we have no link, display our box
		if ( empty( $link ) ) {

			// display the box
			echo REFRCreator_Helper::get_refr_subbox( $post_id );

			// and return
			return;
		}

		// we have a shortlink. show it along with the count
		if( ! empty( $link ) ) {

			// get my count
			$count  = REFRCreator_Helper::get_refr_meta( $post_id, '_refr_clicks', '0' );

			// and echo the box
			echo REFRCreator_Helper::get_refr_linkbox( $link, $post_id, $count );
		}
	}

	/**
	 * our check for a custom REFR keyword
	 *
	 * @param  integer $post_id [description]
	 *
	 * @return void
	 */
	public function refr_keyword( $post_id ) {

		// run various checks to make sure we aren't doing anything weird
		if ( REFRCreator_Helper::meta_save_check( $post_id ) ) {
			return;
		}

		// make sure we're working with an approved post type
		if ( ! in_array( get_post_type( $post_id ), REFRCreator_Helper::get_refr_types() ) ) {
			return;
		}

		// we have a keyword and we're going to store it
		if( ! empty( $_POST['refr-keyw'] ) ) {

			// sanitize it
			$keywd  = REFRCreator_Helper::prepare_api_keyword( $_POST['refr-keyw'] );

			// update the post meta
			update_post_meta( $post_id, '_refr_keyword', $keywd );
		} else {
			// delete it if none was passed
			delete_post_meta( $post_id, '_refr_keyword' );
		}
	}

	/**
	 * Create refr link on publish if one doesn't exist
	 *
	 * @param  integer $post_id [description]
	 *
	 * @return void
	 */
	public function refr_on_save( $post_id ) {

		// bail if this is an import since it'll potentially mess up the process
		if ( ! empty( $_POST['import_id'] ) ) {
			return;
		}

		// run various checks to make sure we aren't doing anything weird
		if ( REFRCreator_Helper::meta_save_check( $post_id ) ) {
			return;
		}

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), REFRCreator_Helper::get_refr_status( 'save' ) ) ) {
			return;
		}

		// make sure we're working with an approved post type
		if ( ! in_array( get_post_type( $post_id ), REFRCreator_Helper::get_refr_types() ) ) {
			return;
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			return;
		}

		// bail if user hasn't checked the box
		if ( false === $onsave = REFRCreator_Helper::get_refr_option( 'sav' ) ) {
		   	return;
		}

		// check for a link and bail if one exists
		if ( false !== $exist = REFRCreator_Helper::get_refr_meta( $post_id ) ) {
			return;
		}

		// get my post URL and title
		$url    = REFRCreator_Helper::prepare_api_link( $post_id );
		$title  = get_the_title( $post_id );

		// and optional keyword
		$keywd  = ! empty( $_POST['refr-keyw'] ) ? REFRCreator_Helper::prepare_api_keyword( $_POST['refr-keyw'] ) : '';

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// make the API call
		$build  = REFRCreator_Helper::run_refr_api_call( 'shorturl', $args );

		// bail if empty data or error received
		if ( empty( $build ) || false === $build['success'] ) {
			return;
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_refr_url', $shorturl );
			update_post_meta( $post_id, '_refr_clicks', '0' );

			// do the action after saving
			do_action( 'refr_after_url_save', $post_id, $shorturl );
		}
	}

	/**
	 * the custom display columns for click counts
	 *
	 * @param  [type] $column_name [description]
	 * @param  [type] $post_id     [description]
	 * @return [type]              [description]
	 */
	public function display_columns( $column, $post_id ) {

		// start my column output
		switch ( $column ) {

		case 'refr-click':

			echo '<span>' . REFRCreator_Helper::get_refr_meta( $post_id, '_refr_clicks', '0' ) . '</span>';

			break;

		// end all case breaks
		}
	}

	/**
	 * register and display columns
	 *
	 */
	public function register_columns( $columns ) {

		// call the global post type object
		global $post_type_object;

		// make sure we're working with an approved post type
		if ( ! in_array( $post_type_object->name, REFRCreator_Helper::get_refr_types() ) ) {
			return $columns;
		}

		// get display for column icon
		$columns['refr-click'] = '<span title="' . __( 'REFR Clicks', 'wprefr' ) . '" class="dashicons dashicons-editor-unlink"></span>';

		// return the columns
		return $columns;
	}

	/**
	 * the action row link based on the status
	 *
	 * @param  [type] $actions [description]
	 * @param  [type] $post    [description]
	 * @return [type]          [description]
	 */
	public function refr_row_action( $actions, $post ) {

		// make sure we're working with an approved post type
		if ( ! in_array( $post->post_type, REFRCreator_Helper::get_refr_types() ) ) {
			return $actions;
		}

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post->ID ), REFRCreator_Helper::get_refr_status() ) ) {
			return $actions;
		}

		// check for existing and add our new action
		if ( false === $exist = REFRCreator_Helper::get_refr_meta( $post->ID ) ) {
			$actions['create-refr'] = REFRCreator_Helper::create_row_action( $post->ID );
		} else {
			$actions['update-refr'] = REFRCreator_Helper::update_row_action( $post->ID );
		}

		// return the actions
		return $actions;
	}

// end class
}

// end exists check
}

// Instantiate our class
new REFRCreator_Admin();

