/**
 * Shortcode Builder.
 *
 * Reads the schema-generated form, keeps a live server-rendered preview (REST /preview),
 * builds the shortcode client-side (mirrors ILT_Attributes::to_shortcode()), and handles
 * copy, reset, presets, import and saved tables.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.iltBuilder;
	if ( ! cfg ) {
		return;
	}

	var $form = $( '#ilt-builder-form' );
	var $preview = $( '#ilt-preview' );
	var $shortcode = $( '#ilt-shortcode' );
	var $toast = $( '#ilt-toast' );
	var table = cfg.table; // { id, title, values } while editing a saved table.
	var previewTimer = null;
	var previewRequest = 0;
	var toastTimer = null;
	var silent = false; // Suppresses change handlers while the form is filled programmatically.
	var shadow = null;

	// ---- Values -------------------------------------------------------------

	function normalize( key, value ) {
		var field = cfg.schema[ key ];
		var fallback = cfg.defaults[ key ];
		switch ( field.type ) {
			case 'bool':
				return !! value;
			case 'int':
				var n = parseInt( value, 10 );
				if ( isNaN( n ) || n < 0 || ( n === 0 && field.min > 0 ) ) {
					return fallback;
				}
				return Math.max( field.min, Math.min( field.max, n ) );
			case 'color':
				value = String( value || '' ).trim().toLowerCase();
				return value === '' ? fallback : value;
			case 'text':
				return String( value || '' ).replace( /["'\[\]]/g, '' ).trim().slice( 0, 60 );
			default:
				return value === undefined || value === null || value === '' ? fallback : String( value );
		}
	}

	function getValues() {
		var values = {};
		Object.keys( cfg.schema ).forEach( function ( key ) {
			var field = cfg.schema[ key ];
			var $inputs = $form.find( '[data-ilt-key="' + key + '"]' );
			var raw;
			if ( field.type === 'league' || field.type === 'enum' ) {
				raw = $inputs.filter( ':checked' ).val();
			} else if ( field.type === 'bool' ) {
				raw = $inputs.prop( 'checked' );
			} else {
				raw = $inputs.val();
			}
			values[ key ] = normalize( key, raw );
		} );
		return values;
	}

	function setValues( values ) {
		silent = true;
		Object.keys( cfg.schema ).forEach( function ( key ) {
			if ( ! Object.prototype.hasOwnProperty.call( values, key ) ) {
				return;
			}
			var field = cfg.schema[ key ];
			var value = values[ key ];
			var $inputs = $form.find( '[data-ilt-key="' + key + '"]' );
			if ( field.type === 'league' || field.type === 'enum' ) {
				$inputs.filter( '[value="' + value + '"]' ).prop( 'checked', true );
			} else if ( field.type === 'bool' ) {
				$inputs.prop( 'checked', !! value );
			} else if ( field.type === 'text' ) {
				$inputs.val( value );
			} else if ( field.type === 'int' ) {
				$inputs.val( value );
				$form.find( '[data-ilt-range="' + key + '"]' ).val( value );
			} else if ( field.type === 'color' ) {
				$inputs.val( value );
				if ( /^#([0-9a-f]{3}){1,2}$/i.test( value ) ) {
					try {
						$inputs.wpColorPicker( 'color', value );
					} catch ( e ) {}
				}
				$inputs.closest( '.wp-picker-container' ).find( '.wp-color-result' ).css( 'background-color', value );
			}
		} );
		silent = false;
		changed();
	}

	function sameValues( a, b ) {
		return Object.keys( cfg.schema ).every( function ( key ) {
			return a[ key ] === b[ key ];
		} );
	}

	// ---- Shortcode (mirror of ILT_Attributes::to_shortcode) -----------------

	function toString( value ) {
		return typeof value === 'boolean' ? ( value ? 'true' : 'false' ) : String( value );
	}

	function toShortcode( values, id, base ) {
		var compare = base || cfg.defaults;
		var parts = [ 'iran_league' ];
		if ( id ) {
			parts.push( 'id="' + parseInt( id, 10 ) + '"' );
		}
		Object.keys( cfg.schema ).forEach( function ( key ) {
			if ( values[ key ] !== compare[ key ] ) {
				parts.push( key + '="' + toString( values[ key ] ) + '"' );
			}
		} );
		return '[' + parts.join( ' ' ) + ']';
	}

	function updateShortcode() {
		var values = getValues();
		var code;
		if ( table ) {
			code = toShortcode( values, table.id, table.values );
			var dirty = ! sameValues( values, table.values );
			$( '.ilt-editing__unsaved' ).text( cfg.i18n.unsaved ).prop( 'hidden', ! dirty );
		} else {
			code = toShortcode( values );
		}
		$shortcode.val( code );
	}

	// ---- Preview ------------------------------------------------------------

	function previewRoot() {
		if ( shadow ) {
			return shadow;
		}
		var host = $preview.get( 0 );
		if ( host.attachShadow ) {
			// Isolates the table from admin styles, so it looks like the front end.
			shadow = host.attachShadow( { mode: 'open' } );
		} else {
			shadow = host;
		}
		return shadow;
	}

	function showPreview( html ) {
		var root = previewRoot();
		root.innerHTML =
			'<link rel="stylesheet" href="' + cfg.styleUrl + '">' +
			'<style>:host{display:block;font-family:Tahoma,Vazirmatn,sans-serif;}</style>' +
			html;
	}

	function showPreviewMessage( text ) {
		showPreview( '<p style="padding:1em;color:#50575e">' + $( '<div>' ).text( text ).html() + '</p>' );
	}

	function loadPreview() {
		var id = ++previewRequest;
		$preview.attr( 'aria-busy', 'true' ).addClass( 'is-loading' );
		request( 'POST', 'preview', { values: getValues() } )
			.done( function ( res ) {
				if ( id !== previewRequest ) {
					return;
				}
				showPreview( res.html );
				// Use the values the server actually applied (e.g. an invalid color falls back to its default).
				$shortcode.val( table ? toShortcode( res.values, table.id, table.values ) : toShortcode( res.values ) );
			} )
			.fail( function () {
				if ( id === previewRequest ) {
					showPreviewMessage( cfg.i18n.previewError );
				}
			} )
			.always( function () {
				if ( id === previewRequest ) {
					$preview.attr( 'aria-busy', 'false' ).removeClass( 'is-loading' );
				}
			} );
	}

	// ---- Contrast (WCAG 2.x) -------------------------------------------------

	var probe = null;

	function toRgb( color ) {
		if ( ! probe ) {
			probe = document.createElement( 'span' );
			probe.style.display = 'none';
			document.body.appendChild( probe );
		}
		probe.style.color = '';
		probe.style.color = color;
		if ( ! probe.style.color ) {
			return null;
		}
		var m = getComputedStyle( probe ).color.match( /[\d.]+/g );
		return m ? m.slice( 0, 3 ).map( Number ) : null;
	}

	function luminance( rgb ) {
		var c = rgb.map( function ( v ) {
			v = v / 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * c[ 0 ] + 0.7152 * c[ 1 ] + 0.0722 * c[ 2 ];
	}

	function contrast( a, b ) {
		var ra = toRgb( a );
		var rb = toRgb( b );
		if ( ! ra || ! rb ) {
			return null;
		}
		var la = luminance( ra );
		var lb = luminance( rb );
		return ( Math.max( la, lb ) + 0.05 ) / ( Math.min( la, lb ) + 0.05 );
	}

	function updateContrast() {
		var v = getValues();
		var pairs = [
			[ cfg.i18n.header, v.title_backcolor, v.title_color ],
			[ cfg.i18n.oddRows, v.odd_color, v.text_color ],
			[ cfg.i18n.evenRows, v.even_color, v.text_color ],
		];
		var messages = [];
		pairs.forEach( function ( p ) {
			var ratio = contrast( p[ 1 ], p[ 2 ] );
			if ( ratio !== null && ratio < 4.5 ) {
				messages.push( cfg.i18n.lowContrast.replace( '%1$s', p[ 0 ] ).replace( '%2$s', ratio.toFixed( 1 ) ) );
			}
		} );
		var $box = $( '#ilt-contrast' );
		$box.empty().prop( 'hidden', ! messages.length );
		messages.forEach( function ( text ) {
			$box.append( $( '<p>' ).text( text ) );
		} );
	}

	function changed() {
		if ( silent ) {
			return;
		}
		updateContrast();
		updateShortcode();
		clearTimeout( previewTimer );
		previewTimer = setTimeout( loadPreview, 300 );
	}

	// ---- Helpers ------------------------------------------------------------

	function request( method, path, data ) {
		return $.ajax( {
			url: cfg.rest.root + path,
			method: method,
			contentType: 'application/json',
			data: data ? JSON.stringify( data ) : undefined,
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', cfg.rest.nonce );
			},
		} );
	}

	function toast( text, isError ) {
		clearTimeout( toastTimer );
		$toast.text( text ).toggleClass( 'is-error', !! isError ).addClass( 'is-visible' );
		toastTimer = setTimeout( function () {
			$toast.removeClass( 'is-visible' );
		}, 2500 );
	}

	function errorMessage( xhr, fallback ) {
		return ( xhr && xhr.responseJSON && xhr.responseJSON.message ) || fallback;
	}

	function copyText( text ) {
		var deferred = $.Deferred();
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then( deferred.resolve, function () {
				fallbackCopy( text ) ? deferred.resolve() : deferred.reject();
			} );
		} else if ( fallbackCopy( text ) ) {
			deferred.resolve();
		} else {
			deferred.reject();
		}
		return deferred.promise();
	}

	function fallbackCopy( text ) {
		var $tmp = $( '<textarea readonly>' ).val( text ).css( { position: 'fixed', top: 0, left: 0, opacity: 0 } ).appendTo( 'body' );
		$tmp.get( 0 ).select();
		var ok = false;
		try {
			ok = document.execCommand( 'copy' );
		} catch ( e ) {
			ok = false;
		}
		$tmp.remove();
		return ok;
	}

	function setTable( next ) {
		table = next;
		var $notice = $( '.ilt-editing' );
		if ( table ) {
			$notice.prop( 'hidden', false ).find( '.ilt-editing__text' ).text( cfg.i18n.editing.replace( '%s', table.title ) );
			$( '#ilt-table-title' ).val( table.title );
			$( '#ilt-save' ).text( cfg.i18n.update );
			$( '#ilt-save-new' ).prop( 'hidden', false );
			if ( window.history && window.history.replaceState && ! cfg.modal ) {
				window.history.replaceState( null, '', cfg.urls.builder + '&table=' + table.id );
			}
		} else {
			$notice.prop( 'hidden', true );
			$( '#ilt-save' ).text( cfg.i18n.saveAs );
			$( '#ilt-save-new' ).prop( 'hidden', true );
		}
		updateShortcode();
	}

	function save( asNew ) {
		var editing = table && ! asNew;
		var data = { title: $( '#ilt-table-title' ).val(), values: getValues() };
		var $buttons = $( '#ilt-save, #ilt-save-new' ).prop( 'disabled', true );
		request( 'POST', editing ? 'tables/' + table.id : 'tables', data )
			.done( function ( res ) {
				setTable( { id: res.id, title: res.title, values: res.values } );
				toast( cfg.i18n.saved );
			} )
			.fail( function ( xhr ) {
				toast( errorMessage( xhr, cfg.i18n.saveFailed ), true );
			} )
			.always( function () {
				$buttons.prop( 'disabled', false );
			} );
	}

	// ---- Wiring -------------------------------------------------------------

	$form.find( '.ilt-color' ).wpColorPicker( {
		change: function ( event, ui ) {
			// Iris fires before the input value updates.
			$( event.target ).val( ui.color.toString() );
			changed();
		},
		clear: function () {
			setTimeout( changed, 0 );
		},
	} );

	$form.on( 'input change', '[data-ilt-key]', function () {
		var key = $( this ).data( 'iltKey' );
		if ( cfg.schema[ key ] && cfg.schema[ key ].type === 'int' ) {
			$form.find( '[data-ilt-range="' + key + '"]' ).val( $( this ).val() );
		}
		changed();
	} );

	$form.on( 'input', '[data-ilt-range]', function () {
		var key = $( this ).data( 'iltRange' );
		$form.find( '[data-ilt-key="' + key + '"]' ).val( $( this ).val() );
		changed();
	} );

	// Normalize out-of-range numbers when leaving the field.
	$form.on( 'blur', 'input[type="number"][data-ilt-key]', function () {
		var key = $( this ).data( 'iltKey' );
		var value = normalize( key, $( this ).val() );
		$( this ).val( value );
		$form.find( '[data-ilt-range="' + key + '"]' ).val( value );
	} );

	$form.on( 'submit', function ( e ) {
		e.preventDefault();
	} );

	$( '#ilt-preset' ).on( 'change', function () {
		var preset = cfg.presets[ $( this ).val() ];
		if ( preset ) {
			setValues( preset.values );
			toast( cfg.i18n.presetApplied );
		}
	} );

	$( '#ilt-copy' ).on( 'click', function () {
		copyText( $shortcode.val() )
			.done( function () {
				toast( cfg.i18n.copied );
			} )
			.fail( function () {
				$shortcode.trigger( 'focus' ).trigger( 'select' );
				toast( cfg.i18n.copyFailed, true );
			} );
	} );

	$shortcode.on( 'focus', function () {
		this.select();
	} );

	$( '#ilt-reset' ).on( 'click', function () {
		$( '#ilt-preset' ).val( '' );
		setValues( cfg.defaults );
		toast( cfg.i18n.resetDone );
	} );

	$( '#ilt-import-button' ).on( 'click', function () {
		var text = $( '#ilt-import' ).val();
		if ( ! text.trim() ) {
			$( '#ilt-import' ).trigger( 'focus' );
			return;
		}
		request( 'POST', 'parse', { shortcode: text } )
			.done( function ( res ) {
				if ( res.table ) {
					request( 'GET', 'tables/' + res.table.id ).done( function ( t ) {
						setTable( { id: t.id, title: t.title, values: t.values } );
						setValues( res.values );
					} );
				} else {
					setTable( null );
					setValues( res.values );
				}
				toast( cfg.i18n.imported );
			} )
			.fail( function ( xhr ) {
				toast( errorMessage( xhr, cfg.i18n.importFailed ), true );
			} );
	} );

	$( '#ilt-save' ).on( 'click', function () {
		save( false );
	} );
	$( '#ilt-save-new' ).on( 'click', function () {
		save( true );
	} );

	$( '#ilt-insert' ).on( 'click', function () {
		var parentWindow = window.parent;
		try {
			if ( parentWindow && parentWindow !== window && typeof parentWindow.send_to_editor === 'function' ) {
				parentWindow.send_to_editor( $shortcode.val() );
				if ( typeof parentWindow.tb_remove === 'function' ) {
					parentWindow.tb_remove();
				}
				return;
			}
		} catch ( e ) {}
		toast( cfg.i18n.insertMissing, true );
	} );

	showPreviewMessage( cfg.i18n.loading );
	if ( table ) {
		setTable( table );
	}
	updateShortcode();
	updateContrast();
	loadPreview();
} )( jQuery );
