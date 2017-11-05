//********************************************************************************************************************************
// reusable message function
//********************************************************************************************************************************
function refrMessage( msgType, msgText ) {
	jQuery( 'div#wpbody h2:first' ).after( '<div id="message" class="' + msgType + ' below-h2"><p>' + msgText + '</p></div>' );
}

//********************************************************************************************************************************
// clear any admin messages
//********************************************************************************************************************************
function refrClearAdmin() {
	jQuery( 'div#wpbody div#message' ).remove();
	jQuery( 'div#wpbody div#setting-error-settings_updated' ).remove();
}

//********************************************************************************************************************************
// button action when disabled
//********************************************************************************************************************************
function refrBtnDisable( btnDiv, btnSpin, btnItem ) {
	jQuery( btnDiv ).find( btnSpin ).css( 'visibility', 'visible' );
	jQuery( btnDiv ).find( btnItem ).attr( 'disabled', 'disabled' );
}

//********************************************************************************************************************************
// button action when enabled
//********************************************************************************************************************************
function refrBtnEnable( btnDiv, btnSpin, btnItem ) {
	jQuery( btnDiv ).find( btnSpin ).css( 'visibility', 'hidden' );
	jQuery( btnDiv ).find( btnItem ).removeAttr( 'disabled' );
}

//********************************************************************************************************************************
// start the engine
//********************************************************************************************************************************
jQuery(document).ready( function($) {

//********************************************************************************************************************************
// quick helper to check for an existance of an element
//********************************************************************************************************************************
	$.fn.divExists = function(callback) {
		// slice some args
		var args = [].slice.call( arguments, 1 );
		// check for length
		if ( this.length ) {
			callback.call( this, args );
		}
		// return it
		return this;
	};

//********************************************************************************************************************************
// set some vars
//********************************************************************************************************************************
	var yrlSocial   = '';
	var yrlAdminBox = '';
	var yrlClickBox = '';
	var yrlClickRow = '';
	var yrlKeyword  = '';
	var yrlPostID   = '';
	var yrlNonce    = '';

//********************************************************************************************************************************
// social link in a new window because FANCY
//********************************************************************************************************************************
	$( 'div.refr-sidebox' ).on( 'click', 'a.admin-twitter-link', function() {
		// only do this on larger screens
		if ( $( window ).width() > 765 ) {
			// get our link
			yrlSocial = $( this ).attr( 'href' );
			// open our fancy window
			window.open( yrlSocial, 'social-share-dialog', 'width=626,height=436' );
			// and finish
			return false;
		}
	});

//********************************************************************************************************************************
// do the password magic
//********************************************************************************************************************************
	$( 'td.apikey-field-wrapper' ).divExists( function() {

		// hide it on load
		$( 'input#refr-api' ).hidePassword( false );

		// now check for clicks
		$( 'td.apikey-field-wrapper' ).on( 'click', 'span.password-toggle', function () {

			// if our password is not visible
			if ( ! $( this ).hasClass( 'password-visible' ) ) {
				$( this ).addClass( 'password-visible' );
				$( 'input#refr-api' ).showPassword( false );
			} else {
				$( this ).removeClass( 'password-visible' );
				$( 'input#refr-api' ).hidePassword( false );
			}

		});
	});

//********************************************************************************************************************************
// other external links in new tab
//********************************************************************************************************************************
	$( 'div.refr-sidebox' ).find( 'a.external' ).attr( 'target', '_blank' );

//********************************************************************************************************************************
// show / hide post types on admin
//********************************************************************************************************************************
	$( 'tr.setting-item-types' ).divExists( function() {

		// see if our box is checked
		yrlAdminBox = $( this ).find( 'input#refr-cpt' ).is( ':checked' );

		// if it is, show it
		if ( yrlAdminBox === true ) {
			$( 'tr.secondary' ).show();
		}

		// if not, hide it and make sure boxes are not checked
		if ( yrlAdminBox === false ) {
			$( 'tr.secondary' ).hide();
			$( 'tr.secondary' ).find( 'input:checkbox' ).prop( 'checked', false );
		}

		// now the check for clicking
		$( 'tr.setting-item-types' ).on( 'change', 'input#refr-cpt', function() {

			// check the box (again)
			yrlAdminBox = $( this ).is( ':checked' );

			// if it is, show it
			if ( yrlAdminBox === true ) {
				$( 'tr.secondary' ).fadeIn( 700 );
			}

			// if not, hide it and make sure boxes are not checked
			if ( yrlAdminBox === false ) {
				$( 'tr.secondary' ).fadeOut( 700 );
				$( 'tr.secondary' ).find( 'input:checkbox' ).prop( 'checked', false );
			}
		});

	});

//********************************************************************************************************************************
// create REFR on call
//********************************************************************************************************************************
	$( 'div#refr-post-display').on( 'click', 'input.refr-api', function () {

		// get my post ID and my nonce
		yrlPostID   = $( this ).data( 'post-id' );
		yrlNonce    = $( this ).data( 'nonce' );

		// bail without post ID or nonce
		if ( yrlPostID === '' || yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// adjust buttons
		$( 'div#refr-post-display' ).find( 'span.refr-spinner' ).css( 'visibility', 'visible' );
		$( 'div#refr-post-display' ).find( 'input.refr-api').attr( 'disabled', 'disabled' );

		// get my optional keyword
		yrlKeyword  = $( 'div#refr-post-display' ).find( 'input.refr-keyw' ).val();

		// set my data array
		var data = {
			action:  'create_refr',
			keyword: yrlKeyword,
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			$( 'div#refr-post-display' ).find( 'span.refr-spinner' ).css( 'visibility', 'hidden' );
			$( 'div#refr-post-display' ).find( 'input.refr-api').removeAttr( 'disabled' );

			var obj;

			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				refrMessage( 'error', refrAdmin.defaultError );
			}

			if( obj.success === true ) {
				refrMessage( 'updated', obj.message );
			}

			else if( obj.success === false && obj.message !== null ) {
				refrMessage( 'error', obj.message );
			}

			else {
				refrMessage( 'error', refrAdmin.defaultError );
			}

			// add in the new REFR box if it comes back
			if( obj.success === true && obj.linkbox !== null ) {

				// remove the submit box
				$( 'div#refr-post-display' ).find( 'p.refr-submit-block' ).remove();

				// swap out our boxes
				$( 'div#refr-post-display' ).find( 'p.refr-input-block' ).replaceWith( obj.linkbox );

				// add our shortlink button
				$( 'div#edit-slug-box' ).append( '<input type="hidden" value="' + obj.linkurl + '" id="shortlink">' );
				$( 'div#edit-slug-box' ).append( refrAdmin.shortSubmit );
			}
		});

	});

//********************************************************************************************************************************
// delete REFR on call
//********************************************************************************************************************************
	$( 'div#refr-post-display' ).on( 'click', 'span.refr-delete', function () {

		// get my post ID and nonce
		yrlPostID   = $( this ).data( 'post-id' );
		yrlNonce    = $( this ).data( 'nonce' );

		// bail without post ID or nonce
		if ( yrlPostID === '' || yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// set my data array
		var data = {
			action:  'delete_refr',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				refrMessage( 'error', refrAdmin.defaultError );
			}

			if( obj.success === true ) {
				refrMessage( 'updated', obj.message );
			}

			else if( obj.success === false && obj.message !== null ) {
				refrMessage( 'error', obj.message );
			}
			else {
				refrMessage( 'error', refrAdmin.defaultError );
			}

			// add in the new REFR box if it comes back
			if( obj.success === true && obj.refrbox !== null ) {

				$( 'div#refr-post-display' ).find( 'p.howto' ).remove();
				$( 'div#refr-post-display' ).find( 'p.refr-exist-block' ).replaceWith( obj.linkbox );

				$( 'div#edit-slug-box' ).find( 'input#shortlink' ).remove();
				$( 'div#edit-slug-box' ).find( 'a:contains("Get Shortlink")' ).remove();
			}
		});

	});

//********************************************************************************************************************************
// update REFR click count
//********************************************************************************************************************************
	$( 'div.row-actions' ).on( 'click', 'a.refr-admin-update', function (e) {

		// stop the hash
		e.preventDefault();

		// get my nonce
		yrlNonce    = $( this ).data( 'nonce' );

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// get my post ID
		yrlPostID   = $( this ).data( 'post-id' );

		// bail without post ID
		if ( yrlPostID === '' ) {
			return;
		}

		// set my row and box as a variable for later
		yrlClickRow = $( this ).parents( 'div.row-actions' );
		yrlClickBox = $( this ).parents( 'tr.entry' ).find( 'td.refr-click' );

		// set my data array
		var data = {
			action:  'stats_refr',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// hide the row actions
			yrlClickRow.removeClass( 'visible' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}

			// add in the new number box if it comes back
			if( obj.success === true && obj.clicknm !== null ) {
				yrlClickBox.find( 'span' ).text( obj.clicknm );
			}
		});
	});

//********************************************************************************************************************************
// create REFR inline
//********************************************************************************************************************************
	$( 'div.row-actions' ).on( 'click', 'a.refr-admin-create', function (e) {

		// stop the hash
		e.preventDefault();

		// get my nonce
		yrlNonce    = $( this ).data( 'nonce' );

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// get my post ID
		yrlPostID   = $( this ).data( 'post-id' );

		// bail without post ID
		if ( yrlPostID === '' ) {
			return;
		}

		// set my row and box as a variable for later
		yrlClickRow = $( this ).parents( 'div.row-actions' );
		yrlClickBox = $( this ).parents( 'div.row-actions' ).find( 'span.create-refr' );

		// set my data array
		var data = {
			action:  'inline_refr',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// hide the row actions
			yrlClickRow.removeClass( 'visible' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}

			// add in the new click box if it comes back
			if( obj.success === true && obj.rowactn !== null ) {
				yrlClickBox.replaceWith( obj.rowactn );
			}
		});
	});

//********************************************************************************************************************************
// run API status update update from admin
//********************************************************************************************************************************
	$( 'div#refr-admin-status' ).on( 'click', 'input.refr-click-status', function () {

		// get my nonce first
		yrlNonce    = $( 'input#refr_status' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		refrClearAdmin();

		// adjust buttons
		refrBtnDisable( 'div#refr-admin-status', 'span.refr-status-spinner', 'input.refr-click-status' );

		// set my data array
		var data = {
			action:  'status_refr',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			refrBtnEnable( 'div#refr-admin-status', 'span.refr-status-spinner', 'input.refr-click-status' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				refrMessage( 'error', refrAdmin.defaultError );
			}

			// we got a status back
			if( obj.success === true ) {

				// check the icon
				if( obj.baricon !== '' ) {
					$( 'div#refr-admin-status' ).find( 'span.api-status-icon' ).replaceWith( obj.baricon );
				}

				// check the text return
				if( obj.message !== '' ) {
					$( 'div#refr-admin-status' ).find( 'p.api-status-text' ).text( obj.message );
				}

				// check the checkmark return
				if( obj.stcheck !== '' ) {
					// add the checkmark
					$( 'div#refr-admin-status' ).find( 'p.api-status-actions' ).append( obj.stcheck );
					// delay then fade out
					$( 'span.api-status-checkmark' ).delay( 3000 ).fadeOut( 1000 );
				}

			}
			else if( obj.success === false && obj.message !== null ) {
				refrMessage( 'error', obj.message );
			}
			else {
				refrMessage( 'error', refrAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// run click update from admin
//********************************************************************************************************************************
	$( 'div#refr-data-refresh' ).on( 'click', 'input.refr-click-updates', function () {

		// get my nonce first
		yrlNonce    = $( 'input#refr_refresh' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		refrClearAdmin();

		// adjust buttons
		refrBtnDisable( 'div#refr-data-refresh', 'span.refr-refresh-spinner', 'input.refr-click-updates' );

		// set my data array
		var data = {
			action:  'refresh_refr',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			refrBtnEnable( 'div#refr-data-refresh', 'span.refr-refresh-spinner', 'input.refr-click-updates' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				refrMessage( 'error', refrAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				refrMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				refrMessage( 'error', obj.message );
			}
			else {
				refrMessage( 'error', refrAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// attempt data import
//********************************************************************************************************************************
	$( 'div#refr-data-refresh' ).on( 'click', 'input.refr-click-import', function () {

		// get my nonce first
		yrlNonce    = $( 'input#refr_import' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		refrClearAdmin();

		// adjust buttons
		refrBtnDisable( 'div#refr-data-refresh', 'span.refr-import-spinner', 'input.refr-click-import' );

		// set my data array
		var data = {
			action:  'import_refr',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			refrBtnEnable( 'div#refr-data-refresh', 'span.refr-import-spinner', 'input.refr-click-import' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				refrMessage( 'error', refrAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				refrMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				refrMessage( 'error', obj.message );
			}
			else {
				refrMessage( 'error', refrAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// change meta key from old plugin
//********************************************************************************************************************************
	$( 'div#refr-data-refresh' ).on( 'click', 'input.refr-convert', function () {

		// get my nonce first
		yrlNonce    = $( 'input#refr_convert' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		refrClearAdmin();

		// adjust buttons
		refrBtnDisable( 'div#refr-data-refresh', 'span.refr-convert-spinner', 'input.refr-convert' );

		// set my data array
		var data = {
			action:  'convert_refr',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			refrBtnEnable( 'div#refr-data-refresh', 'span.refr-convert-spinner', 'input.refr-convert' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				refrMessage( 'error', refrAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				refrMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				refrMessage( 'error', obj.message );
			}
			else {
				refrMessage( 'error', refrAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// you're still here? it's over. go home.
//********************************************************************************************************************************
});
