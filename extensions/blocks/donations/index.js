/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import attributes from './attributes';
import edit from './edit';
import save from './save';
import icon from './icon';

/**
 * Style dependencies
 */
import './editor.scss';

export const name = 'donations';
export const title = __( 'Donations', 'jetpack' );
export const settings = {
	title,
	description: __( 'Collect one-time, monthly, or annually recurring donations.', 'jetpack' ),
	icon,
	category: 'earn',
	keywords: [ __( 'Donations', 'jetpack' ) ],
	supports: {
		html: false,
	},
	edit,
	save,
	attributes,
	example: {},
};
