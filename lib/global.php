<?php
/**
 * REFR Shortlinks - Global Module
 *
 * Contains functions and options that involve both front and back
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

if ( ! class_exists( 'REFRCreator_Global' ) ) {

// Start up the engine
class REFRCreator_Global
{

	/**
	 * This is our constructor
	 *
	 * @return REFRCreator_Global
	 */
	public function __construct() {
		add_filter( 'pre_get_shortlink',            array( $this, 'shortlink_button'    ),  2,  2   );
		add_filter( 'get_shortlink',                array( $this, 'refr_shortlink'    ),  10, 3   );
		add_action( 'transition_post_status',       array( $this, 'refr_on_publish'   ),  10, 3   );
		add_action( 'publish_future_post',          array( $this, 'refr_on_schedule'  ),  10      );

		// our two cron jobs
		add_action( 'refr_cron',                  array( $this, 'refr_click_cron'   )           );
		add_action( 'refr_test',                  array( $this, 'refr_test_cron'    )           );
	}

	/**
	 * hijack the normal shortlink button and
	 * use ours instead
	 *
	 * @param  [type] $shortlink [description]
	 * @param  [type] $id        [description]
	 * @return [type]            [description]
	 */
	public function shortlink_button( $shortlink, $id ) {

		// bail if the setting isn't enabled
		if(	false === $enabled = REFRCreator_Helper::get_refr_option( 'sht' ) ) {
			return $shortlink;
		}

		// check existing postmeta for REFR
		$custom = REFRCreator_Helper::get_refr_meta( $id );

		// return the custom REFR link or the regular one
		return ! empty( $custom ) ? $custom : $shortlink;
	}

	/**
	 * Filter wp_shortlink with new REFR link
	 *
	 * @param  [type] $shortlink [description]
	 * @param  [type] $id        [description]
	 * @param  [type] $context   [description]
	 * @return [type]            [description]
	 */
	public function refr_shortlink( $shortlink, $id, $context ) {

		// no shortlinks exist on non-singular items, so bail
		if ( ! is_singular() ) {
			return;
		}

		// Look for the post ID passed by wp_get_shortlink() first
		if ( empty( $id ) ) {

			// call the global post object
			global $post;

			// and get the ID
			$id = absint( $post->ID );
		}

		// Fall back in case we still don't have a post ID
		if ( empty( $id ) ) {
			return ! empty( $shortlink ) ? $shortlink : false;
		}

		// check existing postmeta for REFR
		$custom = REFRCreator_Helper::get_refr_meta( $id );

		// return the custom REFR link or the regular one
		return ! empty( $custom ) ? $custom : $shortlink;
	}

	/**
	 * generate a REFR link when a post is
	 * manually moved from future to publish
	 *
	 * @param  [type] $new_status [description]
	 * @param  [type] $old_status [description]
	 * @param  [type] $post       [description]
	 * @return [type]             [description]
	 */
	public function refr_on_publish( $new_status, $old_status, $post ) {

		// we only want to handle items going from 'future' to 'publish'
		if ( 'future' == $old_status && 'publish' == $new_status ) {
        	REFRCreator_Helper::get_single_shorturl( $post->ID, 'sch' );
		}
	}

	/**
	 * generate a REFR link when a post is
	 * automatically moved from future to publish
	 *
	 * @param  [type] $post_id [description]
	 * @return [type]          [description]
	 */
	public function refr_on_schedule( $post_id ) {
		REFRCreator_Helper::get_single_shorturl( $post_id, 'sch' );
	}

	/**
	 * run update job to get click counts via cron
	 *
	 * @return void
	 */
	public function refr_click_cron() {

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			return;
		}

		// fetch the IDs that contain a REFR url meta key
		$items  = REFRCreator_Helper::get_refr_post_ids();

		// bail if none are present
		if ( empty( $items ) ) {
			return false;
		}

		// loop the IDs
		foreach ( $items as $item_id ) {

			// get my click number
			$clicks = REFRCreator_Helper::get_single_click_count( $item_id );

			// and update my meta
			if ( ! empty( $clicks['clicknm'] ) ) {
				update_post_meta( $item_id, '_refr_clicks', $clicks['clicknm'] );
			}
		}
	}

	/**
	 * run a daily test to make sure the API is available
	 *
	 * @return void
	 */
	public function refr_test_cron() {

		// bail if the API key or URL have not been entered
		if(	false === $api = REFRCreator_Helper::get_refr_api_data() ) {
			return;
		}

		// make the API call
		$build  = REFRCreator_Helper::run_refr_api_call( 'db-stats', array(), false );

		// handle the check and set it
		$check  = ! empty( $build ) && false !== $build['success'] ? 'connect' : 'noconnect';

		// set the option return
		if ( false !== get_option( 'refr_api_test' ) ) {
			update_option( 'refr_api_test', $check );
		} else {
			add_option( 'refr_api_test', $check, null, 'no' );
		}
	}

// end class
}

// end exists check
}

// Instantiate our class
new REFRCreator_Global();

