const path = require('path');
const fs = require('fs');

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = (env, { mode }) => ({
  ...defaultConfig,

  // Dynamically produce entries from the slotfills index file and all blocks.
  entry: () => {
    const blocks = defaultConfig.entry();

    return {
      ...blocks,
      ...fs
        .readdirSync('./entries')
        .reduce((acc, dirPath) => {
          acc[
            `entries-${dirPath}`
          ] = `./entries/${dirPath}`;
          return acc;
        }, {
          // All other custom entry points can be included here.
        }),
    };
  },

  // Use different filenames for production and development builds for clarity.
  output: {
    clean: mode === 'production',
    filename: (pathData) => {
      const dirname = pathData.chunk.name;

      // Process all blocks.
      if (!pathData.chunk.name.includes('src-')) {
        return '[name].js';
      }

      const srcDirname = dirname.replace('src-', '');
      return `${srcDirname}/index.js`;
    },
    path: path.join(__dirname, 'build'),
  },

  // Configure plugins.
  plugins: [
    ...defaultConfig.plugins,
    new CopyWebpackPlugin({
      patterns: [
        {
          from: '**/{block.json,*.php,*.css}',
          context: 'src',
          noErrorOnMissing: true,
        },
      ],
    }),
  ],

  // This webpack alias rule is needed at the root to ensure that the paths are resolved
  // using the custom alias defined below.
  resolve: {
    ...defaultConfig.resolve,
    alias: {
      ...defaultConfig.alias,
      '@': path.resolve(__dirname),
    },
    extensions: ['.js', '.jsx', '.ts', '.tsx', '...'],
  },

  // Cache the generated webpack modules and chunks to improve build speed.
  // @see https://webpack.js.org/configuration/cache/
  cache: {
    ...defaultConfig.cache,
    type: 'filesystem',
  },
});