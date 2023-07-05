const path = require('path');
const fs = require('fs');

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

module.exports = (env, { mode }) => ({
  ...defaultConfig,

  // Dynamically produce entries from the slotfills index file and all blocks.
  entry: () => {
    const blocks = defaultConfig.entry();

    return {
      ...blocks,
      ...fs
        .readdirSync(path.join(__dirname, 'entries'))
        .reduce((acc, dirPath) => {
          acc[
            `entries-${dirPath}`
          ] = path.join(__dirname, 'entries', dirPath);
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

      // Process all non-entries entries.
      if (!pathData.chunk.name.includes('entries-')) {
        return '[name].js';
      }

      const srcDirname = dirname.replace('entries-', '');
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
          from: '**/{index.php,*.css}',
          context: 'entries',
          noErrorOnMissing: true,
        },
      ],
    }),
    new MiniCssExtractPlugin({
      filename: (pathData) => {
        const dirname = pathData.chunk.name;
        // Process all blocks.
        if (!pathData.chunk.name.includes('entries-')) {
          return '[name].css';
        }

        const srcDirname = dirname.replace('entries-', '');
        return `${srcDirname}/index.css`;
      },
    }),
    new CleanWebpackPlugin({
      cleanAfterEveryBuildPatterns: [
        /**
         * Remove duplicate entry CSS files generated from default
         * MiniCssExtractPlugin plugin in wpScripts.
         *
         * The default MiniCssExtractPlugin filename is [name].css
         * resulting in the generation of the entries-*.css files.
         * The configuration in this file for MiniCssExtractPlugin outputs
         * the entry CSS into the entry src directory name.
         */
        'entries-*.css',
        // Maps are built when running the start mode with wpScripts.
        'entries-*.css.map',
      ],
      protectWebpackAssets: false,
    }),
  ],

  // This webpack alias rule is needed at the root to ensure that the paths are resolved
  // using the custom alias defined below.
  resolve: {
    alias: {
      ...defaultConfig.resolve.alias,
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
  devServer: mode === 'production' ? {} : {
    ...defaultConfig.devServer,
    allowedHosts: 'all',
    static: {
      directory: '/build',
    },
  },
});
