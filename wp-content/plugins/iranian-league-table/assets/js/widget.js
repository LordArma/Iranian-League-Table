/**
 * League Table widget form: color pickers and presets.
 * Works on the classic Widgets screen, in the Customizer and in the Legacy Widget block.
 */
( function ( $ ) {
	'use strict';

	function markChanged( $input ) {
		// Lets the Widgets screen / Customizer / Legacy Widget block notice the change.
		$input.trigger( 'change' );
	}

	function init( $scope ) {
		$scope.find( '.ilt-widget-color' ).each( function () {
			var $input = $( this );
			if ( $input.closest( '.wp-picker-container' ).length ) {
				return;
			}
			$input.wpColorPicker( {
				change: function ( event, ui ) {
					$input.val( ui.color.toString() );
					markChanged( $input );
				},
				clear: function () {
					markChanged( $input );
				},
			} );
		} );
	}

	$( document ).on( 'change', '.ilt-widget-preset', function () {
		var presets = $( this ).data( 'presets' ) || {};
		var values = presets[ $( this ).val() ];
		if ( ! values ) {
			return;
		}
		var $form = $( this ).closest( 'form, .widget-content, .wp-block-legacy-widget__edit-form' );
		Object.keys( values ).forEach( function ( key ) {
			var $input = $form.find( '.ilt-widget-color[data-ilt-key="' + key + '"]' );
			if ( ! $input.length ) {
				return;
			}
			$input.val( values[ key ] );
			try {
				$input.wpColorPicker( 'color', values[ key ] );
			} catch ( e ) {}
			markChanged( $input );
		} );
	} );

	$( document ).on( 'widget-added widget-updated', function ( event, $widget ) {
		init( $widget );
	} );

	$( function () {
		init( $( '#widgets-right, .widget-liquid-right, body' ) );
	} );
} )( jQuery );
