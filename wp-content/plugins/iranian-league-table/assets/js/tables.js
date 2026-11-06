/**
 * Saved Tables list: copy buttons and delete confirmation.
 */
( function () {
	'use strict';

	const cfg = window.iltTables || {};
	const toast = document.getElementById( 'ilt-toast' );
	let timer = null;

	function show( text, isError ) {
		if ( ! toast ) {
			return;
		}
		clearTimeout( timer );
		toast.textContent = text;
		toast.classList.toggle( 'is-error', !! isError );
		toast.classList.add( 'is-visible' );
		timer = setTimeout( function () {
			toast.classList.remove( 'is-visible' );
		}, 2500 );
	}

	function fallbackCopy( text ) {
		const area = document.createElement( 'textarea' );
		area.value = text;
		area.setAttribute( 'readonly', '' );
		area.style.position = 'fixed';
		area.style.opacity = '0';
		document.body.appendChild( area );
		area.select();
		let ok = false;
		try {
			ok = document.execCommand( 'copy' );
		} catch {
			ok = false;
		}
		document.body.removeChild( area );
		return ok;
	}

	document.addEventListener( 'click', function ( event ) {
		const copy = event.target.closest( '.ilt-copy' );
		if ( copy ) {
			const text = copy.getAttribute( 'data-shortcode' );
			const done = function () {
				show( cfg.copied );
			};
			const fail = function () {
				if ( fallbackCopy( text ) ) {
					done();
				} else {
					show( cfg.copyFailed, true );
				}
			};
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( text ).then( done, fail );
			} else {
				fail();
			}
			return;
		}

		const del = event.target.closest( '.ilt-delete' );
		// eslint-disable-next-line no-alert -- Confirms a destructive action.
		if ( del && ! window.confirm( cfg.confirmDelete ) ) {
			event.preventDefault();
		}
	} );
} )();
