/**
 * Mirrors ILT_Attributes::sanitize_value() so the editor shows the same values the server renders.
 */
const data = window.iltBlock || { schema: {}, defaults: {}, presets: {}, tables: [], aliases: {}, i18n: {} };

export default data;

const TRUE_WORDS = [ 'true', '1', 'yes', 'on' ];
const FALSE_WORDS = [ 'false', '0', 'no', 'off' ];

export function normalize( key, value ) {
	const field = data.schema[ key ];
	if ( ! field ) {
		return undefined;
	}
	const invalid = field.invalid !== undefined ? field.invalid : field.default;
	if ( value === null || value === undefined || typeof value === 'object' ) {
		return invalid;
	}
	switch ( field.type ) {
		case 'league': {
			const slug = data.aliases[ String( value ).trim() ];
			return slug || field.default;
		}
		case 'enum': {
			const v = String( value ).trim().toLowerCase();
			return Object.prototype.hasOwnProperty.call( field.choices, v ) ? v : invalid;
		}
		case 'bool': {
			if ( typeof value === 'boolean' ) {
				return value;
			}
			const v = String( value ).trim().toLowerCase();
			if ( TRUE_WORDS.includes( v ) ) {
				return true;
			}
			if ( FALSE_WORDS.includes( v ) ) {
				return false;
			}
			return invalid;
		}
		case 'int': {
			const v = String( value ).trim();
			if ( ! /^\d+(\.\d+)?(px)?$/i.test( v ) || ( parseInt( v, 10 ) === 0 && field.min > 0 ) ) {
				return invalid;
			}
			return Math.max( field.min, Math.min( field.max, parseInt( v, 10 ) ) );
		}
		case 'text':
			return String( value ).replace( /["'[\]]/g, '' ).trim().slice( 0, 60 );
		case 'color': {
			const v = String( value ).trim();
			if (
				/^#([0-9a-f]{3}){1,2}$/i.test( v ) ||
				/^[a-z]{3,20}$/i.test( v ) ||
				/^(rgba?|hsla?)\(\s*[0-9.,%\s/]+\)$/i.test( v )
			) {
				return v.toLowerCase();
			}
			return invalid;
		}
	}
	return invalid;
}

/** Base values for a block: the saved table's values, or the defaults. */
export function baseValues( tableId ) {
	const table = tableId ? data.tables.find( ( t ) => t.id === tableId ) : null;
	return table ? table.values : data.defaults;
}

/** Effective values: base + the block's own options. */
export function effectiveValues( attributes ) {
	const values = { ...baseValues( attributes.tableId ) };
	Object.keys( attributes.options || {} ).forEach( ( key ) => {
		if ( data.schema[ key ] ) {
			values[ key ] = normalize( key, attributes.options[ key ] );
		}
	} );
	return values;
}

/** Keeps only options that differ from the base, so saved tables can still restyle the block. */
export function minimalOptions( values, tableId ) {
	const base = baseValues( tableId );
	const out = {};
	Object.keys( data.schema ).forEach( ( key ) => {
		if ( values[ key ] !== undefined && values[ key ] !== base[ key ] ) {
			out[ key ] = values[ key ];
		}
	} );
	return out;
}

/** Block attributes from shortcode attributes (named, lower-cased keys). */
export function fromShortcodeAttributes( named ) {
	const atts = {};
	Object.keys( named || {} ).forEach( ( k ) => {
		atts[ k.toLowerCase() ] = named[ k ];
	} );
	const tableId = parseInt( atts.id, 10 ) || 0;
	const values = {};
	Object.keys( data.schema ).forEach( ( key ) => {
		if ( atts[ key ] !== undefined ) {
			values[ key ] = normalize( key, atts[ key ] );
		}
	} );
	return { tableId, options: minimalOptions( { ...baseValues( tableId ), ...values }, tableId ) };
}
