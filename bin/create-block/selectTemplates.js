const path = require('path');
const defaultValues = require('./defaultValues');

const { blockLanguage } = process.env;

/**
 * Custom variants for scaffolding blocks.
 *
 * Currently there are only two variants:
 * - static:  A block that scaffolds a save.js file
 *            that saves the content and markup directly in the post content.
 * - dynamic: A block that scaffolds a render.php template
 *            which can be used to render the block on the front-end.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/packages/create-block/docs/external-template.md#external-project-templates
 */
module.exports = {
  defaultValues,
  variants: {
    static: {
      blockTemplatesPath: path.join(__dirname, 'templates', blockLanguage),
    },
    dynamic: {
      blockTemplatesPath: path.join(__dirname, 'templates', blockLanguage),
      render: 'file:render.php',
    },
  },
  blockTemplatesPath: path.join(__dirname, 'templates', blockLanguage),
};
