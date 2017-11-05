<?php
/**
 * REFR Shortlinks - Ajax Module
 *
 * Contains our ajax related functions
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

if ( ! class_exists( 'REFRCreator_Ajax' ) ) {

// Start up the engine
class REFRCreator_Ajax
{

	/**
	 * This is our constructor
	 *
	 * @return REFRCreator_Ajax
	 */
	public function __construct() {
		add_action( 'wp_ajax_create_refr',        array( $this, 'create_refr'       )           );
		add_action( 'wp_ajax_delete_refr',        array( $this, 'delete_refr'       )           );
		add_action( 'wp_ajax_stats_refr',         array( $this, 'stats_refr'        )           );
		add_action( 'wp_ajax_inline_refr',        array( $this, 'inline_refr'       )           );
		add_action( 'wp_ajax_status_refr',        array( $this, 'status_refr'       )           );
		add_action( 'wp_ajax_refresh_refr',       array( $this, 'refresh_refr'      )           );
		add_action( 'wp_ajax_convert_refr',       array( $this, 'convert_refr'      )           );
		add_action( 'wp_ajax_import_refr',        array( $this, 'import_refr'       )           );
	}

	/**
	 * Create shortlink function
	 */
	public function create_refr() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'refr_editor_create', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), REFRCreator_Helper::get_refr_status() ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'INVALID_STATUS';
			$ret['message'] = __( 'This is not a valid post status.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a URL
		if ( false !== $link = REFRCreator_Helper::get_refr_meta( $post_id, '_refr_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'URL_EXISTS';
			$ret['message'] = __( 'A URL already exists.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a permalink
		if ( false === $url = REFRCreator_Helper::prepare_api_link( $post_id ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_PERMALINK';
			$ret['message'] = __( 'No permalink could be retrieved.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// check for keyword and get the title
		$keyword = ! empty( $_POST['keyword'] ) ? REFRCreator_Helper::prepare_api_keyword( $_POST['keyword'] ) : '';
		$title   = get_the_title( $post_id );

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keyword );

		// make the API call
		$build  = REFRCreator_Helper::run_refr_api_call( 'shorturl', $args );

		// bail if empty data
		if ( empty( $build ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $build['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_refr_url', $shorturl );
			update_post_meta( $post_id, '_refr_clicks', '0' );

			// and do the API return
			$ret['success'] = true;
			$ret['message'] = __( 'You have created a new REFR link.', 'wprefr' );
			$ret['linkurl'] = $shorturl;
			$ret['linkbox'] = REFRCreator_Helper::get_refr_linkbox( $shorturl, $post_id );
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wprefr' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Delete shortlink function
	 */
	public function delete_refr() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'refr_editor_delete', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// do a quick check for a URL
		if ( false === $link = REFRCreator_Helper::get_refr_meta( $post_id, '_refr_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_URL_EXISTS';
			$ret['message'] = __( 'There is no URL to delete.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// passed it all. go forward
		delete_post_meta( $post_id, '_refr_url' );
		delete_post_meta( $post_id, '_refr_clicks' );

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'You have removed your REFR link.', 'wprefr' );
		$ret['linkbox'] = REFRCreator_Helper::get_refr_subbox( $post_id );
		echo json_encode( $ret );
		die();
	}

	/**
	 * retrieve stats
	 */
	public function stats_refr() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// verify our nonce
		$check	= check_ajax_referer( 'refr_inline_update_' . absint( $post_id ), 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// get my click number
		$clicks = REFRCreator_Helper::get_single_click_count( $post_id );

		// bad API call
		if ( empty( $clicks['success'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = $clicks['errcode'];
			$ret['message'] = $clicks['message'];
			echo json_encode( $ret );
			die();
		}

		// got it. update the meta
		update_post_meta( $post_id, '_refr_clicks', $clicks['clicknm'] );

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'Your REFR click count has been updated', 'wprefr' );
		$ret['clicknm'] = $clicks['clicknm'];
		echo json_encode( $ret );
		die();
	}

	/**
	 * Create shortlink function inline. Called on ajax
	 */
	public function inline_refr() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), REFRCreator_Helper::get_refr_status() ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'INVALID_STATUS';
			$ret['message'] = __( 'This is not a valid post status.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'refr_inline_create_' . absint( $post_id ), 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a URL
		if ( false !== $link = REFRCreator_Helper::get_refr_meta( $post_id, '_refr_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'URL_EXISTS';
			$ret['message'] = __( 'A URL already exists.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a permalink
		if ( false === $url = REFRCreator_Helper::prepare_api_link( $post_id ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_PERMALINK';
			$ret['message'] = __( 'No permalink could be retrieved.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// get my post URL and title
		$title  = get_the_title( $post_id );

		// check for a keyword
		$keywd  = REFRCreator_Helper::get_refr_keyword( $post_id );

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// make the API call
		$build  = REFRCreator_Helper::run_refr_api_call( 'shorturl', $args );

		// bail if empty data
		if ( empty( $build ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $build['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_refr_url', $shorturl );
			update_post_meta( $post_id, '_refr_clicks', '0' );

			// and do the API return
			$ret['success'] = true;
			$ret['message'] = __( 'You have created a new REFR link.', 'wprefr' );
			$ret['rowactn'] = '<span class="update-refr">' . REFRCreator_Helper::update_row_action( $post_id ) . '</span>';
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wprefr' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * run the status check on call
	 */
	public function status_refr() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'refr_status_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// make the API call
		$build  = REFRCreator_Helper::run_refr_api_call( 'db-stats' );

		// handle the check and set it
		$check  = ! empty( $build ) && false !== $build['success'] ? 'connect' : 'noconnect';

		// set the option return
		if ( false !== get_option( 'refr_api_test' ) ) {
			update_option( 'refr_api_test', $check );
		} else {
			add_option( 'refr_api_test', $check, null, 'no' );
		}

		// now get the API data
		$data	= REFRCreator_Helper::get_api_status_data();

		// check to see if no data happened
		if( empty( $data ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_STATUS_DATA';
			$ret['message'] = __( 'The status of the REFR API could not be determined.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// if we have data, send back things
		if(	! empty( $data ) ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['baricon'] = $data['icon'];
			$ret['message'] = $data['text'];
			$ret['stcheck'] = '<span class="dashicons dashicons-yes api-status-checkmark"></span>';
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wprefr' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * run update job to get click counts via manual ajax
	 */
	public function refresh_refr() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'refr_refresh_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// fetch the IDs that contain a REFR url meta key
		if ( false === $items = REFRCreator_Helper::get_refr_post_ids() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_IDS';
			$ret['message'] = __( 'There are no items with stored URLs.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// loop the IDs
		foreach ( $items as $item_id ) {

			// get my click number
			$clicks = REFRCreator_Helper::get_single_click_count( $item_id );

			// bad API call
			if ( empty( $clicks['success'] ) ) {
				$ret['success'] = false;
				$ret['errcode'] = $clicks['errcode'];
				$ret['message'] = $clicks['message'];
				echo json_encode( $ret );
				die();
			}

			// got it. update the meta
			update_post_meta( $item_id, '_refr_clicks', $clicks['clicknm'] );
		}

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'The click counts have been updated', 'wprefr' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * convert from Ozh (and Otto's) plugin
	 */
	public function convert_refr() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'refr_convert_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// filter our key to replace
		$key = apply_filters( 'refr_key_to_convert', 'refr_shorturl' );

		// fetch the IDs that contain a REFR url meta key
		if ( false === $items = REFRCreator_Helper::get_refr_post_ids( $key ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_KEYS';
			$ret['message'] = __( 'There are no meta keys to convert.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// set up SQL query
		global $wpdb;

		// prepare my query
		$setup  = $wpdb->prepare("
			UPDATE $wpdb->postmeta
			SET    meta_key = '%s'
			WHERE  meta_key = '%s'
			",
			esc_sql( '_refr_url' ), esc_sql( $key )
		);

		// run SQL query
		$query = $wpdb->query( $setup );

		// start our return
		$ret = array();

		// no matches, return message
		if( $query == 0 ) {
			$ret['success'] = false;
			$ret['errcode'] = 'KEY_MISSING';
			$ret['message'] = __( 'There are no keys matching this criteria. Please try again.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// we had matches. return the success message with a count
		if( $query > 0 ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['updated'] = $query;
			$ret['message'] = sprintf( _n( '%d key has been updated.', '%d keys have been updated.', $query, 'wprefr' ), $query );
			echo json_encode( $ret );
			die();
		}
	}

	/**
	 * check the REFR install for existing links
	 * and pull the data if it exists
	 */
	public function import_refr() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'refr_import_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// set my args for the API call
		$args   = array( 'filter' => 'top', 'limit' => apply_filters( 'refr_import_limit', 999 ) );

		// make the API call
		$fetch  = REFRCreator_Helper::run_refr_api_call( 'stats', $args );

		// bail if empty data
		if ( empty( $fetch ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $fetch['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( empty( $fetch['data']['links'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_LINKS';
			$ret['message'] = __( 'There was no available link data to import.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// filter the incoming for matching links
		$filter = REFRCreator_Helper::filter_refr_import( $fetch['data']['links'] );

		// bail error received
		if ( empty( $filter ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_MATCHING_LINKS';
			$ret['message'] = __( 'There were no matching links to import.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// set a false flag
		$error  = false;

		// now filter them
		foreach ( $filter as $item ) {

			// do the import
			$import = REFRCreator_Helper::maybe_import_link( $item );

			// bail error received
			if ( empty( $import ) ) {
				$error  = true;
				break;
			}
		}

		// bail if we had true on the import
		if ( true === $error ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_IMPORT_ACTION';
			$ret['message'] = __( 'The data could not be imported.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// hooray. it worked. do the ajax return
		if ( false === $error ) {
			$ret['success'] = true;
			$ret['message'] = __( 'All available REFR data has been imported.', 'wprefr' );
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wprefr' );
		echo json_encode( $ret );
		die();
	}

// end class
}

// end exists check
}

// Instantiate our class
new REFRCreator_Ajax();

