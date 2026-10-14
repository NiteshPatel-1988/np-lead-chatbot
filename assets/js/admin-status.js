( function () {
	'use strict';

	var config = window.npleadchatStatus;
	if ( ! config ) {
		return;
	}

	document.addEventListener( 'change', function ( event ) {
		var select = event.target;
		if ( ! select.classList || ! select.classList.contains( 'npleadchat-status-select' ) ) {
			return;
		}

		var body = new URLSearchParams();
		body.append( 'action', 'npleadchat_update_status' );
		body.append( 'nonce', config.nonce );
		body.append( 'lead_id', select.getAttribute( 'data-lead-id' ) );
		body.append( 'status', select.value );

		select.disabled = true;

		fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( ! result || ! result.success ) {
					throw new Error( 'failed' );
				}
				select.setAttribute( 'data-previous', select.value );
			} )
			.catch( function () {
				select.value = select.getAttribute( 'data-previous' );
				window.alert( config.error );
			} )
			.then( function () {
				select.disabled = false;
			} );
	} );
}() );
