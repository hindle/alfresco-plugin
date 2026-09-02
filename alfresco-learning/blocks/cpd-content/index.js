import { registerBlockType } from '@wordpress/blocks';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
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
			fileDescription,
			postId,
		} = attributes;

		// useBlockProps() wires up the standard WP block wrapper, including the
		// generated `wp-block-alfresco-cpd-content` class that the stylesheets
		// are scoped to. `cpd-content` is passed explicitly (matching save())
		// because the container rules key off both classes.
		const blockProps = useBlockProps( { className: 'cpd-content' } );

		// Capture the post ID of the current post and save it
		const currentPostId = useSelect(
			( select ) => select( editorStore ).getCurrentPostId(),
			[]
		);

		useEffect( () => {
			if ( currentPostId && currentPostId !== postId ) {
				setAttributes( { postId: currentPostId } );
			}
		}, [ currentPostId ] );

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
	save: ( { attributes } ) => {
		const {
			title,
			description,
			imageUrl,
			imageAlt,
			videoId,
			fileId,
			fileTitle,
			fileDescription,
			postId,
		} = attributes;

		const blockProps = useBlockProps.save( {
			className: 'cpd-content',
		} );

		return (
			<div { ...blockProps } data-post-id={ postId }>
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
    					<a href="#" data-o-authenticated="1" className="cpd-content__image-launch-button" id="cpd-content__video-button">Play Video</a>
              <a href="/planning-hub/signup/" data-o-anonymous="1" className="cpd-content__image-launch-button">Login to Play</a>
  					</div>
					</div>
					<div className="cpd-content__video-container" data-video-id={ videoId }>
						<video
							id="cpd-content__video"
							className="cpd-content__video video-js vjs-big-play-centered"
							controls
							preload="auto"
							poster= { imageUrl }
							data-setup="{}"
						>
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
					<a href="#" data-o-authenticated="1" className="cpd-content__button" id="cpd-content__file-button" data-file-id={ fileId }>Download Guide</a>
          <a href="/planning-hub/signup/" data-o-anonymous="1" className="cpd-content__button">Login to Download</a>
				</div>
        <dialog id="cpd-content__video-error" className="cpd-content__error" closedby='any'>
          <p>An error has occorred when loading the video.</p>
          <p>Please refresh the page and try again. If this continues, please email <a href="mailto:info@alfrescolearning.co.uk">info@alfrescolearning.co.uk</a>.</p>
          <button commandfor="cpd-content__video-error" command="close" className="cpd-content__button">Close</button>
        </dialog>
        <dialog id="cpd-content__file-error" className="cpd-content__error" closedby='any'>
          <p>An error has occorred when downloading the guide.</p>
          <p>Please refresh the page and try again. If this continues, please email <a href="mailto:info@alfrescolearning.co.uk">info@alfrescolearning.co.uk</a>.</p>
          <button commandfor="cpd-content__file-error" command="close" className="cpd-content__button">Close</button>
        </dialog>
			</div>
		);
	},
} );
