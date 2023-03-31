#!/usr/bin/env node

const prompts = require('prompts');
const path = require('path');
const { sync: spawn } = require('cross-spawn');

const fs = require('fs');

// The directory where the blocks will be created relative to the current working directory.
const directoryName = 'blocks';

// Create the directory if it doesn't exist.
if (!fs.existsSync(directoryName)) {
  fs.mkdirSync(directoryName);
  // eslint-disable-next-line no-console
  console.log(`Directory '${directoryName}' created successfully!`);
  // Navigate to the directory to create the block.
  process.chdir(directoryName);
} else {
  process.chdir(directoryName);
}

/**
 * Prompts the user to select a block language (TypeScript or JavaScript)
 * and then create a block using the @wordpress/create-block package.
 */
(async () => {
  const response = await prompts({
    type: 'select',
    name: 'blockLanguage',
    message: 'Create a block in TypeScript or JavaScript?',
    choices: [
      { title: 'TypeScript', value: 'typescript' },
      { title: 'JavaScript', value: 'javascript' },
    ],
    initial: 0,
  });

  const language = response?.blockLanguage || null;

  if (language) {
    // Set the block language as an environment variable
    // so it can be used in the selectTemplates.js file.
    process.env.blockLanguage = language;

    // Create a block using the @wordpress/create-block package.
    const result = spawn(
      'npx',
      [
        '@wordpress/create-block',
        /**
         * This argument specifies an external npm package as a template.
         * In this case, the selectTemplates.js file is used as a the entry for the template.
         * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/#template
         */
        '--template',
        path.join(__dirname, 'selectTemplates.js'),
        /**
         * With this argument, the create-block package runs in
         * "No plugin mode" which only scaffolds block files into the current directory.
         * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/#no-plugin
         */
        '--no-plugin',
      ],
      { stdio: 'inherit' },
    );

    process.exit(result.status);
  } else {
    process.exit(1);
  }
})();
