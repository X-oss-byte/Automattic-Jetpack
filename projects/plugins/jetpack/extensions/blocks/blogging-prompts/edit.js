import apiFetch from '@wordpress/api-fetch';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import icon from './icon';

function BloggingPromptsEdit( { attributes, setAttributes } ) {
	const { answerCount, gravatars, prompt, promptId, showLabel, showResponses } = attributes;
	const blockProps = useBlockProps( { className: 'jetpack-blogging-prompts' } );

	useEffect( () => {
		let path = '/jetpack/v4/blogging-prompts';

		if ( promptId ) {
			path = +'?prompt_id=' + encodeURIComponent( promptId );
		}

		apiFetch( { path } ).then( prompts => {
			const promptData = promptId ? prompts.find( p => p.id === promptId ) : prompts[ 0 ];

			setAttributes( {
				answerCount: promptData.answered_users_count,
				gravatars: promptData.answered_users_sample.map( ( { avatar } ) => ( { url: avatar } ) ),
				prompt: promptData.text,
				promptId: promptData.id,
			} );
		} );
	}, [ promptId, setAttributes ] );

	const onShowLabelChange = newValue => {
		setAttributes( { showLabel: newValue } );
	};

	const onShowResponsesChange = newValue => {
		setAttributes( { showResponses: newValue } );
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ _x( 'Settings', 'title of block settings sidebar section', 'jetpack' ) }>
					<ToggleControl
						label={ __( 'Show daily prompt label', 'jetpack' ) }
						checked={ showLabel }
						onChange={ onShowLabelChange }
					/>
					<ToggleControl
						label={ __( 'Show other responses', 'jetpack' ) }
						checked={ showResponses }
						onChange={ onShowResponsesChange }
					/>
				</PanelBody>
			</InspectorControls>

			{ showLabel && (
				<div className="jetpack-blogging-prompts__label">
					{ icon }
					{ __( 'Daily writing prompt', 'jetpack' ) }
				</div>
			) }

			<div className="jetpack-blogging-prompts__prompt">{ prompt }</div>

			{ showResponses && (
				<div className="jetpack-blogging-prompts__answers">
					{ gravatars &&
						gravatars.slice( 0, 3 ).map( ( { url } ) => {
							return (
								url && (
									// eslint-disable-next-line jsx-a11y/alt-text
									<img
										className="jetpack-blogging-prompts__answers-gravatar"
										// Gravatar are decorative, here.
										aria-hidden="true"
										src={ url }
										key={ url }
									/>
								)
							);
						} ) }

					<a
						className="jetpack-blogging-prompts__answers-link"
						href={ `https://wordpress.com/tag/dailyprompt-${ promptId }` }
						target="_blank"
						rel="noreferrer"
					>
						{ answerCount > 0
							? sprintf(
									// translators: %s is the number of responses.
									_n( 'View %s response', 'View all %s responses', answerCount, 'jetpack' ),
									answerCount
							  )
							: __( 'No other responses, yet.', 'jetpack' ) }
					</a>
				</div>
			) }
		</div>
	);
}

export default BloggingPromptsEdit;