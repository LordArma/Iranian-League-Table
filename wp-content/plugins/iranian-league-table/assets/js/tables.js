/**
 * Saved Tables list: copy buttons and delete confirmation.
 */
( function () {
	'use strict';

	var cfg = window.iltTables || {};
	var toast = document.getElementById( 'ilt-toast' );
	var timer = null;

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
		var area = document.createElement( 'textarea' );
		area.value = text;
		area.setAttribute( 'readonly', '' );
		area.style.position = 'fixed';
		area.style.opacity = '0';
		document.body.appendChild( area );
		area.select();
		var ok = false;
		try {
			ok = document.execCommand( 'copy' );
		} catch ( e ) {
			ok = false;
		}
		document.body.removeChild( area );
		return ok;
	}

	document.addEventListener( 'click', function ( event ) {
		var copy = event.target.closest( '.ilt-copy' );
		if ( copy ) {
			var text = copy.getAttribute( 'data-shortcode' );
			var done = function () {
				show( cfg.copied );
			};
			var fail = function () {
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

		var del = event.target.closest( '.ilt-delete' );
		if ( del && ! window.confirm( cfg.confirmDelete ) ) {
			event.preventDefault();
		}
	} );
} )();
