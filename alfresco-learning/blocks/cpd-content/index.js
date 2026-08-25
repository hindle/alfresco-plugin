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
			fileTitle,
			fileDescription
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

				<RichText
					tagName="h2"
					className="cpd-content__file-title"
					placeholder={ __( 'Enter file title...' ) }
					value={ fileTitle }
					onChange={ ( value ) =>
						setAttributes( { fileTitle: value } )
					}
				/>

				<RichText
					tagName="p"
					className="cpd-content__file-description"
					placeholder={ __( 'Enter file description...' ) }
					value={ fileDescription }
					onChange={ ( value ) =>
						setAttributes( { fileDescription: value } )
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
			fileId,
			fileTitle,
			fileDescription
		} = attributes;

		const blockProps = useBlockProps.save( {
			className: 'cpd-content',
		} );

		return (
			<div { ...blockProps }>
				<div className="cpd-content__content-container">
					<div className="cpd-content__image-container">
						<div className="cpd-content__image-wrapper">
							{ imageUrl && (
								<img
									className="cpd-content__image"
									src={ imageUrl }
									alt={ imageAlt }
								/>
							) }
						</div>
						<div className="cpd-content__image-overlay">
    					<a href="#" className="cpd-content__image-launch-button" data-video-id={ videoId }>Launch Video</a>
  					</div>
					</div>
					<div className="cpd-content__video-container">
						<video
							id="cpd-video"
							className="cpd-content__video video-js vjs-big-play-centered"
							controls
							preload="auto"
							poster= { imageUrl }
							data-setup="{}"
						>
							<source
								src="https://customer-f33zs165nr7gyfy4.cloudflarestream.com/6b9e68b07dfee8cc2d116e4c51d6a957/manifest/video.m3u8"
								type="application/x-mpegURL"
							/>
						</video>
					</div>
					<div className="cpd-content__title-container">
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
				<div className="cpd-content__spacer" />
				<div className="cpd-content__file-container">
					<RichText.Content
						tagName="h2"
						className="cpd-content__file-title"
						value={ fileTitle }
					/>
					<RichText.Content
						tagName="p"
						className="cpd-content__file-description"
						value={ fileDescription }
					/>
					<a href="#" className="cpd-content__button" data-file-id={ fileId }>Download Guide</a>
				</div>
			</div>
		);
	},
} );
