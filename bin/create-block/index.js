#!/usr/bin/env node

const prompts = require('prompts');
const path = require('path');
const { sync: spawn } = require('cross-spawn');

const fs = require('fs');

// The directory where the blocks will be created relative to the current working directory.
const directoryName = 'blocks';

if (!fs.existsSync(directoryName)) {
  fs.mkdirSync(directoryName);
  // eslint-disable-next-line no-console
  console.log(`Directory '${directoryName}' created successfully!`);
  // Navigate to the directory to create the block.
  process.chdir(directoryName);
} else {
  process.chdir(directoryName);
}

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
    process.env.blockLanguage = language;
    const result = spawn(
      'npx',
      [
        '@wordpress/create-block',
        '--template', path.join(__dirname, 'selectTemplates.js'), '--no-plugin',
      ],
      { stdio: 'inherit' },
    );

    process.exit(result.status);
  } else {
    process.exit(1);
  }
})();
