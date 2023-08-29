const path = require('path');
const fs = require('fs');

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

/**
 * Process the filename and chunkFilename for Webpack output and MiniCssExtractPlugin.
 * This reusable function dynamically generates filenames based on the provided `pathData`,
 * a flag to determine whether to set the filename as 'index', the file extension (`ext`),
 * and a parameter to explicitly specify whether to use 'runtime' or 'name' as the dirname source.
 *
 * For non-entries entries, it returns a filename in the format '[name].[ext]'.
 * For entries, it constructs a filename with the directory name (stripping 'entries-')
 * and appends '/index' or '/[name]' (if a name is present) followed by the file extension.
 *
 * @param   {Object}  pathData      - The path data object provided by Webpack.
 * @param   {boolean} setAsIndex    - A flag to determine whether to set the filename as 'index'
 *                                    when processing entries. Pass `true` to use 'index' or `false`
 *                                    to use '[name]'.
 * @param   {string}  ext           - The file extension to be used for the output filename.
 * @param   {string}  dirnameSource - The pathData.chunk prop to set the directory name.
 *                                    'runtime' or 'name'. Defaults to 'name' if not provided.
 * @returns {string}                  The generated filename.
 */
const processFilename = (pathData, setAsIndex, ext, dirnameSource = 'name') => {
  const dirname = dirnameSource === 'runtime'
    ? pathData.chunk.runtime : pathData.chunk.name;

  let filename = '[name]';
  if (typeof setAsIndex === 'boolean' && setAsIndex) {
    filename = 'index';
  }

  // Process all non-entries entries.
  if (!dirname.includes('entries-')) {
    return `[name].${ext}`;
  }

  const srcDirname = dirname.replace('entries-', '');
  return `${srcDirname}/${filename}.${ext}`;
};

module.exports = (env, { mode }) => ({
  ...defaultConfig,

  // Dynamically produce entries from the slotfills index file and all blocks.
  entry: () => {
    const blocks = defaultConfig.entry();

    /**
     * Get the entry points from a directory.
     *
     * @returns {Object} An object of entries.
     */
    function getEntries(entryDirName) {
      const directoryPath = path.join(__dirname, entryDirName);
      const directoryExists = fs.existsSync(directoryPath);

      if (directoryExists) {
        return fs
          .readdirSync(directoryPath)
          .reduce((acc, dirPath) => {
            // Ignore .gitkeep files.
            if (dirPath?.includes('.gitkeep')) {
              return acc;
            }

            acc[
              `${entryDirName}-${dirPath}`
            ] = path.join(__dirname, entryDirName, dirPath);
            return acc;
          }, {});
      }
      // eslint-disable-next-line no-console
      console.log(`Directory "${entryDirName}" does not exist.`);
      return {};
    }

    return {
      ...blocks,
      ...getEntries('entries'),
      ...{
        // All other custom entry points can be included here.
      },
    };
  },

  // Use different filenames for production and development builds for clarity.
  output: {
    clean: mode === 'production',
    filename: (pathData) => processFilename(pathData, true, 'js'),
    chunkFilename: (pathData) => processFilename(pathData, false, 'js', 'runtime'),
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
      filename: (pathData) => processFilename(pathData, true, 'css'),
      chunkFilename: (pathData) => processFilename(pathData, false, 'css', 'runtime'),
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
