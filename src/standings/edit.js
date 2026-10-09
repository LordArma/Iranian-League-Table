import { InspectorControls, PanelColorSettings, ContrastChecker, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, RangeControl, RadioControl, TextControl, Button, ExternalLink } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import data, { effectiveValues, minimalOptions } from './values';

export default function Edit( { attributes, setAttributes, name } ) {
	const blockProps = useBlockProps();
	const { tableId } = attributes;
	const values = effectiveValues( attributes );
	const { i18n, schema } = data;

	const update = ( changes ) => {
		setAttributes( { options: minimalOptions( { ...values, ...changes }, tableId ) } );
	};

	const keysIn = ( section ) => Object.keys( schema ).filter( ( key ) => schema[ key ].section === section );

	const control = ( key ) => {
		const field = schema[ key ];
		const choices = Object.keys( field.choices || {} ).map( ( value ) => ( { value, label: field.choices[ value ] } ) );
		switch ( field.type ) {
			case 'league':
				return <SelectControl key={ key } label={ field.label } value={ values[ key ] } options={ choices } onChange={ ( v ) => update( { [ key ]: v } ) } __nextHasNoMarginBottom __next40pxDefaultSize />;
			case 'enum':
				return <RadioControl key={ key } label={ field.label } help={ field.help } selected={ values[ key ] } options={ choices } onChange={ ( v ) => update( { [ key ]: v } ) } />;
			case 'bool':
				return <ToggleControl key={ key } label={ field.label } help={ field.help } checked={ !! values[ key ] } onChange={ ( v ) => update( { [ key ]: v } ) } __nextHasNoMarginBottom />;
			case 'int':
				return <RangeControl key={ key } label={ field.label } help={ field.help } value={ values[ key ] } min={ field.min } max={ field.max } onChange={ ( v ) => update( { [ key ]: v === undefined ? field.default : v } ) } __nextHasNoMarginBottom __next40pxDefaultSize />;
			case 'text':
				return <TextControl key={ key } label={ field.label } help={ field.help } value={ values[ key ] } onChange={ ( v ) => update( { [ key ]: v } ) } __nextHasNoMarginBottom __next40pxDefaultSize />;
		}
		return null;
	};

	const presetOptions = [ { value: '', label: i18n.choosePreset } ].concat(
		Object.keys( data.presets ).map( ( id ) => ( { value: id, label: data.presets[ id ].label } ) )
	);
	const tableOptions = [ { value: '0', label: i18n.noTable } ].concat(
		data.tables.map( ( t ) => ( { value: String( t.id ), label: t.title } ) )
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ i18n.savedTable } initialOpen={ !! tableId || data.tables.length > 0 }>
					<SelectControl
						label={ i18n.savedTable }
						hideLabelFromVision
						value={ String( tableId || 0 ) }
						options={ tableOptions }
						help={ i18n.savedTableHelp }
						onChange={ ( v ) => setAttributes( { tableId: parseInt( v, 10 ) || 0, options: {} } ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<p><ExternalLink href={ data.builderUrl.replace( 'ilt-builder', 'ilt-tables' ) }>{ i18n.manage }</ExternalLink></p>
				</PanelBody>
				<PanelBody title={ i18n.content }>{ keysIn( 'content' ).map( control ) }</PanelBody>
				<PanelBody title={ i18n.zones } initialOpen={ false }>{ keysIn( 'zones' ).map( control ) }</PanelBody>
				<PanelColorSettings
					title={ i18n.colors }
					initialOpen={ false }
					colorSettings={ keysIn( 'colors' ).filter( ( key ) => schema[ key ].type === 'color' ).map( ( key ) => ( {
						label: schema[ key ].label,
						value: values[ key ],
						onChange: ( v ) => update( { [ key ]: v || schema[ key ].default } ),
					} ) ) }
				>
					<SelectControl
						label={ i18n.preset }
						value=""
						options={ presetOptions }
						onChange={ ( id ) => id && data.presets[ id ] && update( data.presets[ id ].values ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					{ keysIn( 'colors' ).filter( ( key ) => schema[ key ].type !== 'color' ).map( control ) }
					<ContrastChecker backgroundColor={ values.title_backcolor } textColor={ values.title_color } />
					<ContrastChecker backgroundColor={ values.odd_color } textColor={ values.text_color } />
					<ContrastChecker backgroundColor={ values.even_color } textColor={ values.text_color } />
				</PanelColorSettings>
				<PanelBody title={ i18n.sizes } initialOpen={ false }>
					{ keysIn( 'sizes' ).map( control ) }
					<Button variant="secondary" onClick={ () => setAttributes( { options: {} } ) }>{ i18n.reset }</Button>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender block={ name } attributes={ attributes } skipBlockSupportAttributes />
			</div>
		</>
	);
}
