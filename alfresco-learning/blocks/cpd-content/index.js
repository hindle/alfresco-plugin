import { registerBlockType } from '@wordpress/blocks';
import {
	RichText,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	// edit() is what shows up in wp-admin while editing the page.
	edit: ( { attributes, setAttributes } ) => {
		const {
			title,
			description,
			videoId,
			fileId,
		} = attributes;

		// useBlockProps() wires up the standard WP block wrapper (classes,
		// styles) automatically — this replaces the manual `props.className`
		// handling from the createElement version.
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody title={ __( 'Video ID' ) } initialOpen>
						<TextControl
							label={ __( 'Video ID' ) }
							help={ __(
								'ID from cloudflare.'
							) }
							value={ videoId }
							onChange={ ( value ) =>
								setAttributes( { videoId: value } )
							}
						/>
						<TextControl
							label={ __( 'File ID' ) }
							help={ __( 'ID from AWS S3.' ) }
							value={ fileId }
							onChange={ ( value ) =>
								setAttributes( { fileId: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>

				<RichText
					tagName="h1"
					className="cpd-content__title"
					placeholder={ __( 'Enter title...' ) }
					value={ title }
					onChange={ ( value ) =>
						setAttributes( { title: value } )
					}
				/>

				<RichText
					tagName="p"
					className="cpd-content__description"
					placeholder={ __( 'Enter description...' ) }
					value={ description }
					onChange={ ( value ) =>
						setAttributes( { description: value } )
					}
				/>
			</div>
		);
	},

	// save() defines the static HTML written into the database and served
	// on the front end — this is where the real <button id="..."> tags are.
	save: ( { attributes } ) => {
		const {
			title,
			description,
			videoId,
			fileId
		} = attributes;

		const blockProps = useBlockProps.save( {
			className: 'cpd-content',
		} );

		return (
			<div { ...blockProps }>
				<RichText.Content
					tagName="h1"
					className="cpd-content__title"
					value={ title }
				/>
				<RichText.Content
					tagName="p"
					className="cpd-content__description"
					value={ description }
				/>
				<div className="cpd-content__video">
					<iframe
						src={`https://videoplayer.cloudflare.com/${videoId}`}
						frameBorder="0"
						allowFullScreen
					/>
				</div>
			</div>
		);
	},
} );
