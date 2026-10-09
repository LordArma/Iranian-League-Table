import { registerBlockType, createBlock } from '@wordpress/blocks';
import { next as nextShortcode } from '@wordpress/shortcode';
import metadata from './block.json';
import Edit from './edit';
import { fromShortcodeAttributes } from './values';

const SHORTCODE_ONLY = /^\s*\[iran_league(\s[^\]]*)?\]\s*$/i;

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
	transforms: {
		from: [
			{
				// Pasting or converting a classic paragraph that holds the shortcode.
				type: 'shortcode',
				tag: 'iran_league',
				transform: ( attrs ) => createBlock( metadata.name, fromShortcodeAttributes( attrs.named ) ),
			},
			{
				// A Shortcode block that contains only [iran_league ...].
				type: 'block',
				blocks: [ 'core/shortcode' ],
				isMatch: ( { text } ) => SHORTCODE_ONLY.test( text || '' ),
				transform: ( { text } ) => {
					const match = nextShortcode( 'iran_league', text );
					return createBlock( metadata.name, fromShortcodeAttributes( match ? match.shortcode.attrs.named : {} ) );
				},
			},
		],
	},
} );
