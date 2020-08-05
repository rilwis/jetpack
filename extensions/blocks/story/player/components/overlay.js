/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/no-static-element-interactions */

/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { createElement, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DecoratedButton } from './button';
import { PlayIcon, ReplayIcon, CloseIcon, NavigateBeforeIcon, NavigateNextIcon } from './icons';

export default function Overlay( {
	playing,
	ended,
	disabled,
	onClick,
	hasPrevious,
	hasNext,
	onNextSlide,
	onPreviousSlide,
	tapToPlayPause,
} ) {
	const onOverlayPressed = () => {
		! disabled && tapToPlayPause && onClick();
	};

	const onPlayPressed = useCallback(
		event => {
			if ( tapToPlayPause || disabled ) {
				// let the event bubble
				return;
			}
			event.stopPropagation();
			onClick();
		},
		[ tapToPlayPause, onClick ]
	);

	const onPreviousSlideHandler = useCallback(
		event => {
			event.stopPropagation();
			onPreviousSlide();
		},
		[ onPreviousSlide ]
	);

	const onNextSlideHandler = useCallback(
		event => {
			event.stopPropagation();
			onNextSlide();
		},
		[ onNextSlide ]
	);

	return (
		<div
			className={ classNames( {
				'wp-story-overlay': true,
				'wp-story-clickable': tapToPlayPause,
			} ) }
			onClick={ onOverlayPressed }
		>
			<div className="wp-story-prev-slide" onClick={ onPreviousSlideHandler }>
				{ hasPrevious && (
					<DecoratedButton size={ 44 } label="Previous Slide" className="outlined-w">
						<NavigateBeforeIcon size={ 24 } />
					</DecoratedButton>
				) }
			</div>
			<div className="wp-story-next-slide" onClick={ onNextSlideHandler }>
				{ hasNext && (
					<DecoratedButton size={ 44 } label="Next Slide" className="outlined-w">
						<NavigateNextIcon size={ 24 } />
					</DecoratedButton>
				) }
			</div>
			{ tapToPlayPause && ! playing && ! ended && (
				<DecoratedButton size={ 80 } label="Play Story" onClick={ onPlayPressed }>
					<PlayIcon size={ 56 } />
				</DecoratedButton>
			) }
			{ ended && (
				<DecoratedButton size={ 80 } label="Replay Story" onClick={ onPlayPressed }>
					<ReplayIcon size={ 56 } />
				</DecoratedButton>
			) }
		</div>
	);
}
