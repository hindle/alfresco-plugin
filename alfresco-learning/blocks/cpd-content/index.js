import { registerBlockType } from '@wordpress/blocks';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { Button, PanelBody, TextControl } from '@wordpress/components';
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
			imageId,
			imageUrl,
			imageAlt,
			videoId,
			fileId,
		} = attributes;

		// useBlockProps() wires up the standard WP block wrapper, including the
		// generated `wp-block-alfresco-cpd-content` class that the stylesheets
		// are scoped to. `cpd-content` is passed explicitly (matching save())
		// because the container rules key off both classes.
		const blockProps = useBlockProps( { className: 'cpd-content' } );

		const onSelectImage = ( media ) => {
			setAttributes( {
				imageId: media.id,
				imageUrl: media.url,
				imageAlt: media.alt || '',
			} );
		};

		const onRemoveImage = () => {
			setAttributes( { imageId: undefined, imageUrl: '', imageAlt: '' } );
		};

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

				<div className="cpd-content__image-field">
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectImage }
							allowedTypes={ [ 'image' ] }
							value={ imageId }
							render={ ( { open } ) =>
								imageUrl ? (
									<div className="cpd-content__image-wrapper">
										<img
											className="cpd-content__image"
											src={ imageUrl }
											alt={ imageAlt }
										/>
										<div className="cpd-content__image-actions">
											<Button
												variant="secondary"
												onClick={ open }
											>
												{ __( 'Replace image' ) }
											</Button>
											<Button
												variant="tertiary"
												isDestructive
												onClick={ onRemoveImage }
											>
												{ __( 'Remove image' ) }
											</Button>
										</div>
									</div>
								) : (
									<Button variant="secondary" onClick={ open }>
										{ __( 'Select image' ) }
									</Button>
								)
							}
						/>
					</MediaUploadCheck>
				</div>

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
			imageUrl,
			imageAlt,
			videoId,
			fileId
		} = attributes;

		const blockProps = useBlockProps.save( {
			className: 'cpd-content',
		} );

		return (
			<div { ...blockProps }>
				{ imageUrl && (
					<img
						className="cpd-content__image"
						src={ imageUrl }
						alt={ imageAlt }
					/>
				) }
				<div className="cpd-content__wrapper">
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
				</div>
			</div>
		);
	},
} );
