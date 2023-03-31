const path = require('path');
const defaultValues = require('./defaultValues');

const { blockLanguage } = process.env;

/**
 * Custom Variables and templates for scaffolding blocks.
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
