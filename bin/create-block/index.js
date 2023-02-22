const path = require('path');

/**
 * Custom Variables and templates for scaffolding blocks.
 * @see https://github.com/WordPress/gutenberg/blob/trunk/packages/create-block/docs/external-template.md#external-project-templates
 */
module.exports = {
  defaultValues: {
    namespace: 'create-wordpress-plugin',
    plugin: false,
    description: '',
    dashicon: 'palmtree',
    category: 'widgets',
    editorScript: 'file:index.js',
    editorStyle: 'file:index.css',
    style: ['file:style-index.css'],
  },
  variants: {
    static: {},
    dynamic: {
      render: 'file:render.php',
    },
  },
  blockTemplatesPath: path.join(__dirname, 'templates', 'block'),
};
