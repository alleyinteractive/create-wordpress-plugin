import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import metadata from './block.json';

import './style.scss';

/* @ts-expect-error Provided types are inaccurate to the actual plugin API. */
registerBlockType(metadata, { edit });
