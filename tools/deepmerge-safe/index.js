'use strict';

const UNSAFE = new Set( [ '__proto__', 'constructor', 'prototype' ] );

function isMergeable( value ) {
	if ( ! value || typeof value !== 'object' ) {
		return false;
	}
	const tag = Object.prototype.toString.call( value );
	return tag !== '[object RegExp]' && tag !== '[object Date]';
}

function clone( value, options ) {
	return options.clone !== false && options.isMergeableObject( value )
		? deepmerge( Array.isArray( value ) ? [] : {}, value, options )
		: value;
}

function keys( target ) {
	return Object.keys( target ).concat(
		Object.getOwnPropertySymbols( target ).filter( ( s ) =>
			Object.prototype.propertyIsEnumerable.call( target, s )
		)
	).filter( ( key ) => ! UNSAFE.has( key ) );
}

function mergeObject( target, source, options ) {
	const result = {};
	if ( options.isMergeableObject( target ) ) {
		keys( target ).forEach( ( key ) => {
			result[ key ] = clone( target[ key ], options );
		} );
	}
	keys( source ).forEach( ( key ) => {
		const has = Object.prototype.hasOwnProperty.call( target, key );
		if ( has && options.isMergeableObject( source[ key ] ) ) {
			const custom = options.customMerge && options.customMerge( key, options );
			result[ key ] = ( typeof custom === 'function' ? custom : deepmerge )( target[ key ], source[ key ], options );
		} else {
			result[ key ] = clone( source[ key ], options );
		}
	} );
	return result;
}

function deepmerge( target, source, options ) {
	options = Object.assign( {}, options );
	options.arrayMerge = options.arrayMerge || ( ( t, s, o ) => t.concat( s ).map( ( v ) => clone( v, o ) ) );
	options.isMergeableObject = options.isMergeableObject || isMergeable;
	options.cloneUnlessOtherwiseSpecified = ( v, o ) => clone( v, o );

	const sourceIsArray = Array.isArray( source );
	if ( sourceIsArray !== Array.isArray( target ) ) {
		return clone( source, options );
	}
	return sourceIsArray ? options.arrayMerge( target, source, options ) : mergeObject( target, source, options );
}

deepmerge.all = function ( array, options ) {
	if ( ! Array.isArray( array ) ) {
		throw new Error( 'first argument should be an array' );
	}
	return array.reduce( ( prev, next ) => deepmerge( prev, next, options ), {} );
};

module.exports = deepmerge;
