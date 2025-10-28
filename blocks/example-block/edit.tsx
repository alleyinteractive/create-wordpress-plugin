import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

import './index.scss';

/**
 * The create-wordpress-plugin/example-block block edit function.
 *
 * @return {WPElement} Element to render.
 */
export default function Edit() {
  return (
    <p {...useBlockProps()}>
      { __('Block Title - hello from the editor!') }
    </p>
  );
}
