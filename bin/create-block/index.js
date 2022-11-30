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
    editorStyle: 'file:editor.scss',
    style: 'file:style.scss',
  },
  variants: {
    static: {},
    dynamic: {},
  },
  blockTemplatesPath: path.join(__dirname, 'templates', 'block'),
};